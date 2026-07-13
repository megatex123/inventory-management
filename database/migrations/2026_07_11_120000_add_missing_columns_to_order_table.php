<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMissingColumnsToOrderTable extends Migration
{
    /**
     * order_id, invoice_id, approve, approved_at and is_reason were added to
     * the live `order` table outside of any migration (no migration file
     * ever created them), and craft_id/serve_id/care_id/order_date drifted
     * from their original create_order_table definitions the same way. This
     * backfills the migration history to match live so a fresh install ends
     * up with the schema the app actually relies on (order_id in particular
     * is read/written throughout OrderController).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            if (!Schema::hasColumn('order', 'order_id')) {
                $table->string('order_id', 191)->after('id');
            }
            if (!Schema::hasColumn('order', 'invoice_id')) {
                $table->string('invoice_id', 191)->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('order', 'approve')) {
                $table->boolean('approve')->nullable()->after('care_id');
            }
            if (!Schema::hasColumn('order', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approve');
            }
            if (!Schema::hasColumn('order', 'is_reason')) {
                $table->tinyInteger('is_reason')->comment('1: Work, 2: Gaming');
            }
        });

        // order_date was originally a nullable string; live storage is DATETIME.
        // craft_id/serve_id/care_id were originally NOT NULL; live is nullable
        // (orders can be created before those add-ons are decided). Raw SQL
        // instead of ->change() — requires doctrine/dbal, which is not
        // installed in this project.
        DB::statement("ALTER TABLE `order` MODIFY `order_date` DATETIME NULL DEFAULT NULL");
        DB::statement("ALTER TABLE `order` MODIFY `craft_id` INT NULL DEFAULT NULL");
        DB::statement("ALTER TABLE `order` MODIFY `serve_id` INT NULL DEFAULT NULL");
        DB::statement("ALTER TABLE `order` MODIFY `care_id` INT NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'invoice_id', 'approve', 'approved_at', 'is_reason']);
        });

        DB::statement("ALTER TABLE `order` MODIFY `order_date` VARCHAR(191) NULL DEFAULT NULL");
        DB::statement("ALTER TABLE `order` MODIFY `craft_id` INT NOT NULL");
        DB::statement("ALTER TABLE `order` MODIFY `serve_id` INT NOT NULL");
        DB::statement("ALTER TABLE `order` MODIFY `care_id` INT NOT NULL");
    }
}
