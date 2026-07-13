<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DestinationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('destination')->truncate();

        // I_QVMR/IE_QVMR are also inserted conditionally by the
        // 2026_07_11_110000_rebuild_inv_move_table migration (for the
        // Inventory Movement CSV import); included here too so this seeder
        // alone reproduces all 4 live rows even if run standalone, and so it
        // doesn't truncate away rows the migration already inserted.
        DB::unprepared('INSERT INTO `destination` (`id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,\'IE_QVSE\',1,\'2026-07-02 04:06:14\',\'2026-07-02 04:06:14\',NULL),(2,\'I_QVTD\',1,\'2026-07-02 04:07:01\',\'2026-07-02 04:07:01\',NULL),(3,\'I_QVMR\',1,\'2026-07-11 07:12:49\',\'2026-07-11 07:12:49\',NULL),(4,\'IE_QVMR\',1,\'2026-07-11 07:12:49\',\'2026-07-11 07:12:49\',NULL);');
    }
}
