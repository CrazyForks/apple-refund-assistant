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
        //       id: notification.id,
        //      transaction_id: transactionInfo.transactionId,
        //      original_transaction_id: originalTransactionId,
        //      refund_date: transactionInfo.revocationDate
        //        ? new Date(transactionInfo.revocationDate)
        //        : new Date(),
        //      refund_amount: transactionInfo.price
        //        ? transactionInfo.price / 1000
        //        : undefined,
        //      refund_reason: '撤销原因:' + transactionInfo.revocationReason,
        //      environment: notification.environment,
        Schema::create('refund_logs', function (Blueprint $table) {
            $table->id();

            $table->string('apple_account_token')->index()->nullable()->comment('office update this column');
            $table->unsignedBigInteger('app_id');
            $table->string('bundle_id')->nullable();
            $table->string('notification_uuid')->nullable();
            $table->string('purchase_at')->nullable();


            $table->string('transaction_id')->index();
            $table->string('original_transaction_id')->index();
            $table->decimal('amount');
            $table->string('currency')->nullable();
            $table->timestamp('refund_at')->nullable();


            $table->string('refund_reason')->nullable();
            $table->string('environment')->nullable();

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
