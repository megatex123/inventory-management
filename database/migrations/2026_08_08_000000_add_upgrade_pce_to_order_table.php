<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpgradePceToOrderTable extends Migration
{
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            if (!Schema::hasColumn('order', 'upgrade_pce_enabled')) {
                $table->boolean('upgrade_pce_enabled')->nullable()->after('tag_along');
            }
            if (!Schema::hasColumn('order', 'upgrade_pce_notes')) {
                $table->text('upgrade_pce_notes')->nullable()->after('upgrade_pce_enabled');
            }
        });
    }

    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            if (Schema::hasColumn('order', 'upgrade_pce_enabled')) {
                $table->dropColumn('upgrade_pce_enabled');
            }
            if (Schema::hasColumn('order', 'upgrade_pce_notes')) {
                $table->dropColumn('upgrade_pce_notes');
            }
        });
    }
}
