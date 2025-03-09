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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_method');
            $table->string('transaction_id')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->decimal('amount', 8, 2);
            $table->string('currency');
            $table->string('tx_ref')->nullable();
            $table->string('original_tx_ref')->nullable();
            $table->string('status')->default('pending');
            $table->string('bank_account')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
