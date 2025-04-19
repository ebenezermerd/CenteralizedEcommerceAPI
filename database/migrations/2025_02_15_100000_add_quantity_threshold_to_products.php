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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('quantity_threshold')->nullable()->after('quantity');
            $table->decimal('additional_cost_percentage', 5, 2)->nullable()->after('quantity_threshold');
            $table->decimal('additional_cost_fixed', 10, 2)->nullable()->after('additional_cost_percentage');
            $table->enum('additional_cost_type', ['percentage', 'fixed'])->nullable()->after('additional_cost_fixed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('quantity_threshold');
            $table->dropColumn('additional_cost_percentage');
            $table->dropColumn('additional_cost_fixed');
            $table->dropColumn('additional_cost_type');
        });
    }
}; 