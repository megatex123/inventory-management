<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BusinessIdBackfill extends Command
{
    protected $signature = 'business-id:backfill {entity?} {--dry-run}';

    protected $description = 'Renumber existing business/display IDs to the normalized format (full backfill, not just new records)';

    private const SIMPLE_ENTITIES = [
        'customer' => ['table' => 'customers', 'column' => 'customer_id', 'prefix' => 'QV-CUST-', 'pad' => 6],
        'meeting' => ['table' => 'meetings', 'column' => 'meeting_id', 'prefix' => 'QV-MEET-', 'pad' => 6],
        'order' => ['table' => 'order', 'column' => 'order_id', 'prefix' => 'QV-ORDR-', 'pad' => 6],
        'serve' => ['table' => 'serve_data', 'column' => 'serve_id', 'prefix' => 'QV-SRV-', 'pad' => 6],
        'supplier' => ['table' => 'suppliers', 'column' => 'supplier_id', 'prefix' => 'QV-SUPP-', 'pad' => 6],
    ];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $entity = $this->argument('entity');

        $validEntities = array_merge(array_keys(self::SIMPLE_ENTITIES), ['care', 'inv-care']);
        if ($entity && !in_array($entity, $validEntities, true)) {
            $this->error("Unknown entity '{$entity}'. Valid: " . implode(', ', $validEntities));
            return 1;
        }

        if ($dryRun) {
            $this->comment('DRY RUN — no changes will be written.');
        }

        foreach (self::SIMPLE_ENTITIES as $key => $config) {
            if ($entity && $entity !== $key) {
                continue;
            }
            $this->backfillSimple($key, $config, $dryRun);
        }

        if (!$entity || $entity === 'care') {
            $this->backfillCareDataId($dryRun);
        }

        if (!$entity || $entity === 'inv-care') {
            $this->backfillInvCare($dryRun);
        }

        return 0;
    }

    private function backfillSimple(string $label, array $config, bool $dryRun)
    {
        $this->info("=== {$label} ({$config['table']}.{$config['column']}) ===");

        $rows = DB::table($config['table'])->orderBy('id')->get(['id', $config['column']]);

        $sequence = 0;
        foreach ($rows as $row) {
            $sequence++;
            $newValue = $config['prefix'] . str_pad((string) $sequence, $config['pad'], '0', STR_PAD_LEFT);
            $oldValue = $row->{$config['column']};

            if ($oldValue === $newValue) {
                continue;
            }

            $this->line("  id {$row->id}: " . ($oldValue ?? '(none)') . " -> {$newValue}");

            if (!$dryRun) {
                DB::table($config['table'])->where('id', $row->id)->update([$config['column'] => $newValue]);
            }
        }
    }

    private function backfillCareDataId(bool $dryRun)
    {
        $this->info('=== care (care_data.care_data_id) ===');

        $rows = DB::table('care_data')->orderBy('id')->get(['id', 'care_data_id']);

        $sequence = 0;
        foreach ($rows as $row) {
            $sequence++;
            $newValue = 'QV-CARE-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $oldValue = $row->care_data_id;

            if ($oldValue === $newValue) {
                continue;
            }

            $this->line("  id {$row->id}: " . ($oldValue ?? '(none)') . " -> {$newValue}");

            if (!$dryRun) {
                DB::table('care_data')->where('id', $row->id)->update(['care_data_id' => $newValue]);
            }
        }
    }

    private function backfillInvCare(bool $dryRun)
    {
        $this->info('=== inv-care (inv_care.inv_care, per-category) ===');

        $categories = DB::table('categories')->pluck('name', 'id');
        $rows = DB::table('inv_care')->orderBy('id')->get(['id', 'inv_care', 'category']);
        $grouped = $rows->groupBy('category');

        foreach ($grouped as $categoryId => $categoryRows) {
            $categoryName = $categories[$categoryId] ?? 'MISC';
            $prefix = "IC-{$categoryName}-";

            $sequence = 0;
            foreach ($categoryRows->sortBy('id') as $row) {
                $sequence++;
                $newValue = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
                $oldValue = $row->inv_care;

                if ($oldValue === $newValue) {
                    continue;
                }

                $this->line("  id {$row->id} (category {$categoryName}): " . ($oldValue ?? '(none)') . " -> {$newValue}");

                if (!$dryRun) {
                    DB::table('inv_care')->where('id', $row->id)->update(['inv_care' => $newValue]);
                }
            }
        }
    }
}
