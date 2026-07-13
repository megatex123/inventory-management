<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Column set below (component specs, brand/sub-category, price history,
     * default image) reflects the live schema, which drifted out from under
     * this migration's original SSD-specific placeholder columns
     * (capacity/form/interface/read/write/tier) without ever being recorded
     * as a migration.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('is_care')->nullable();
            $table->string('product_code');
            $table->integer('cat_id');
            $table->string('category_name')->nullable();
            $table->integer('sub_cat_id');
            $table->integer('brand_id');
            $table->string('product_name');
            $table->integer('core')->default(0);
            $table->integer('threads')->nullable();
            $table->string('max_usage', 255)->nullable();
            $table->string('type', 255)->nullable();
            $table->integer('include_fans')->nullable();
            $table->integer('frequency')->nullable();
            $table->string('support')->nullable();
            $table->string('latency')->nullable();
            $table->string('additional')->nullable();
            $table->string('vram')->nullable();
            $table->string('80_plus')->nullable();
            $table->string('atx')->nullable();
            $table->string('gen')->nullable();
            $table->string('pcie')->nullable();
            $table->string('storage')->nullable();
            $table->string('size', 255)->nullable();
            $table->string('colour', 255)->nullable();
            $table->integer('back_connect')->nullable();
            $table->string('price')->default('0.00');
            $table->timestamp('price_updated_at')->useCurrent();
            $table->string('available')->nullable();
            $table->string('available_local')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->string('buying_date')->nullable();
            $table->string('image')->default('/backend/products/1767110319.png');
            $table->integer('product_qty')->nullable();
            $table->integer('product_loan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
