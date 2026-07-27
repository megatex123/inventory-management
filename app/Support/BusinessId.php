<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class BusinessId
{
    /**
     * Generalizes ProductWarrantyController::generateSerialNo()'s existing
     * logic (already the "Normalize ID" target scheme) into one shared helper.
     * No locking/retry — matches this app's existing accepted concurrency
     * risk posture for business-code generation.
     */
    public static function next(string $table, string $column, string $prefix, int $pad = 6): string
    {
        $lastValue = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value($column);

        $nextNumber = 1;
        if ($lastValue) {
            preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastValue, $matches);
            $nextNumber = (isset($matches[1]) ? (int) $matches[1] : 0) + 1;
        }

        return $prefix . str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT);
    }
}
