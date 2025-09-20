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
            $table->string('status')->default(\App\Enums\AppStatusEnum::UN_VERIFIED->value);

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
