<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CareTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('care')->truncate();

        DB::unprepared('INSERT INTO `care` (`id`, `name`, `code`, `fee`, `period`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,\'COR3\',\'COR3-1402\',\'1479.00\',\'3 years\',\'2025-12-30 05:16:42\',\'2026-01-22 01:01:40\',NULL),(2,\'RI5E\',\'RI5E-2109\',\'1499.00\',\'5 years\',\'2025-12-30 05:16:42\',\'2026-01-21 06:54:59\',NULL),(3,\'VIS10N\',\'VIS10N-2712\',\'2499.00\',\'10 years\',\'2025-12-30 05:16:42\',\'2026-01-21 06:55:15\',NULL);');
    }
}
