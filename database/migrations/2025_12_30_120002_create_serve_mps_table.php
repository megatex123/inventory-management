<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateServeMpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serve_mps', function (Blueprint $table) {
            $table->increments('id');
            $table->string('serve_mps_id', 50);
            $table->string('serve_data_id', 255);
            $table->date('date_start')->nullable();
            $table->boolean('two_year_assembly_warranty')->nullable()->default(true);
            $table->boolean('two_free_onsite_troubleshooting_first_6_months')->nullable()->default(true);
            $table->boolean('two_free_onsite_troubleshooting_claim_1')->nullable()->default(false);
            $table->date('two_free_onsite_troubleshooting_claim_1_date')->nullable();
            $table->boolean('two_free_onsite_troubleshooting_claim_2')->nullable()->default(false);
            $table->date('two_free_onsite_troubleshooting_claim_2_date')->nullable();
            $table->boolean('two_advance_cable_management_first_year')->nullable()->default(true);
            $table->boolean('two_advance_cable_management_claim_1')->nullable()->default(false);
            $table->date('two_advance_cable_management_claim_1_date')->nullable();
            $table->boolean('two_advance_cable_management_claim_2')->nullable()->default(false);
            $table->date('two_advance_cable_management_claim_2_date')->nullable();
            $table->boolean('one_free_dust_cleaning_first_year')->nullable()->default(true);
            $table->boolean('one_free_dust_cleaning_claim')->nullable()->default(false);
            $table->boolean('fifty_percent_off_dust_cleaning_second_year')->nullable()->default(true);
            $table->boolean('thirty_percent_off_labour_fees_upgrade_first_year')->nullable()->default(true);
            $table->string('rm100_promo_code_next_build', 100)->nullable();
            $table->boolean('generate_code')->nullable()->default(false);
            $table->boolean('rm100_promo_code_claim')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->index('serve_data_id', 'idx_qvse_cid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('serve_mps');
    }
}
