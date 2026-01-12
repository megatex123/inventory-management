<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingDetailsTable extends Migration {
    public function up()
    {
        Schema::create('meeting_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')
                  ->constrained('meetings')
                  ->onDelete('cascade');
            $table->decimal('initial_budget', 10, 2)->nullable();
            $table->string('reason')->nullable();
            $table->enum('play_mode', ['Singleplayer', 'Multiplayer'])->nullable();
            $table->string('include_monitor')->nullable();
            $table->text('notes')->nullable();
            $table->string('theme_style')->nullable();
            $table->string('preference')->nullable();
            $table->string('exemption')->nullable();
            $table->boolean('future_proof')->default(false);
            $table->string('case_size')->nullable();
            $table->boolean('okay_with_aio')->default(false);
            $table->boolean('need_rgb')->default(false);
            $table->boolean('gpu_sag')->nullable();
            $table->boolean('qvcrf_tag')->default(false);
            $table->boolean('qvse')->default(false);
            $table->boolean('qvca')->default(false);
            $table->boolean('qvtd')->default(false);
            $table->datetime('target_build_date')->nullable();
            $table->string('target_location')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_details');
    }
};
