<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCareDataIdToCareDataTable extends Migration
{
    public function up()
    {
        Schema::table('care_data', function (Blueprint $table) {
            $table->string('care_data_id')->nullable()->after('care_id');
        });
    }

    public function down()
    {
        Schema::table('care_data', function (Blueprint $table) {
            $table->dropColumn('care_data_id');
        });
    }
}
