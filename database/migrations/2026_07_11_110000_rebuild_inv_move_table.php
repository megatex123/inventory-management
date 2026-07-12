<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RebuildInvMoveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Original inv_move (2026-06-20) was scaffolded but never wired to a
        // controller/UI: it had no timestamps/soft-delete columns despite the
        // InvMove model using SoftDeletes, no business-facing movement_id, and
        // reference_id was typed as bigInteger even though real reference
        // codes (e.g. "QVT_ORD 0001") aren't numeric. 0 rows in production,
        // so rebuilding cleanly rather than altering in place.
        Schema::dropIfExists('inv_move');

        Schema::create('inv_move', function (Blueprint $table) {
            $table->id();
            $table->string('movement_id', 50)->unique();
            $table->dateTime('date');
            $table->unsignedInteger('master_sku_id');
            $table->unsignedInteger('destination_id');
            $table->string('item_name', 191)->nullable();
            $table->string('type', 50)->default('Inventory');
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->string('reference_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->index('master_sku_id');
            $table->index('destination_id');
        });

        // The CSV import references destination codes I_QVMR and IE_QVMR
        // that don't exist yet alongside the existing IE_QVSE/I_QVTD rows.
        foreach (['I_QVMR', 'IE_QVMR'] as $code) {
            if (!DB::table('destination')->where('description', $code)->exists()) {
                DB::table('destination')->insert([
                    'description' => $code,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inv_move');

        Schema::create('inv_move', function (Blueprint $table) {
            $table->increments('id');
            $table->string('item_name', 50)->nullable();
            $table->dateTime('date');
            $table->bigInteger('master_sku_id')->nullable();
            $table->bigInteger('destination_id')->nullable();
            $table->bigInteger('reference_id')->nullable();
            $table->string('unit_cost', 50)->nullable();
            $table->bigInteger('quantity')->nullable();
            $table->string('type', 50)->nullable();
        });
    }
}
