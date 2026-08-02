<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveInvExclServe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // The feature had 0 live rows at removal time -- confirmed via
        // App\Models\InvExclServe::count() before this migration was written.
        Schema::dropIfExists('inv_excl_serve');

        // Menu group 98 ("QS Excl. Inventory") and its two links (99/100)
        // under the Inventory sidebar menu.
        DB::table('menu_items')->whereIn('id', [98, 99, 100])->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Table structure intentionally not restored -- the feature is
        // removed, not paused. Restore from
        // database/migrations/2026_06_20_000003_create_inv_excl_serve_table.php
        // if this ever needs to be reinstated.
    }
}
