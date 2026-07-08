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

        DB::unprepared('INSERT INTO `destination` (`id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,\'IE_QVSE\',1,\'2026-07-02 04:06:14\',\'2026-07-02 04:06:14\',NULL),(2,\'I_QVTD\',1,\'2026-07-02 04:07:01\',\'2026-07-02 04:07:01\',NULL);');
    }
}
