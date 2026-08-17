<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * inv_serve (created 2026-08-16) was a mistaken duplicate: the table it
 * was meant to replace, inv_excl_serve, was never actually dropped on
 * production despite migration 2026_08_02_010000_remove_inv_excl_serve
 * showing as run in some environments -- production's inv_excl_serve is
 * alive with 7 real rows matching the exact CSV that prompted building
 * inv_serve in the first place. This migration:
 *   1. Creates inv_excl_serve if it doesn't exist (e.g. this local dev
 *      DB, where the 2026-08-02 removal genuinely took effect), matching
 *      the original schema but with unit_cost as DECIMAL (was a lossy
 *      integer -- see 2026_08_08_100000_fix_inv_care_column_constraints
 *      for the same fix on inv_care) and serve_data_id nullable (was
 *      NOT NULL -- these are general stock, not tied to an order until
 *      handed out).
 *   2. If inv_excl_serve already exists (production), normalizes those
 *      same two columns without touching existing data otherwise.
 *   3. Drops inv_serve, the mistaken duplicate, and its data.
 *
 * Uses raw SQL throughout, consistent with this project's established
 * pattern for anything beyond a plain addColumn (see
 * docs/QuiviTech/Dev-Setup.md's Doctrine DBAL gotcha, and the
 * unsignedBigInteger()->nullable() grammar quirk found earlier this
 * session while adding serve_data_id to the now-removed inv_serve).
 */
class RestoreInvExclServeDropInvServe extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inv_excl_serve')) {
            DB::statement("
                CREATE TABLE inv_excl_serve (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    inv_excl_serve VARCHAR(255) NOT NULL,
                    serve_data_id BIGINT UNSIGNED NULL,
                    sku_code VARCHAR(100) NOT NULL,
                    item_name VARCHAR(100) NOT NULL,
                    unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
                    max_stock INT NOT NULL DEFAULT 0,
                    current_stock INT NOT NULL DEFAULT 0,
                    to_restock INT NOT NULL DEFAULT 0,
                    status INT NOT NULL DEFAULT 1,
                    generate_id INT NOT NULL DEFAULT 0,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL
                )
            ");
        } else {
            $unitCost = DB::selectOne("SHOW COLUMNS FROM inv_excl_serve WHERE Field = 'unit_cost'");
            if ($unitCost && stripos($unitCost->Type, 'decimal') === false) {
                DB::statement('ALTER TABLE inv_excl_serve MODIFY unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0');
            }

            $serveDataId = DB::selectOne("SHOW COLUMNS FROM inv_excl_serve WHERE Field = 'serve_data_id'");
            if ($serveDataId && ($serveDataId->Null !== 'YES' || stripos($serveDataId->Type, 'bigint') === false)) {
                DB::statement('ALTER TABLE inv_excl_serve MODIFY serve_data_id BIGINT UNSIGNED NULL');
                DB::table('inv_excl_serve')->where('serve_data_id', 0)->update(['serve_data_id' => null]);
            }
        }

        Schema::dropIfExists('inv_serve');
    }

    public function down()
    {
        // Not reversed -- inv_serve was a mistake, not a feature to
        // restore, and inv_excl_serve predates this migration entirely
        // (it's the real, original table).
    }
}
