<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeProductsSubCatIdAndBrandIdNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * Neither column is actually populated by the app today: there is no
     * sub-category picker anywhere in the product create/edit forms, and
     * brand_id is optional by design (the Brand <select> added 2026-08-03
     * has a deliberate "-- Select Brand --" null option). Both were
     * NOT NULL with no default in the live schema, which meant every
     * POST /api/product would fail on this constraint even after removing
     * the unrelated capacity/form/interface/read/write/tier phantom-column
     * writes -- see the "Known bug" entry in docs/QuiviTech/Work-In-Progress.md.
     *
     * Raw SQL, not Schema::table()->change() -- this app's Laravel
     * 7/Carbon 2.73 combo is incompatible with any doctrine/dbal version
     * (see docs/QuiviTech/Dev-Setup.md's "Doctrine DBAL is broken" note),
     * so ->change() must never be used in new migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `products` MODIFY `sub_cat_id` INT(11) NULL');
        DB::statement('ALTER TABLE `products` MODIFY `brand_id` INT(11) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `products` MODIFY `sub_cat_id` INT(11) NOT NULL');
        DB::statement('ALTER TABLE `products` MODIFY `brand_id` INT(11) NOT NULL');
    }
}
