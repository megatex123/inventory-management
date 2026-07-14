<?php

use Illuminate\Database\Seeder;
use App\Models\ThreadBomHeader;
use App\Models\ThreadBomLine;

class ThreadBomTableSeeder extends Seeder
{
    /**
     * From BOM_QVTD_Asus.csv / BOM_QVTD_Corsair.csv / BOM_QVTD_SeaSonic.csv.
     * Asus and SeaSonic's source sheets are byte-identical (just the Brand
     * column differs) so they share one line set; Corsair genuinely differs
     * (different connector quantities on the EPS/PCIe bundles).
     *
     * Each source row also carries a "Default" flag picking between
     * alternative connector/sleeve colours (e.g. MOLEX Blue vs MDPC-X
     * Black). v1 only seeds the Default=True line per component so every
     * header resolves to one complete, unambiguous parts list — the
     * colour-variant alternatives exist in the source data but aren't
     * modeled as separate headers yet (see docs/QuiviTech/QuiviThread.md).
     *
     * qty_per_cable here is the CSV's raw "Qty Per Cable" value (not
     * multiplied by "Total Cables Per Package" — that column represents
     * how many physical cables are bundled per sale, e.g. "2 x 8 EPS",
     * which isn't modeled as a multiplier in v1 either).
     *
     * @return void
     */
    public function run()
    {
        ThreadBomLine::query()->delete();
        ThreadBomHeader::query()->forceDelete();

        // sku_code => item_name, for the 19 connector/comb/terminal SKUs used below.
        $names = [
            'QVSKU 0021' => 'MOLEX Black 8 EPS Pin  ATX Connector',
            'QVSKU 0022' => 'MOLEX Blue 8 EPS Pin ATX Connector',
            'QVSKU 0023' => 'MOLEX Blue 10 MB Pin  ATX Connector',
            'QVSKU 0025' => 'MOLEX Black 12V 2x6 PCIe Pin ATX Connector',
            'QVSKU 0027' => 'MOLEX Blue 18 MB Pin  ATX Connector',
            'QVSKU 0029' => 'MOLEX Blue 24 MB Pin  ATX Connector',
            'QVSKU 0031' => 'MDPC-X 8 Pin Cable Comb',
            'QVSKU 0032' => 'MDPC-X 12V 2x6 PCIe Pin Cable Comb',
            'QVSKU 0033' => 'MDPC-X 24 Pin Cable Comb',
            'QVSKU 0034' => 'MDPC-X 4:1 Heatshrink Small',
            'QVSKU 0035' => 'MDPC-X 15 AWG Pin Terminal',
            'QVSKU 0036' => 'MDPC-X 17 AWG Pin Terminal',
            'QVSKU 0049' => 'MDPC-X 3:1 Heatshrink Micro',
            'QVSKU 0051' => 'MOLEX Black 8 PCIe Pin ATX Connector',
        ];

        // Asus & SeaSonic share this line set.
        $standardLines = [
            '24pin' => [
                ['QVSKU 0029', 1, 5.76], ['QVSKU 0023', 1, 1.81], ['QVSKU 0027', 1, 3.91],
                ['QVSKU 0033', 2, 7.31], ['QVSKU 0034', 50, 0.25], ['QVSKU 0035', 50, 0.39],
            ],
            '8eps' => [
                ['QVSKU 0022', 1, 1.9758], ['QVSKU 0051', 1, 1.88], ['QVSKU 0031', 2, 4.87],
                ['QVSKU 0034', 16, 0.25], ['QVSKU 0035', 16, 0.39],
            ],
            '8pcie' => [
                ['QVSKU 0051', 2, 1.88], ['QVSKU 0031', 2, 4.87],
                ['QVSKU 0034', 15, 0.25], ['QVSKU 0035', 15, 0.39],
            ],
            '12v2x6pcie' => [
                ['QVSKU 0051', 2, 1.88], ['QVSKU 0025', 1, 0.66], ['QVSKU 0032', 2, 6.09],
                ['QVSKU 0036', 26, 0.39], ['QVSKU 0049', 26, 0.25],
            ],
        ];

        // Corsair's real differences: doubled EPS connector qty, no
        // heatshrink on 24-pin (matches source Default=False for that row).
        $corsairLines = [
            '24pin' => [
                ['QVSKU 0029', 1, 5.76], ['QVSKU 0023', 1, 1.81], ['QVSKU 0027', 1, 3.91],
                ['QVSKU 0033', 2, 7.31], ['QVSKU 0035', 50, 0.39],
            ],
            '8eps' => [
                ['QVSKU 0022', 2, 1.9758], ['QVSKU 0031', 2, 4.87],
                ['QVSKU 0034', 16, 0.25], ['QVSKU 0035', 16, 0.39],
            ],
            '8pcie' => [
                ['QVSKU 0051', 1, 1.88], ['QVSKU 0022', 1, 1.9758], ['QVSKU 0031', 2, 4.87],
                ['QVSKU 0034', 15, 0.25], ['QVSKU 0035', 15, 0.39],
            ],
            '12v2x6pcie' => [
                ['QVSKU 0022', 2, 1.9758], ['QVSKU 0025', 1, 0.66], ['QVSKU 0032', 2, 6.09],
                ['QVSKU 0036', 26, 0.39], ['QVSKU 0049', 26, 0.25],
            ],
        ];

        $brandLines = [
            'Asus' => $standardLines,
            'SeaSonic' => $standardLines,
            'Corsair' => $corsairLines,
        ];

        foreach ($brandLines as $brand => $cableTypes) {
            foreach ($cableTypes as $cableType => $lines) {
                $header = ThreadBomHeader::create([
                    'psu_brand' => $brand,
                    'cable_type' => $cableType,
                    'colour_variant' => null,
                    'is_default' => true,
                ]);

                foreach ($lines as [$skuCode, $qty, $cost]) {
                    ThreadBomLine::create([
                        'thread_bom_header_id' => $header->id,
                        'sku_code' => $skuCode,
                        'item_name' => $names[$skuCode],
                        'qty_per_cable' => $qty,
                        'unit_cost' => $cost,
                    ]);
                }
            }
        }
    }
}
