<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoDeleteUnapproved extends Command
{
    protected $signature = 'customers:auto-delete-unapproved';

    protected $description = 'Soft delete unapproved customers older than 6 months';

    public function handle()
    {
        $cutoffDate = Carbon::now()->subMonths(6);

        $affected = DB::table('customers')
        ->where(function ($q) {
            $q->where('approve', '!=', 1)
            ->orWhereNull('approve');
        })
        ->whereNull('deleted_at')
        ->where('updated_at', '<', now()->subMonths(6))
        ->update([
            'deleted_at' => now(),
        ]);

        $this->info("Soft deleted {$affected} customers.");
    }
}
