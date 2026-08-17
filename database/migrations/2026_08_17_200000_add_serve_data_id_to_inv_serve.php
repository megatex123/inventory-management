<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * The original inv_excl_serve table (dropped 2026-08-02, rebuilt here as
 * inv_serve without this) had a serve_data_id column linking each stock
 * item to a specific QuiviServe record. Nullable here (unlike the
 * original's NOT NULL) -- these are general packaging/perk stock, not
 * tied to a customer's order until actually handed out, and the 7 rows
 * already live in inv_serve have no serve_data_id to backfill.
 *
 * Uses a raw ALTER TABLE rather than Schema::table()->addColumn(): the
 * Blueprint form (unsignedBigInteger()->nullable()->after()) was
 * confirmed live to silently produce int(11) NOT NULL instead of the
 * requested bigint unsigned nullable on this MariaDB version -- raw SQL
 * sidesteps whatever grammar quirk caused that.
 */
class AddServeDataIdToInvServe extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('inv_serve', 'serve_data_id')) {
            DB::statement('ALTER TABLE inv_serve ADD COLUMN serve_data_id BIGINT UNSIGNED NULL AFTER sku_code');
            return;
        }

        // Column already exists (e.g. from an earlier, buggy run of this
        // same migration) -- normalize it to the correct type/nullability
        // instead of skipping.
        $column = DB::selectOne("SHOW COLUMNS FROM inv_serve WHERE Field = 'serve_data_id'");
        if ($column && ($column->Null !== 'YES' || stripos($column->Type, 'bigint') === false)) {
            DB::statement('ALTER TABLE inv_serve MODIFY serve_data_id BIGINT UNSIGNED NULL');
            DB::table('inv_serve')->where('serve_data_id', 0)->update(['serve_data_id' => null]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('inv_serve', 'serve_data_id')) {
            DB::statement('ALTER TABLE inv_serve DROP COLUMN serve_data_id');
        }
    }
}
