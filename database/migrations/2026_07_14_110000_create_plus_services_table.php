<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlusServicesTable extends Migration
{
    public function up()
    {
        Schema::create('plus_services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code', 50)->unique();
            $table->string('name', 191);
            $table->string('category', 30);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plus_services');
    }
}
