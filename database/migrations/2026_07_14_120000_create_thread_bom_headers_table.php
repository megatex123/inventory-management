<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadBomHeadersTable extends Migration
{
    public function up()
    {
        Schema::create('thread_bom_headers', function (Blueprint $table) {
            $table->id();
            // psu_brand is a plain string, not a FK to the `brand` table — that
            // table is scoped to PC-part brands (a different concern) and this
            // is a small, fixed set (Asus/Corsair/SeaSonic/...).
            $table->string('psu_brand', 100);
            $table->string('cable_type', 30);
            $table->string('colour_variant', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['psu_brand', 'cable_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('thread_bom_headers');
    }
}
