<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->decimal('subtotal', 8, 2)->default(0.00);
            $table->decimal('shipping', 8, 2)->default(0.00);
            $table->decimal('discount', 8, 2)->default(0.00);
            $table->decimal('tax', 8, 2)->default(0.00);
            $table->decimal('total', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum(fn ($item) => $item->subtotal);
        $this->tax = $this->subtotal * 0.1; // Example tax calculation
        $this->total = $this->subtotal + $this->tax - $this->discount + $this->shipping;
        $this->save();
    }
};
