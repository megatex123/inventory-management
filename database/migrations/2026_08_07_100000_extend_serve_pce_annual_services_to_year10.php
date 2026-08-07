<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendServePceAnnualServicesToYear10 extends Migration
{
    /**
     * Collector's Edition's documented perk is "3 years unlimited
     * troubleshooting, next 7 years = 50% off" -- i.e. years 4-10, not
     * 4-7. The 2026-07-11 restructure only added years 4-7 for both the
     * 50% Dust Cleaning and 30% Upgrade Service series; this extends both
     * to the full 7-year window (years 8-10 added here).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('serve_pce', function (Blueprint $table) {
            $table->tinyInteger('dust_cleaning_50_year8')->nullable()->after('dust_cleaning_50_claim_date_year7');
            $table->date('dust_cleaning_50_claim_date_year8')->nullable()->after('dust_cleaning_50_year8');
            $table->tinyInteger('dust_cleaning_50_year9')->nullable()->after('dust_cleaning_50_claim_date_year8');
            $table->date('dust_cleaning_50_claim_date_year9')->nullable()->after('dust_cleaning_50_year9');
            $table->tinyInteger('dust_cleaning_50_year10')->nullable()->after('dust_cleaning_50_claim_date_year9');
            $table->date('dust_cleaning_50_claim_date_year10')->nullable()->after('dust_cleaning_50_year10');

            $table->tinyInteger('upgrade_service_30_year8')->nullable()->after('upgrade_service_30_claim_date_year7');
            $table->date('upgrade_service_30_claim_date_year8')->nullable()->after('upgrade_service_30_year8');
            $table->tinyInteger('upgrade_service_30_year9')->nullable()->after('upgrade_service_30_claim_date_year8');
            $table->date('upgrade_service_30_claim_date_year9')->nullable()->after('upgrade_service_30_year9');
            $table->tinyInteger('upgrade_service_30_year10')->nullable()->after('upgrade_service_30_year9');
            $table->date('upgrade_service_30_claim_date_year10')->nullable()->after('upgrade_service_30_year10');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('serve_pce', function (Blueprint $table) {
            $table->dropColumn([
                'dust_cleaning_50_year8', 'dust_cleaning_50_claim_date_year8',
                'dust_cleaning_50_year9', 'dust_cleaning_50_claim_date_year9',
                'dust_cleaning_50_year10', 'dust_cleaning_50_claim_date_year10',
                'upgrade_service_30_year8', 'upgrade_service_30_claim_date_year8',
                'upgrade_service_30_year9', 'upgrade_service_30_claim_date_year9',
                'upgrade_service_30_year10', 'upgrade_service_30_claim_date_year10',
            ]);
        });
    }
}
