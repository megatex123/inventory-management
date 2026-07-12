<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ReplaceDocumentsWithCustomerProgress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // The generic "Document Management" file repository (documents table,
        // zero rows in production) is being replaced by a customer-progress
        // tracker: each row is a stage/milestone tied to a specific customer
        // (and optionally an order), not just a standalone uploaded file.
        Schema::dropIfExists('documents');

        Schema::create('customer_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed, on_hold
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type', 20)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('order_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_progress');

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 20)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
