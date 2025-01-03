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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('taxes', 8, 2);
            $table->enum('status', ['Completed', 'To process', 'Cancelled', 'Return']);
            $table->decimal('shipping', 8, 2)->default(0);
            $table->decimal('discount', 8, 2)->default(0);
            $table->decimal('subtotal', 8, 2);
            $table->string('order_number')->unique();
            $table->decimal('total_amount', 8, 2);
            $table->integer('total_quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
