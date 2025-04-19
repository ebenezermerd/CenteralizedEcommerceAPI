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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->decimal('additional_cost', 10, 2)->default(0)->after('subtotal');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->decimal('additional_costs_total', 10, 2)->default(0)->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('additional_cost');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('additional_costs_total');
        });
    }
}; 