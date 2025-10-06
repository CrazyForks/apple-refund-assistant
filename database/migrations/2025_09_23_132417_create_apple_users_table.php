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
        Schema::create('apple_users', function (Blueprint $table) {
            $table->id();
            $table->string('app_account_token')->comment('https://developer.apple.com/documentation/appstoreserverapi/appaccounttoken');
            $table->unsignedBigInteger('app_id')->index();

            // LINK https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest
            $table->decimal('purchased_dollars')->default(0)->comment('https://developer.apple.com/documentation/appstoreserverapi/lifetimedollarspurchased');
            $table->decimal('refunded_dollars')->default(0)->comment('https://developer.apple.com/documentation/appstoreserverapi/lifetimedollarsrefunded');
            $table->bigInteger('play_seconds')->default(0)->comment('https://developer.apple.com/documentation/appstoreserverapi/playtime');
            $table->timestamp('register_at')->nullable()->comment('now()-register_at https://developer.apple.com/documentation/appstoreserverapi/accounttenure');

            $table->timestamps();

            $table->unique(['app_account_token', 'app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apple_users');
    }
};
