<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuItemsTable extends Migration
{
    public function up()
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
            // link = clickable route, group = top-level collapsible, header = h6 section label
            $table->enum('type', ['link', 'group', 'header']);
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('route')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('divider_before')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_items');
    }
}
