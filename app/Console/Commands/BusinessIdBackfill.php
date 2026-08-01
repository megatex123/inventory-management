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

        $validEntities = array_merge(array_keys(self::SIMPLE_ENTITIES), ['care', 'inv-care', 'craft-tag']);
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

        if (!$entity || $entity === 'craft-tag') {
            $this->backfillCraftTagId($dryRun);
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

    /**
     * Unlike the other entities above, this is NOT a full renumber-every-row
     * backfill -- craft_tag_id only applies to approved orders (approve=1)
     * and is only ever set once, on approval (see
     * OrderController::updateApprove()). This fills in the gap for orders
     * that were approved before that logic existed (or before craft_tag_id
     * existed at all) and so were never assigned one.
     *
     * The running sequence is tracked LOCALLY rather than by repeatedly
     * calling BusinessId::next() per row: that helper looks up the highest
     * existing value in the DB each time, which only advances once a write
     * actually happens -- under --dry-run (no writes), every row would
     * incorrectly compute the same "next" value instead of a real preview
     * of the sequence.
     */
    private function backfillCraftTagId(bool $dryRun)
    {
        $this->info('=== craft-tag (order.craft_tag_id, approved orders only) ===');

        $rows = DB::table('order')
            ->where('approve', 1)
            ->whereNull('craft_tag_id')
            ->orderBy('id')
            ->get(['id']);

        if ($rows->isEmpty()) {
            $this->line('  (nothing to backfill)');
            return;
        }

        $prefix = 'QV-CRFT-';
        $lastValue = DB::table('order')->where('craft_tag_id', 'like', $prefix . '%')->orderByDesc('id')->value('craft_tag_id');
        $nextNumber = 1;
        if ($lastValue) {
            preg_match('/(\d+)$/', $lastValue, $matches);
            $nextNumber = (isset($matches[1]) ? (int) $matches[1] : 0) + 1;
        }

        foreach ($rows as $row) {
            $newValue = $prefix . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;

            $this->line("  id {$row->id}: (none) -> {$newValue}");

            if (!$dryRun) {
                DB::table('order')->where('id', $row->id)->update(['craft_tag_id' => $newValue]);
            }
        }
    }
}
