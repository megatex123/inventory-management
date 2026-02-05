<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->integer('cat_id');
            $table->string('product_name');
            $table->string('capacity')->nullable();
            $table->string('form')->nullable();
            $table->string('interface')->nullable();
            $table->string('read')->nullable();
            $table->string('write')->nullable();
            $table->string('tier')->nullable();
            $table->string('price')->nullable();
            $table->datetime('price_updated_at')->nullable();
            $table->string('available')->nullable();
            $table->string('available_local')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->string('buying_date')->nullable();
            $table->string('image')->nullable();
            $table->string('product_qty')->nullable();
            $table->timestamps();
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
