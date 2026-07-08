<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProductRawTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_raw', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('product_code');
            $table->integer('cat_id');
            $table->string('category_name')->nullable();
            $table->integer('sub_cat_id');
            $table->integer('brand_id');
            $table->string('product_name');
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
        Schema::dropIfExists('product_raw');
    }
}
