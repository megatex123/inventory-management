<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateServeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serve_data', function (Blueprint $table) {
            $table->id();
            $table->string('serve_id', 50)->unique();
            $table->string('qvse_cid', 100)->nullable()->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('lkp_serve_id');
            $table->boolean('start_serve_enabled')->nullable()->default(false);
            $table->dateTime('start_serve_date')->nullable();
            $table->bigInteger('start_serve_timestamp')->nullable();
            $table->boolean('upgrade_pce_enabled')->nullable()->default(false);
            $table->text('upgrade_pce_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->index('start_serve_enabled', 'idx_start_serve_enabled');
            $table->index('upgrade_pce_enabled', 'idx_upgrade_pce_enabled');
            $table->index('start_serve_date', 'idx_start_serve_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('serve_data');
    }
}
