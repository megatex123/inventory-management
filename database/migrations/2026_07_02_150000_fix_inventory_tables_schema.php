<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixInventoryTablesSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // These tables have zero-date defaults on their timestamp columns (created outside
        // migrations). Under strict sql_mode, ALTER TABLE revalidates every column's default,
        // so zero-date defaults must be tolerated for the duration of this migration.
        $originalSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode as mode')->mode;
        DB::statement("SET SESSION sql_mode = ''");

        try {
            Schema::table('master_sku', function (Blueprint $table) {
                $table->unsignedBigInteger('product_raw_id')->nullable()->change();
            });

            Schema::table('inv_care', function (Blueprint $table) {
                $table->datetime('deleted_at')->nullable()->default(null)->change();
            });

            Schema::table('inv_excl_serve', function (Blueprint $table) {
                $table->datetime('deleted_at')->nullable()->default(null)->change();
            });

            // Zero-date deleted_at rows (from the NOT NULL default) are not "deleted" in Laravel's
            // soft-delete sense; normalize them to NULL now that the column accepts it.
            DB::table('inv_care')->where('deleted_at', '0000-00-00 00:00:00')->update(['deleted_at' => null]);
            DB::table('inv_excl_serve')->where('deleted_at', '0000-00-00 00:00:00')->update(['deleted_at' => null]);
        } finally {
            DB::statement("SET SESSION sql_mode = '{$originalSqlMode}'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('master_sku', function (Blueprint $table) {
            $table->unsignedBigInteger('product_raw_id')->nullable(false)->change();
        });

        Schema::table('inv_care', function (Blueprint $table) {
            $table->datetime('deleted_at')->nullable(false)->change();
        });

        Schema::table('inv_excl_serve', function (Blueprint $table) {
            $table->datetime('deleted_at')->nullable(false)->change();
        });
    }
}
