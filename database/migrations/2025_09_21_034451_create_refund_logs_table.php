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
        Schema::create('refund_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();

            $table->string('app_account_token')->index()->nullable()->comment('office update this column');

            $table->unsignedBigInteger('app_id')->index();
            $table->string('transaction_id')->index();
            $table->string('original_transaction_id')->index();
            $table->string('notification_uuid')->nullable();
            $table->string('bundle_id')->nullable();
            $table->string('bundle_version')->nullable();
            $table->string('environment')->nullable();


            $table->string('purchase_date')->nullable();
            $table->decimal('price');
            $table->string('currency')->nullable();


            $table->timestamp('refund_date')->nullable();
            $table->string('refund_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_logs');
    }
};
