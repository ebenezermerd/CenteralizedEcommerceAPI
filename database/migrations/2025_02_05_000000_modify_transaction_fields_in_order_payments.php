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
        Schema::table('order_payments', function (Blueprint $table) {
            // Drop existing columns if you want to modify them
            // $table->dropColumn(['transaction_id', 'payment_date']);

            // Add new columns or modify existing ones
            // For example, if you want to modify the transaction_id to be unique:
            $table->string('transaction_id')->nullable()->unique()->change();
            $table->string('payment_date')->nullable()->change();

            // Or if you want to add additional fields:
            $table->string('transaction_status')->nullable()->after('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            // Reverse the changes
            $table->string('transaction_id')->nullable()->change();
            $table->dropColumn(['payment_date', 'transaction_status']);
        });
    }
};
