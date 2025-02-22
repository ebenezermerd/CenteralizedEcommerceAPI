<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'sale', 'revenue', 'product', 'user'
            $table->decimal('amount', 10, 2)->default(0);
            $table->integer('count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_analytics');
    }
};
