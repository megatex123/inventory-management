<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateCustomersTableAddRegistrationFields extends Migration
{
    public function up()
    {
        // STEP 1: Rename name → full_name if exists
        // Uses raw SQL instead of Schema::renameColumn()/->change() because both
        // require doctrine/dbal, which is not installed in this project. Also
        // widens full_name to nullable: the public registration flow
        // (CustomersController@store) treats it as optional, matching live.
        if (Schema::hasColumn('customers', 'name') && !Schema::hasColumn('customers', 'full_name')) {
            DB::statement("ALTER TABLE customers CHANGE `name` `full_name` VARCHAR(191) NULL DEFAULT NULL");
        }

        // phone/address are nullable live (public registration leaves them
        // blank pending admin approval) even though the original migration
        // created them NOT NULL.
        DB::statement("ALTER TABLE customers MODIFY `phone` VARCHAR(191) NULL DEFAULT NULL");
        DB::statement("ALTER TABLE customers MODIFY `address` VARCHAR(191) NULL DEFAULT NULL");

        // STEP 2: Add / Drop columns safely
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_id')) {
                $table->string('customer_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('customers', 'preferred_name')) {
                $table->string('preferred_name')->after('full_name');
            }
            if (!Schema::hasColumn('customers', 'contact_method')) {
                $table->string('contact_method')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'contact_other')) {
                $table->string('contact_other')->nullable()->after('contact_method');
            }
            if (!Schema::hasColumn('customers', 'feedback')) {
                $table->text('feedback')->nullable()->after('contact_other');
            }
            if (!Schema::hasColumn('customers', 'hear_about')) {
                $table->string('hear_about')->nullable()->after('feedback');
            }
            if (!Schema::hasColumn('customers', 'hear_about_other')) {
                $table->string('hear_about_other')->nullable()->after('hear_about');
            }
            if (!Schema::hasColumn('customers', 'referred_by')) {
                $table->string('referred_by')->nullable()->after('hear_about_other');
            }
            if (!Schema::hasColumn('customers', 'consent')) {
                $table->boolean('consent')->nullable()->after('referred_by');
            }
            if (!Schema::hasColumn('customers', 'approve')) {
                $table->boolean('approve')->nullable()->after('consent');
            }
            if (!Schema::hasColumn('customers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approve');
            }
            if (Schema::hasColumn('customers', 'photo')) {
                $table->dropColumn('photo');
            }
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('customers', 'update_token')) {
                $table->string('update_token')->nullable()->unique()->after('approved_at');
            }
            if (!Schema::hasColumn('customers', 'update_used')) {
                $table->boolean('update_used')->default(false)->after('update_token');
            }
        });

        // STEP 3: Populate customer_id for existing records
        $customers = DB::table('customers')->whereNull('customer_id')->select('id')->get();
        foreach ($customers as $customer) {
            DB::table('customers')->where('id', $customer->id)
                ->update(['customer_id' => 'QVCST-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT)]);
        }

        // STEP 4: Make customer_id UNIQUE & NOT NULL
        // Raw SQL instead of ->change()/getDoctrineSchemaManager() — same
        // doctrine/dbal-not-installed reason as STEP 1.
        DB::statement("ALTER TABLE customers MODIFY `customer_id` VARCHAR(191) NOT NULL");

        $hasUnique = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'customers')
            ->where('column_name', 'customer_id')
            ->where('non_unique', 0)
            ->exists();

        if (!$hasUnique) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unique('customer_id');
            });
        }
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('customers', 'customer_id')) {
                $table->dropUnique(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('customers', 'preferred_name')) {
                $table->dropColumn('preferred_name');
            }
            if (Schema::hasColumn('customers', 'contact_method')) {
                $table->dropColumn('contact_method');
            }
            if (Schema::hasColumn('customers', 'contact_other')) {
                $table->dropColumn('contact_other');
            }
            if (Schema::hasColumn('customers', 'feedback')) {
                $table->dropColumn('feedback');
            }
            if (Schema::hasColumn('customers', 'hear_about')) {
                $table->dropColumn('hear_about');
            }
            if (Schema::hasColumn('customers', 'hear_about_other')) {
                $table->dropColumn('hear_about_other');
            }
            if (Schema::hasColumn('customers', 'referred_by')) {
                $table->dropColumn('referred_by');
            }
            if (Schema::hasColumn('customers', 'consent')) {
                $table->dropColumn('consent');
            }
            if (Schema::hasColumn('customers', 'approve')) {
                $table->dropColumn('approve');
            }
            if (Schema::hasColumn('customers', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('customers', 'plan')) {
                $table->dropColumn('plan');
            }
            if (Schema::hasColumn('customers', 'update_token')) {
                $table->dropColumn('update_token');
            }
            if (Schema::hasColumn('customers', 'update_used')) {
                $table->dropColumn('update_used');
            }
        });

        if (Schema::hasColumn('customers', 'full_name')) {
            DB::statement("ALTER TABLE customers CHANGE `full_name` `name` VARCHAR(191) NOT NULL");
        }
    }
}
