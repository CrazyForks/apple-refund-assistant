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
        Schema::create('notification_logs', function (Blueprint $table) {

            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('app_id')->index();
            $table->string('notification_uuid')->nullable();
            $table->string('notification_type')->nullable();
            $table->string('bundle_id')->nullable();
            $table->string('bundle_version')->nullable();
            $table->string('environment')->nullable();

            $table->text('payload')->nullable();

            $table->tinyInteger('status');
            $table->boolean('forward_success')->nullable();

            $table->timestamps();

            $table->unique(['notification_uuid', 'app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
