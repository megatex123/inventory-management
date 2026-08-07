<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBuildWayAndTagAlongToOrderTable extends Migration
{
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->string('build_way')->nullable()->after('is_reason');
            $table->boolean('tag_along')->nullable()->after('build_way');
        });
    }

    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn(['build_way', 'tag_along']);
        });
    }
}
