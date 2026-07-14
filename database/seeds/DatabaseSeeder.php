<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Reference/lookup data — safe to reseed on a fresh install.
        // These mirror the current quivi database as of 2026-07-08.
        $this->call(CategoriesTableSeeder::class);
        $this->call(SubCategoriesTableSeeder::class);
        $this->call(BrandTableSeeder::class);
        $this->call(CraftTableSeeder::class);
        $this->call(CareTableSeeder::class);
        $this->call(ServesTableSeeder::class);
        $this->call(DestinationTableSeeder::class);

        // QuiviMerch reference data, from the QuiviTech Overview V2 source spreadsheets.
        $this->call(MerchItemsTableSeeder::class);
        $this->call(InvMerchTableSeeder::class);
        $this->call(InvExclMerchTableSeeder::class);

        // QuiviPlus reference data.
        $this->call(PlusServicesTableSeeder::class);

        // QuiviThread reference data.
        $this->call(ThreadBomTableSeeder::class);
        $this->call(InvThreadTableSeeder::class);
    }
}
