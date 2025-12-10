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
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('amount', 16, 8)->default(0)->change();
            $table->decimal('locked_amount', 16, 8)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('price', 16, 4)->change();
            $table->decimal('amount', 16, 8)->change();
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('price', 16, 4)->change();
            $table->decimal('amount', 16, 8)->change();
            $table->decimal('commission', 16, 4)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 16, 4)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('amount', 8, 8)->default(0)->change();
            $table->decimal('locked_amount', 8, 8)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('price', 8, 4)->change();
            $table->decimal('amount', 8, 8)->change();
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('price', 8, 4)->change();
            $table->decimal('amount', 8, 8)->change();
            $table->decimal('commission', 8, 4)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 8, 4)->default(0)->change();
        });
    }
};
