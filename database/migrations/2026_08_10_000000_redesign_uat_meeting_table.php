<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RedesignUatMeetingTable extends Migration
{
    public function up()
    {
        // Step 1: Normalize created_at/updated_at's invalid zero-date defaults to valid ones
        // The table predates Laravel migrations and has '0000-00-00 00:00:00' defaults which
        // cause MariaDB strict mode to reject any ALTER TABLE that rebuilds the table.
        // This fix is idempotent: only runs if the invalid default is still present.
        $columns = DB::select("
            SELECT COLUMN_NAME, COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'uat_meeting'
            AND COLUMN_NAME IN ('created_at', 'updated_at')
        ");

        foreach ($columns as $col) {
            // Check if default contains the invalid zero-date
            if (strpos($col->COLUMN_DEFAULT, '0000-00-00') !== false) {
                if ($col->COLUMN_NAME === 'created_at') {
                    DB::statement("ALTER TABLE uat_meeting MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
                } elseif ($col->COLUMN_NAME === 'updated_at') {
                    DB::statement("ALTER TABLE uat_meeting MODIFY updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                }
            }
        }

        // Step 2: Add the new columns
        Schema::table('uat_meeting', function (Blueprint $table) {
            if (!Schema::hasColumn('uat_meeting', 'requirement_id')) {
                $table->string('requirement_id')->nullable()->after('uat_id');
            }
            if (!Schema::hasColumn('uat_meeting', 'order_id')) {
                $table->string('order_id')->nullable()->after('requirement_id');
            }
            if (!Schema::hasColumn('uat_meeting', 'budget_change')) {
                $table->text('budget_change')->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('uat_meeting', 'parts_changes')) {
                $table->text('parts_changes')->nullable()->after('budget_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'add_on_parts')) {
                $table->text('add_on_parts')->nullable()->after('parts_changes');
            }
            if (!Schema::hasColumn('uat_meeting', 'parts_notes')) {
                $table->text('parts_notes')->nullable()->after('add_on_parts');
            }
            if (!Schema::hasColumn('uat_meeting', 'case_size_change')) {
                $table->string('case_size_change')->nullable()->after('parts_notes');
            }
            if (!Schema::hasColumn('uat_meeting', 'overall_notes')) {
                $table->text('overall_notes')->nullable()->after('case_size_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'quivicare_change')) {
                $table->enum('quivicare_change', ['no_change', 'add', 'remove', 'change_plan'])
                    ->nullable()->after('overall_notes');
            }
            if (!Schema::hasColumn('uat_meeting', 'quivithread_change')) {
                $table->enum('quivithread_change', ['no_change', 'add', 'remove', 'change_option'])
                    ->nullable()->after('quivicare_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'changes_required')) {
                $table->boolean('changes_required')->nullable()->after('quivithread_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'new_proposal_required')) {
                $table->boolean('new_proposal_required')->nullable()->after('changes_required');
            }
            if (!Schema::hasColumn('uat_meeting', 'customer_approval')) {
                $table->enum('customer_approval', ['pending', 'approved', 'rejected'])
                    ->nullable()->after('new_proposal_required');
            }
            if (!Schema::hasColumn('uat_meeting', 'follow_up_required')) {
                $table->boolean('follow_up_required')->nullable()->after('customer_approval');
            }
        });

        // Step 3: Drop the old columns with per-column guards for idempotency
        $oldColumns = [
            'initial_budget', 'reason', 'play_mode', 'include_monitor', 'include_notes',
            'notes', 'theme_style', 'preference', 'exemption', 'future_proof', 'case_size',
            'okay_with_aio', 'gpu_sag', 'need_rgb', 'qvcrf_tag', 'qvse', 'qvca', 'qvtd', 'qvtd_notes'
        ];

        foreach ($oldColumns as $column) {
            Schema::table('uat_meeting', function (Blueprint $table) use ($column) {
                if (Schema::hasColumn('uat_meeting', $column)) {
                    $table->dropColumn($column);
                }
            });
        }
    }

    public function down()
    {
        Schema::table('uat_meeting', function (Blueprint $table) {
            foreach ([
                'requirement_id', 'order_id', 'budget_change', 'parts_changes', 'add_on_parts',
                'parts_notes', 'case_size_change', 'overall_notes', 'quivicare_change',
                'quivithread_change', 'changes_required', 'new_proposal_required',
                'customer_approval', 'follow_up_required',
            ] as $column) {
                if (Schema::hasColumn('uat_meeting', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('uat_meeting', function (Blueprint $table) {
            if (!Schema::hasColumn('uat_meeting', 'initial_budget')) {
                $table->float('initial_budget')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'reason')) {
                $table->integer('reason')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'play_mode')) {
                $table->integer('play_mode')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'include_monitor')) {
                $table->string('include_monitor')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'include_notes')) {
                $table->text('include_notes')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'theme_style')) {
                $table->string('theme_style')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'preference')) {
                $table->string('preference')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'exemption')) {
                $table->string('exemption')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'future_proof')) {
                $table->boolean('future_proof')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'case_size')) {
                $table->integer('case_size')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'okay_with_aio')) {
                $table->boolean('okay_with_aio')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'gpu_sag')) {
                $table->boolean('gpu_sag')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'need_rgb')) {
                $table->boolean('need_rgb')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvcrf_tag')) {
                $table->boolean('qvcrf_tag')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvse')) {
                $table->boolean('qvse')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvca')) {
                $table->boolean('qvca')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvtd')) {
                $table->boolean('qvtd')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvtd_notes')) {
                $table->text('qvtd_notes')->nullable();
            }
        });
    }
}
