<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InvMoveReplaceReferenceWithOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inv_move', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('destination_id');
            $table->index('order_id');
        });

        // The one movement that already had a resolvable reference ("QVT_ORD
        // 0059") maps to the real order QV-ORDR-0007 — link it before the old
        // free-text column is dropped. The other imported rows' external PO
        // codes don't correspond to any order in this system, so they're left
        // unlinked rather than guessed at.
        $orderId = DB::table('order')->where('order_id', 'QV-ORDR-0007')->value('id');
        if ($orderId) {
            DB::table('inv_move')->where('movement_id', 'MVMT-0057')->update(['order_id' => $orderId]);
        }

        Schema::table('inv_move', function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inv_move', function (Blueprint $table) {
            $table->string('reference_id', 100)->nullable()->after('destination_id');
        });

        Schema::table('inv_move', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
    }
}
