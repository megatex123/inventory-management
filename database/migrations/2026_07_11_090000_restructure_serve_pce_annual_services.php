<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RestructureServePceAnnualServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('serve_pce', function (Blueprint $table) {
            // 50% off Annual Dust Cleaning (Years 4-7)
            $table->string('dust_cleaning_50_description', 100)->nullable()->after('claim_date_year3');
            $table->tinyInteger('dust_cleaning_50_year4')->nullable()->after('dust_cleaning_50_description');
            $table->date('dust_cleaning_50_claim_date_year4')->nullable()->after('dust_cleaning_50_year4');
            $table->tinyInteger('dust_cleaning_50_year5')->nullable()->after('dust_cleaning_50_claim_date_year4');
            $table->date('dust_cleaning_50_claim_date_year5')->nullable()->after('dust_cleaning_50_year5');
            $table->tinyInteger('dust_cleaning_50_year6')->nullable()->after('dust_cleaning_50_claim_date_year5');
            $table->date('dust_cleaning_50_claim_date_year6')->nullable()->after('dust_cleaning_50_year6');
            $table->tinyInteger('dust_cleaning_50_year7')->nullable()->after('dust_cleaning_50_claim_date_year6');
            $table->date('dust_cleaning_50_claim_date_year7')->nullable()->after('dust_cleaning_50_year7');

            // 50% off Annual Upgrade Service (Years 1-3)
            $table->string('upgrade_service_50_description', 100)->nullable()->after('dust_cleaning_50_claim_date_year7');
            $table->tinyInteger('upgrade_service_50_year1')->nullable()->after('upgrade_service_50_description');
            $table->date('upgrade_service_50_claim_date_year1')->nullable()->after('upgrade_service_50_year1');
            $table->tinyInteger('upgrade_service_50_year2')->nullable()->after('upgrade_service_50_claim_date_year1');
            $table->date('upgrade_service_50_claim_date_year2')->nullable()->after('upgrade_service_50_year2');
            $table->tinyInteger('upgrade_service_50_year3')->nullable()->after('upgrade_service_50_claim_date_year2');
            $table->date('upgrade_service_50_claim_date_year3')->nullable()->after('upgrade_service_50_year3');

            // 30% off Annual Upgrade Service (Years 4-7)
            $table->string('upgrade_service_30_description', 100)->nullable()->after('upgrade_service_50_claim_date_year3');
            $table->tinyInteger('upgrade_service_30_year4')->nullable()->after('upgrade_service_30_description');
            $table->date('upgrade_service_30_claim_date_year4')->nullable()->after('upgrade_service_30_year4');
            $table->tinyInteger('upgrade_service_30_year5')->nullable()->after('upgrade_service_30_claim_date_year4');
            $table->date('upgrade_service_30_claim_date_year5')->nullable()->after('upgrade_service_30_year5');
            $table->tinyInteger('upgrade_service_30_year6')->nullable()->after('upgrade_service_30_claim_date_year5');
            $table->date('upgrade_service_30_claim_date_year6')->nullable()->after('upgrade_service_30_year6');
            $table->tinyInteger('upgrade_service_30_year7')->nullable()->after('upgrade_service_30_claim_date_year6');
            $table->date('upgrade_service_30_claim_date_year7')->nullable()->after('upgrade_service_30_year7');
        });

        // annual_dust_cleaning was a generic description field for the "Free Annual Deep
        // Cleaning" tier (years 1-3, columns unchanged). Old flat Yes/No toggles are
        // replaced by the per-year claim tracking above.
        Schema::table('serve_pce', function (Blueprint $table) {
            $table->dropColumn(['50_dust_cleaning', '50_upgrade_service', '30_upgrade_service']);
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
            $table->string('50_dust_cleaning', 255)->nullable();
            $table->string('50_upgrade_service', 255)->nullable();
            $table->string('30_upgrade_service', 255)->nullable();

            $table->dropColumn([
                'dust_cleaning_50_description',
                'dust_cleaning_50_year4', 'dust_cleaning_50_claim_date_year4',
                'dust_cleaning_50_year5', 'dust_cleaning_50_claim_date_year5',
                'dust_cleaning_50_year6', 'dust_cleaning_50_claim_date_year6',
                'dust_cleaning_50_year7', 'dust_cleaning_50_claim_date_year7',
                'upgrade_service_50_description',
                'upgrade_service_50_year1', 'upgrade_service_50_claim_date_year1',
                'upgrade_service_50_year2', 'upgrade_service_50_claim_date_year2',
                'upgrade_service_50_year3', 'upgrade_service_50_claim_date_year3',
                'upgrade_service_30_description',
                'upgrade_service_30_year4', 'upgrade_service_30_claim_date_year4',
                'upgrade_service_30_year5', 'upgrade_service_30_claim_date_year5',
                'upgrade_service_30_year6', 'upgrade_service_30_claim_date_year6',
                'upgrade_service_30_year7', 'upgrade_service_30_claim_date_year7',
            ]);
        });
    }
}
