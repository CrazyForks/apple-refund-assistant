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
        Schema::create('notification_raw_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('app_id');
            $table->string('notification_uuid')->nullable()->index();
            $table->string('notification_type')->nullable();
            $table->string('bundle_id')->nullable();
            $table->string('environment')->nullable();

            $table->text('request_body')->nullable();
            $table->text('payload')->nullable();

            $table->boolean('forward_success')->nullable();
            $table->string('forward_msg')->nullable();

            $table->timestamps();

            $table->index(['notification_uuid', 'app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_raw_logs');
    }
};
