<?php

use Illuminate\Database\Seeder;
use App\Models\PlusService;

class PlusServicesTableSeeder extends Seeder
{
    /**
     * Service categories from the QuiviTech Overview V2 spec, priced from
     * the real example line items in Copy_of_Invoice.csv where available.
     *
     * @return void
     */
    public function run()
    {
        PlusService::query()->forceDelete();

        $services = [
            ['name' => 'Fan Installation', 'category' => 'installation', 'price' => 18.00],
            ['name' => 'Component Upgrade Service', 'category' => 'upgrade', 'price' => 45.00],
            ['name' => 'Onsite Troubleshooting', 'category' => 'onsite', 'price' => 50.00],
            ['name' => 'Cable Management', 'category' => 'cable_mgmt', 'price' => 40.00],
            ['name' => 'Entry Cleaning', 'category' => 'cleaning', 'price' => 30.00],
            ['name' => 'Deep Cleaning', 'category' => 'cleaning', 'price' => 60.00],
            ['name' => 'GPU Thermal Paste Replacement', 'category' => 'thermal_paste', 'price' => 60.00],
            ['name' => 'Deep Cleaning + GPU Thermal Paste', 'category' => 'combo', 'price' => 120.00],
            ['name' => 'Distance Fee', 'category' => 'distance_fee', 'price' => 20.00],
        ];

        foreach ($services as $i => $service) {
            PlusService::create([
                'service_code' => 'PS-QVPL-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'name' => $service['name'],
                'category' => $service['category'],
                'price' => $service['price'],
                'is_active' => true,
            ]);
        }
    }
}
