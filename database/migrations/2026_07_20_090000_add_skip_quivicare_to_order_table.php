<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkipQuivicareToOrderTable extends Migration
{
    public function up(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->boolean('skip_quivicare')->default(0)->after('care_id');
        });
    }

    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn('skip_quivicare');
        });
    }
};
