<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixMbdCategoryCodeAndAccessoryCatIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // categories.id=6 (Motherboard, name="MBD") had a typo in its code
        // column: "PART-MDB" instead of "PART-MBD". This column drives
        // product-code generation (see ProductsController::store()), so the
        // typo would otherwise propagate into every future Motherboard
        // product code.
        DB::table('categories')
            ->where('id', 6)
            ->update(['code' => 'PART-MBD']);

        // 3 live Accessories products (ids 40/41/42) were all seeded with
        // cat_id=12 (ACC-SAG) despite products 41/42's own product_code
        // values clearly indicating ACC-CTL/ACC-HUB respectively. Product 40
        // (SAG) already has the correct cat_id and is left untouched.
        DB::table('products')
            ->where('id', 41)
            ->update(['cat_id' => 13]); // ACC-CTL

        DB::table('products')
            ->where('id', 42)
            ->update(['cat_id' => 14]); // ACC-HUB
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('categories')
            ->where('id', 6)
            ->update(['code' => 'PART-MDB']);

        DB::table('products')
            ->where('id', 41)
            ->update(['cat_id' => 12]);

        DB::table('products')
            ->where('id', 42)
            ->update(['cat_id' => 12]);
    }
}
