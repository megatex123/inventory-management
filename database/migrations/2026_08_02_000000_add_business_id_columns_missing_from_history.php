<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills migration history for columns already present on the live `quivi` DB
 * but never recorded in any migration file (added directly, out of band, during
 * this session's business-ID-prefix-rename / draft-history / UAT-meeting work):
 * order.craft_tag_id, order.reject_id, order.craft_data_id, uat_meeting.uat_id,
 * meeting_details.requirement_id, meeting_details.theme_style.
 */
class AddBusinessIdColumnsMissingFromHistory extends Migration
{
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            if (!Schema::hasColumn('order', 'craft_tag_id')) {
                $table->string('craft_tag_id')->nullable();
            }
            if (!Schema::hasColumn('order', 'reject_id')) {
                $table->string('reject_id')->nullable();
            }
            if (!Schema::hasColumn('order', 'craft_data_id')) {
                $table->string('craft_data_id')->nullable();
            }
        });

        Schema::table('uat_meeting', function (Blueprint $table) {
            if (!Schema::hasColumn('uat_meeting', 'uat_id')) {
                $table->string('uat_id')->nullable();
            }
        });

        Schema::table('meeting_details', function (Blueprint $table) {
            if (!Schema::hasColumn('meeting_details', 'requirement_id')) {
                $table->string('requirement_id')->nullable();
            }
            if (!Schema::hasColumn('meeting_details', 'theme_style')) {
                $table->string('theme_style')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn(['craft_tag_id', 'reject_id', 'craft_data_id']);
        });

        Schema::table('uat_meeting', function (Blueprint $table) {
            $table->dropColumn('uat_id');
        });

        Schema::table('meeting_details', function (Blueprint $table) {
            $table->dropColumn(['requirement_id', 'theme_style']);
        });
    }
}
