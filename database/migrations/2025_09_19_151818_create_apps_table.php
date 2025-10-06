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
        Schema::create('apps', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();
            $table->string('bundle_id')->nullable();
            $table->string('issuer_id')->nullable();
            $table->string('key_id')->nullable();
            $table->text('p8_key')->nullable()->comment('encrypted');
            $table->string('test_notification_token')->nullable();
            $table->boolean('sample_content_provided')->default(false)->comment('provided sample content');
            $table->string('status')->default(\App\Enums\AppStatusEnum::UN_VERIFIED->value);

            $table->string('notification_url')->nullable();

            $table->decimal('transaction_dollars')->default(0);
            $table->decimal('refund_dollars')->default(0);
            $table->decimal('consumption_dollars')->default(0);
            $table->bigInteger('transaction_count')->default(0);
            $table->bigInteger('refund_count')->default(0);
            $table->bigInteger('consumption_count')->default(0);
            $table->bigInteger('pending_consumption_count')->default(0);
            $table->bigInteger('owner_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
