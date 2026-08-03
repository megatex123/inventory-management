<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddQuiviserveInvMerchRows extends Migration
{
    private $rows = [
        ['sku_code' => 'QVSKU 0001', 'item_name' => 'Essential Kit Box', 'current_stock' => 100, 'max_stock' => 200],
        ['sku_code' => 'QVSKU 0002', 'item_name' => 'Prime Series Box', 'current_stock' => 60, 'max_stock' => 120],
        ['sku_code' => 'QVSKU 0003', 'item_name' => "Collector's Edition Box", 'current_stock' => 25, 'max_stock' => 50],
        ['sku_code' => 'QVSKU 0004', 'item_name' => 'The Stash Screw Box', 'current_stock' => 150, 'max_stock' => 300],
        ['sku_code' => 'QVSKU 0012', 'item_name' => 'Essential Kit Perk Card', 'current_stock' => 100, 'max_stock' => 200],
        ['sku_code' => 'QVSKU 0013', 'item_name' => 'Prime Series Perk Card', 'current_stock' => 60, 'max_stock' => 120],
        ['sku_code' => 'QVSKU 0014', 'item_name' => "Collector's Edition Perk Card", 'current_stock' => 25, 'max_stock' => 50],
    ];

    public function up()
    {
        $lastId = DB::table('inv_merch')
            ->where('inv_merch_id', 'like', 'I-QVMR-%')
            ->orderByRaw('CAST(SUBSTRING(inv_merch_id, 8) AS UNSIGNED) DESC')
            ->value('inv_merch_id');

        $nextSeq = $lastId ? ((int) substr($lastId, 7)) + 1 : 1;

        $now = now();
        $insert = [];
        foreach ($this->rows as $row) {
            $inv_merch_id = 'I-QVMR-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            $nextSeq++;

            $insert[] = [
                'inv_merch_id' => $inv_merch_id,
                'sku_code' => $row['sku_code'],
                'item_name' => $row['item_name'],
                'unit_cost' => 0,
                'max_stock' => $row['max_stock'],
                'current_stock' => $row['current_stock'],
                'to_restock' => 0,
                'status' => 1,
                'generate_id' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('inv_merch')->insert($insert);
    }

    public function down()
    {
        DB::table('inv_merch')->whereIn('sku_code', array_column($this->rows, 'sku_code'))->delete();
    }
}
