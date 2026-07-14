<?php

use Illuminate\Database\Seeder;
use App\Models\InvThread;

class InvThreadTableSeeder extends Seeder
{
    /**
     * From Inv_QVTD_I_QVTD.csv (QuiviThread connector/sleeve/terminal stock).
     *
     * @return void
     */
    public function run()
    {
        InvThread::query()->forceDelete();

        $rows = [
            ['QVSKU 0021', 'MOLEX Black 8 EPS Pin  ATX Connector', 2, 100, 100, 30],
            ['QVSKU 0022', 'MOLEX Blue 8 EPS Pin ATX Connector', 2, 100, 100, 30],
            ['QVSKU 0023', 'MOLEX Blue 10 MB Pin  ATX Connector', 2, 100, 100, 30],
            ['QVSKU 0024', 'MOLEX Black 10 MB Pin ATX Connector', 1, 100, 100, 30],
            ['QVSKU 0025', 'MOLEX Black 12V 2x6 PCIe Pin ATX Connector', 1, 100, 100, 30],
            ['QVSKU 0026', 'MDPC-X 12V 2x6 PCIe Pin ATX Connector', 18, 12, 12, 4],
            ['QVSKU 0027', 'MOLEX Blue 18 MB Pin  ATX Connector', 4, 100, 100, 30],
            ['QVSKU 0028', 'MDPC-X  18  MB Pin ATX Connector', 6, 23, 23, 7],
            ['QVSKU 0029', 'MOLEX Blue 24 MB Pin  ATX Connector', 6, 100, 100, 30],
            ['QVSKU 0030', 'MDPC-X  24  MB Pin ATX Connector', 6, 20, 20, 6],
            ['QVSKU 0031', 'MDPC-X 8 Pin Cable Comb', 5, 100, 100, 30],
            ['QVSKU 0032', 'MDPC-X 12V 2x6 PCIe Pin Cable Comb', 6, 31, 31, 10],
            ['QVSKU 0033', 'MDPC-X 24 Pin Cable Comb', 7, 37, 37, 12],
            ['QVSKU 0034', 'MDPC-X 4:1 Heatshrink Small', 0, 204, 204, 62],
            ['QVSKU 0035', 'MDPC-X 15 AWG Pin Terminal', 0, 2050, 2050, 615],
            ['QVSKU 0036', 'MDPC-X 17 AWG Pin Terminal', 0, 500, 500, 150],
            ['QVSKU 0038', 'MDPC-X Blackest Black Cable Sleeve XTC', 2, 200, 200, 60],
            ['QVSKU 0039', 'MDPC-X XXX White Cable Sleeve XTC', 2, 200, 200, 60],
            ['QVSKU 0040', 'MDPC-X Gold Cable Sleeve XTC', 2, 100, 100, 30],
            ['QVSKU 0041', 'MDPC-X Blackest Black Cable Sleeve MICRO', 3, 30, 30, 9],
            ['QVSKU 0042', 'MDPC-X XXX White Cable Sleeve MICRO', 4, 30, 30, 9],
            ['QVSKU 0043', 'MDPC-X Gold Cable Sleeve MICRO', 4, 30, 30, 9],
            ['QVSKU 0044', 'MDPC-X Platinum X Cable Sleeve XTC', 2, 100, 100, 30],
            ['QVSKU 0045', 'MDPC-X Perfect Pink Cable Sleeve XTC', 2, 100, 100, 30],
            ['QVSKU 0046', 'MDPC-X White 15-AWG Wire', 4, 500, 500, 150],
            ['QVSKU 0047', 'MDPC-X Grey 17-AWG Wire', 4, 50, 50, 15],
            ['QVSKU 0048', 'MDPC-X Black 23-AWG Wire', 2, 30, 30, 9],
            ['QVSKU 0049', 'MDPC-X 3:1 Heatshrink Micro', 0, 1, 1, 1],
            ['QVSKU 0050', 'MDPC-X 8 PCIe Pin  ATX Connector', 6, 82, 82, 25],
            ['QVSKU 0051', 'MOLEX Black 8 PCIe Pin ATX Connector', 2, 100, 100, 30],
        ];

        foreach ($rows as $i => $row) {
            [$sku, $name, $cost, $max, $current, $restock] = $row;
            InvThread::create([
                'inv_thread_id' => 'I-QVTD-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'sku_code' => $sku,
                'item_name' => $name,
                'unit_cost' => $cost,
                'max_stock' => $max,
                'current_stock' => $current,
                'to_restock' => $restock,
                'status' => 1,
                'generate_id' => 1,
            ]);
        }
    }
}
