<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateServePceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serve_pce', function (Blueprint $table) {
            $table->id();
            $table->string('serve_pce_id', 50);
            $table->unsignedBigInteger('serve_data_id')->nullable();
            $table->date('date_start')->nullable();
            $table->string('three_year_warranty', 255)->nullable();
            $table->string('unlimited_troubleshooting', 255)->nullable();
            $table->string('troubleshooting', 255)->nullable();
            $table->string('cable_management', 50)->nullable();
            $table->tinyInteger('cable_management_claim1')->nullable();
            $table->date('cable_management_claim1_date')->nullable();
            $table->tinyInteger('cable_management_claim2')->nullable();
            $table->date('cable_management_claim2_date')->nullable();
            $table->tinyInteger('cable_management_claim3')->nullable();
            $table->date('cable_management_claim3_date')->nullable();
            $table->tinyInteger('cable_management_claim4')->nullable();
            $table->date('cable_management_claim4_date')->nullable();
            $table->string('annual_dust_cleaning', 100)->nullable();
            $table->tinyInteger('annual_dust_cleaning_year1')->nullable();
            $table->date('claim_date_year1')->nullable();
            $table->tinyInteger('annual_dust_cleaning_year2')->nullable();
            $table->date('claim_date_year2')->nullable();
            $table->tinyInteger('annual_dust_cleaning_year3')->nullable();
            $table->date('claim_date_year3')->nullable();
            $table->string('50_dust_cleaning', 255)->nullable();
            $table->string('50_upgrade_service', 255)->nullable();
            $table->string('30_upgrade_service', 255)->nullable();
            $table->string('promo_code', 50)->nullable();
            $table->tinyInteger('generate_code')->nullable();
            $table->boolean('promo_claim')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('serve_pce');
    }
}
