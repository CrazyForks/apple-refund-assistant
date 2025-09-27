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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->boolean('activate')->default(true);
            $table->string('role')->default(\App\Enums\UserRoleEnum::ADMIN);
            $table->unsignedBigInteger('default_app_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn('activate');
            $table->dropColumn('role');
            $table->dropColumn('default_app_id');
        });
    }
};
