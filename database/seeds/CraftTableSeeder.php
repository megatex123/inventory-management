<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CraftTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('craft')->truncate();

        DB::unprepared('INSERT INTO `craft` (`id`, `name`, `code`, `fee`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,\'BASIC\',\'BASIC\',\'600.00\',\'2026-01-03 21:47:46\',\'2026-03-17 23:42:03\',NULL),(2,\'PREMIUM\',\'PREMIUM\',\'600.00\',\'2026-01-03 21:48:07\',\'2026-03-17 23:42:27\',NULL),(3,\'MEDIUM\',\'MEDIUM\',\'600.00\',\'2026-01-03 21:48:29\',\'2026-03-17 23:42:16\',NULL),(4,\'ULTRA\',\'ULTRA\',\'600.00\',\'2026-03-17 23:42:46\',\'2026-03-17 23:42:46\',NULL);');
    }
}
