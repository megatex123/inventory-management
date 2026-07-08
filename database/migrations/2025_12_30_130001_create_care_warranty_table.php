<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCareWarrantyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('care_warranty', function (Blueprint $table) {
            $table->increments('id');
            $table->string('care_warranty_id', 255);
            $table->integer('care_data_id');
            $table->string('care_invoice_id', 255);
            $table->integer('product_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->boolean('eligible_warranty')->nullable()->default(false);
            $table->boolean('eligible_qvca')->nullable()->default(false);
            $table->string('i_qvca_id', 255)->nullable();
            $table->string('spare_item_name', 255)->nullable();
            $table->integer('spare_category_id')->nullable();
            $table->date('date_start')->nullable();
            $table->date('loan_date_end')->nullable();
            $table->boolean('reset_status')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->index('care_data_id', 'idx_care_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('care_warranty');
    }
}
