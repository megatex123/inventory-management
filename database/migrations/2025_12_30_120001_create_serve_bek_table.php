<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateServeBekTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serve_bek', function (Blueprint $table) {
            $table->increments('id');
            $table->string('serve_bek_id', 50);
            $table->string('serve_data_id', 255);
            $table->date('date_start')->nullable();
            $table->boolean('one_year_assembly_warranty')->nullable()->default(true);
            $table->boolean('one_free_onsite_troubleshooting_first_3_months')->nullable()->default(true);
            $table->boolean('one_free_onsite_troubleshooting_claim_1')->nullable()->default(false);
            $table->date('one_free_onsite_troubleshooting_claim_1_date')->nullable();
            $table->boolean('one_basic_cable_management_3_months')->nullable()->default(true);
            $table->boolean('one_basic_cable_management_claim_1')->nullable()->default(false);
            $table->date('one_basic_cable_management_claim_1_date')->nullable();
            $table->boolean('fifty_percent_off_dust_cleaning_first_year')->nullable()->default(true);
            $table->boolean('fifty_percent_off_dust_cleaning_claim_1')->nullable();
            $table->date('fifty_percent_off_dust_cleaning_claim_1_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->index('serve_data_id', 'idx_serve_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('serve_bek');
    }
}
