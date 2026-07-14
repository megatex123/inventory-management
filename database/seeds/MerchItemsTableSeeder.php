<?php

use Illuminate\Database\Seeder;
use App\Models\MerchItem;

class MerchItemsTableSeeder extends Seeder
{
    /**
     * Retail catalog from BOM_QVMR.csv, discount prices from BOM_DIS_QVMR.csv,
     * is_exclusive from which inventory pool (I_QVMR vs IE_QVMR) each SKU
     * ships from in the source spreadsheet.
     *
     * @return void
     */
    public function run()
    {
        // delete() not truncate() — merch_order_items has an FK into this table,
        // and MySQL refuses TRUNCATE on any table referenced by a FK regardless
        // of whether the child table actually has rows.
        MerchItem::query()->forceDelete();

        $items = [
            ['sku_code' => 'QVSKU 0005', 'name' => 'Quivitech White Embroidery Keychain', 'retail_price' => 19.90, 'member_discount_price' => 10.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0006', 'name' => 'Quivitech Red Eagle Hook Keychain', 'retail_price' => 29.90, 'member_discount_price' => 15.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0007', 'name' => 'Quivitech Yellow Eagle Hook Keychain', 'retail_price' => 29.90, 'member_discount_price' => 15.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0008', 'name' => 'Quivitech Blue Eagle Hook Keychain', 'retail_price' => 29.90, 'member_discount_price' => 15.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0009', 'name' => 'Quivitech Pink Eagle Hook Keychain', 'retail_price' => 29.90, 'member_discount_price' => 15.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0016', 'name' => 'Quivitech 2cm x 15cm Velcro Back to Back', 'retail_price' => 9.90, 'member_discount_price' => 6.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0017', 'name' => 'Quivitech 1" x 6" Velcro OneWrap', 'retail_price' => 34.90, 'member_discount_price' => null, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0018', 'name' => 'Quivitech Microfiber Pouch', 'retail_price' => 19.90, 'member_discount_price' => 13.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0019', 'name' => 'Quivitech Polymer Pouch', 'retail_price' => 29.90, 'member_discount_price' => 15.90, 'is_exclusive' => false],
            ['sku_code' => 'QVSKU 0010', 'name' => 'Quivitech Carbon Fiber Keychain', 'retail_price' => 199.90, 'member_discount_price' => null, 'is_exclusive' => true],
            ['sku_code' => 'QVSKU 0011', 'name' => 'Quivitech Full Grain Leather Keychain', 'retail_price' => 69.90, 'member_discount_price' => null, 'is_exclusive' => true],
            ['sku_code' => 'QVSKU 0020', 'name' => 'Quivitech Neoprene Pouch', 'retail_price' => 79.90, 'member_discount_price' => null, 'is_exclusive' => true],
        ];

        foreach ($items as $i => $item) {
            MerchItem::create([
                'item_code' => 'MI-QVMR-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'sku_code' => $item['sku_code'],
                'name' => $item['name'],
                'retail_price' => $item['retail_price'],
                'member_discount_price' => $item['member_discount_price'],
                'is_exclusive' => $item['is_exclusive'],
                'status' => 1,
            ]);
        }
    }
}
