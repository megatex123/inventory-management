<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('serves')->truncate();

        DB::unprepared('INSERT INTO `serves` (`id`, `name`, `code`, `colour`, `fee`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,\'Essential Kit\',\'BEK-2304\',\'#BF40BF\',\'0.00\',\'### **Eligibility:**\\n`Total Build Price < RM 7,000`\\n### **Customer-Facing ID Format:**\\nBEK-2304-XXXX\\n### **Perks:**\\n* 1-year assembly warranty\\n* 1× onsite troubleshoot (within 90 days)\\n* 1× basic cable refresh\\n* Remote support: 3–5 working days\\n* 50% off 1× dust cleaning (Year 1)\',\'2025-12-30 02:27:06\',\'2025-12-31 18:28:01\',NULL),(2,\'Prime Series\',\'MPS-0407\',\'#FFFFFF\',\'200.00\',\'### **Eligibility:**\\n`RM 7,000 – RM 9,999`\\n### **Customer-Facing ID Format:**\\nMPS-0407-XXXX\\n### **Perks:**\\n* 2-year assembly warranty\\n* 2× onsite troubleshoot sessions (within 6 months)\\n* 2× advanced cable refresh\\n* 1× free cleaning (Year 1), 50% off next year\\n* 30% off upgrade labor (Year 1)\\n* RM100 promo code\\n* Merch discounts\',\'2025-12-30 02:49:27\',\'2025-12-31 18:28:11\',NULL),(3,\'Collector’s Edition\',\'PCE-2610\',\'#FFD700\',\'400.00\',\'### **Eligibility:**\\n`≥ RM 10,000`\\n### **Customer-Facing ID Format:**\\nPCE-2610-XXXX\\n### **Perks:**\\n* 3 years unlimited troubleshooting\\n* Next 7 years = 50% off troubleshooting\\n* 4× premium cable refresh (first 2 years)\\n* Free annual cleaning (first 3 years)\\n* Premium merch discounts\\n* RM200 promo code\\n* Express Lab access\\n* Optional upgrade:\\n  **Collector + Carbon Fiber Keychain = RM469.90**\\n  (only for Collector customers)\',\'2025-12-30 02:59:49\',\'2025-12-31 18:27:48\',NULL);');
    }
}
