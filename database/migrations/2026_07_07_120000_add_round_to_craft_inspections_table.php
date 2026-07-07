<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoundToCraftInspectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('craft_inspections', function (Blueprint $table) {
            $table->unsignedTinyInteger('round')->default(1)->after('phase');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('craft_inspections', function (Blueprint $table) {
            $table->dropColumn('round');
        });
    }
}
