<?php

use Illuminate\Database\Seeder;
use App\Models\InvExclMerch;

class InvExclMerchTableSeeder extends Seeder
{
    /**
     * From Inv_Excl_QVMR_IE_QVMR.csv (Collector's-Edition-exclusive merch stock).
     *
     * @return void
     */
    public function run()
    {
        InvExclMerch::truncate();

        $rows = [
            ['sku_code' => 'QVSKU 0010', 'item_name' => 'Quivitech Carbon Fiber Keychain', 'unit_cost' => 43, 'max_stock' => 50, 'current_stock' => 50, 'to_restock' => 15],
            ['sku_code' => 'QVSKU 0011', 'item_name' => 'Quivitech Full Grain Leather Keychain', 'unit_cost' => 16, 'max_stock' => 52, 'current_stock' => 52, 'to_restock' => 16],
            ['sku_code' => 'QVSKU 0020', 'item_name' => 'Quivitech Neoprene Pouch', 'unit_cost' => 8, 'max_stock' => 200, 'current_stock' => 200, 'to_restock' => 60],
        ];

        foreach ($rows as $i => $row) {
            InvExclMerch::create([
                'inv_excl_merch_id' => 'IE-QVMR-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'sku_code' => $row['sku_code'],
                'item_name' => $row['item_name'],
                'unit_cost' => $row['unit_cost'],
                'max_stock' => $row['max_stock'],
                'current_stock' => $row['current_stock'],
                'to_restock' => $row['to_restock'],
                'status' => 1,
                'generate_id' => 1,
            ]);
        }
    }
}
