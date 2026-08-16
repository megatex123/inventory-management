<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QuiviCare Inventory moves from a stock-count model (current_stock/
 * max_stock) to one row per serialized physical unit -- each item now
 * carries its own serial_number and a status (Active/.../Occupied)
 * instead of a quantity. Occupied means that specific unit has already
 * been used as a warranty replacement.
 */
class RedesignInvCareToSerializedUnits extends Migration
{
    public function up()
    {
        Schema::table('inv_care', function (Blueprint $table) {
            if (Schema::hasColumn('inv_care', 'current_stock')) {
                $table->dropColumn('current_stock');
            }
            if (Schema::hasColumn('inv_care', 'max_stock')) {
                $table->dropColumn('max_stock');
            }
            // serial_label (int, nullable) was added speculatively earlier
            // and never wired up anywhere in the app -- replaced here with
            // a proper string serial_number rather than kept alongside it.
            if (Schema::hasColumn('inv_care', 'serial_label')) {
                $table->dropColumn('serial_label');
            }
            if (!Schema::hasColumn('inv_care', 'serial_number')) {
                $table->string('serial_number')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('inv_care', function (Blueprint $table) {
            if (Schema::hasColumn('inv_care', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
            if (!Schema::hasColumn('inv_care', 'serial_label')) {
                $table->integer('serial_label')->nullable();
            }
            if (!Schema::hasColumn('inv_care', 'max_stock')) {
                $table->integer('max_stock')->default(0);
            }
            if (!Schema::hasColumn('inv_care', 'current_stock')) {
                $table->integer('current_stock')->default(0);
            }
        });
    }
}
