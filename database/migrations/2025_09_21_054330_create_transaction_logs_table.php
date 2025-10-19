<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();

            $table->unsignedBigInteger('app_id')->index();
            $table->string('notification_uuid')->nullable();
            $table->string('notification_type')->nullable();
            $table->string('bundle_id')->nullable();
            $table->string('bundle_version')->nullable();
            $table->string('environment')->nullable();

            $table->string('original_transaction_id')->index();
            $table->string('app_account_token')->index()->nullable();
            $table->string('transaction_id')->index();

            $table->string('product_id')->nullable();
            $table->string('product_type')->nullable();
            $table->timestamp('purchase_date')->nullable();
            $table->timestamp('original_purchase_date')->nullable();
            $table->timestamp('expiration_date')->nullable();
            $table->decimal('price')->nullable();
            $table->decimal('currency')->nullable();
            $table->string('in_app_ownership_type')->nullable();
            $table->integer('quantity')->default(1);

            $table->timestamps();

            $table->index(['original_transaction_id', 'app_id']); // For findTransactionByConsumption
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
