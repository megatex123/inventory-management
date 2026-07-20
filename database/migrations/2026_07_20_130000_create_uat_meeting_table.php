<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateUatMeetingTable extends Migration {
    public function up()
    {
        Schema::create('uat_meeting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')
                  ->constrained('meetings')
                  ->onDelete('cascade');
            $table->decimal('initial_budget', 10, 2)->nullable();
            $table->tinyInteger('reason')->comment('1: Work, 2: Gaming');
            $table->tinyInteger('play_mode')->nullable()->comment('1: Multiplayer, 2: Singleplayer');
            $table->boolean('include_monitor')->nullable();
            $table->text('include_notes')->nullable();
            $table->text('notes')->nullable();
            $table->string('theme_style')->nullable();
            $table->string('preference')->nullable();
            $table->string('exemption')->nullable();
            $table->boolean('future_proof')->nullable()->default(false);
            $table->boolean('case_size')->nullable();
            $table->boolean('okay_with_aio')->nullable()->default(false);
            $table->boolean('gpu_sag')->nullable();
            $table->boolean('need_rgb')->nullable()->default(false);
            $table->boolean('qvcrf_tag')->nullable()->default(false);
            $table->boolean('qvse')->nullable()->default(false);
            $table->boolean('qvca')->nullable()->default(false);
            $table->boolean('qvtd')->nullable()->default(false);
            $table->text('qvtd_notes')->nullable();
            $table->datetime('target_build_date')->nullable();
            $table->string('target_location')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('uat_meeting');
    }
};
