<?php

use Illuminate\Database\Seeder;
use App\Models\InvMerch;

class InvMerchTableSeeder extends Seeder
{
    /**
     * From Inv_QVMR_I_QVMR.csv (general merch stock pool).
     *
     * @return void
     */
    public function run()
    {
        InvMerch::truncate();

        $rows = [
            ['sku_code' => 'QVSKU 0005', 'item_name' => 'Quivitech White Embroidery Keychain', 'unit_cost' => 6, 'max_stock' => 200, 'current_stock' => 200, 'to_restock' => 60],
            ['sku_code' => 'QVSKU 0006', 'item_name' => 'Quivitech Red Eagle Hook Keychain', 'unit_cost' => 8, 'max_stock' => 50, 'current_stock' => 50, 'to_restock' => 15],
            ['sku_code' => 'QVSKU 0007', 'item_name' => 'Quivitech Yellow Eagle Hook Keychain', 'unit_cost' => 8, 'max_stock' => 50, 'current_stock' => 50, 'to_restock' => 15],
            ['sku_code' => 'QVSKU 0008', 'item_name' => 'Quivitech Blue Eagle Hook Keychain', 'unit_cost' => 8, 'max_stock' => 51, 'current_stock' => 51, 'to_restock' => 16],
            ['sku_code' => 'QVSKU 0009', 'item_name' => 'Quivitech Pink Eagle Hook Keychain', 'unit_cost' => 8, 'max_stock' => 51, 'current_stock' => 51, 'to_restock' => 16],
            ['sku_code' => 'QVSKU 0016', 'item_name' => 'Quivitech 2cm x 15cm Velcro Back to Back', 'unit_cost' => 1, 'max_stock' => 400, 'current_stock' => 400, 'to_restock' => 120],
            ['sku_code' => 'QVSKU 0017', 'item_name' => 'Quivitech 1" x 6" Velcro OneWrap', 'unit_cost' => 6, 'max_stock' => 300, 'current_stock' => 300, 'to_restock' => 90],
            ['sku_code' => 'QVSKU 0018', 'item_name' => 'Quivitech Microfiber Pouch', 'unit_cost' => 7, 'max_stock' => 200, 'current_stock' => 200, 'to_restock' => 60],
            ['sku_code' => 'QVSKU 0019', 'item_name' => 'Quivitech Polymer Pouch', 'unit_cost' => 8, 'max_stock' => 200, 'current_stock' => 200, 'to_restock' => 60],
        ];

        foreach ($rows as $i => $row) {
            InvMerch::create([
                'inv_merch_id' => 'I-QVMR-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
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
