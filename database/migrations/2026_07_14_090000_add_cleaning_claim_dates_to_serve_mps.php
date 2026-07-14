<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCleaningClaimDatesToServeMps extends Migration
{
    public function up(): void
    {
        Schema::table('serve_mps', function (Blueprint $table) {
            $table->date('one_free_dust_cleaning_claim_date')->nullable()->after('one_free_dust_cleaning_claim');
            $table->date('fifty_percent_off_dust_cleaning_claim_date')->nullable()->after('fifty_percent_off_dust_cleaning_second_year');
            $table->date('thirty_percent_off_labour_fees_claim_date')->nullable()->after('thirty_percent_off_labour_fees_upgrade_first_year');
        });
    }

    public function down(): void
    {
        Schema::table('serve_mps', function (Blueprint $table) {
            $table->dropColumn([
                'one_free_dust_cleaning_claim_date',
                'fifty_percent_off_dust_cleaning_claim_date',
                'thirty_percent_off_labour_fees_claim_date',
            ]);
        });
    }
};
