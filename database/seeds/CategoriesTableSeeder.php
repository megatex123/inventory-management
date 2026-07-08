<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categories')->truncate();

        DB::unprepared('INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,\'CPU\',\'QV-PROD-CPU\',\'2025-12-30 02:49:27\',\'2026-02-05 08:01:28\',NULL),(2,\'SSD\',\'QV-PROD-SSD\',\'2026-01-03 21:49:40\',\'2026-02-05 08:03:10\',NULL),(3,\'GPU\',\'QV-PROD-GPU\',\'2026-01-03 21:49:40\',\'2026-02-05 08:01:38\',NULL),(4,\'HDD\',\'QV-PROD-HDD\',\'2026-01-03 21:49:40\',\'2026-02-05 08:03:10\',NULL),(5,\'RAM\',\'QV-PROD-RAM\',\'2026-01-10 08:49:34\',\'2026-02-05 08:01:03\',NULL),(6,\'MBD\',\'QV-PROD-MDB\',\'2026-01-10 09:52:37\',\'2026-02-05 08:01:17\',NULL),(7,\'PSU\',\'QV-PROD-PSU\',\'2026-02-05 08:02:47\',\'2026-02-05 08:02:47\',NULL),(8,\'HSF\',\'QV-PROD-HSF\',\'2026-02-05 08:03:39\',\'2026-02-05 08:03:39\',NULL),(9,\'CSE\',\'QV-PROD-CSE\',\'2026-02-05 08:03:58\',\'2026-02-11 07:38:52\',NULL),(10,\'FAN\',\'QV-PROD-FAN\',\'2026-02-05 08:03:58\',\'2026-02-11 07:38:52\',NULL),(11,\'AIO\',\'QV-PROD-AIO\',\'2026-02-05 08:03:39\',\'2026-02-05 08:03:39\',NULL),(12,\'ACC-SAG\',\'QV-PROD-ACC-SAG\',\'2026-02-05 08:03:58\',\'2026-02-11 07:38:52\',NULL),(13,\'ACC-CTL \',\'QV-PROD-ACC-CTL\',\'2025-12-30 02:27:06\',\'2026-02-28 07:02:48\',NULL),(14,\'ACC-HUB\',\'QV-PROD-ACC-HUB\',\'2025-12-30 02:59:49\',\'2026-02-28 07:02:56\',NULL),(15,\'PER-MON\',\'QV-PROD-PER-MON\',\'2026-02-28 07:02:21\',\'2026-02-28 07:02:21\',NULL),(16,\'PER-MOU\',\'QV-PROD-PER-MOU\',\'2026-02-28 07:02:21\',\'2026-02-28 07:02:21\',NULL),(17,\'PER-HDS\',\'QV-PROD-PER-HDS\',\'2026-02-28 07:02:21\',\'2026-02-28 07:02:21\',NULL),(18,\'PER-MIC\',\'QV-PROD-PER-MIC\',\'2026-02-28 07:02:21\',\'2026-02-28 07:02:21\',NULL),(19,\'PER-MSP\',\'QV-PROD-PER-MSP\',\'2026-02-28 07:02:21\',\'2026-02-28 07:02:21\',NULL),(20,\'PER-KEY\',\'QV-PROD-PER-KEY\',\'2026-02-28 07:02:21\',\'2026-02-28 07:02:21\',NULL),(21,\'PER-CAM\',\'QV-PROD-PER-CAM\',\'2026-02-28 07:02:21\',\'2026-02-28 07:02:21\',NULL);');
    }
}
