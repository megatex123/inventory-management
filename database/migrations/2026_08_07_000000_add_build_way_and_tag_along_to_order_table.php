<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBuildWayAndTagAlongToOrderTable extends Migration
{
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            if (!Schema::hasColumn('order', 'build_way')) {
                $table->string('build_way')->nullable()->after('is_reason');
            }
            if (!Schema::hasColumn('order', 'tag_along')) {
                $table->boolean('tag_along')->nullable()->after('build_way');
            }
        });
    }

    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            if (Schema::hasColumn('order', 'build_way')) {
                $table->dropColumn('build_way');
            }
            if (Schema::hasColumn('order', 'tag_along')) {
                $table->dropColumn('tag_along');
            }
        });
    }
}
