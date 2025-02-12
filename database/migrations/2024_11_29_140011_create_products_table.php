<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('vendor_id')->constrained('users')->after('id');
            $table->foreignId('categoryId')->constrained('categories');

            // Basic information
            $table->string('name');
            $table->string('sku')->unique()->nullable();
            $table->string('code')->unique()->nullable();
            $table->text('description');
            $table->text('subDescription');
            $table->enum('publish', ['draft', 'published']);
            
            // Add brand column after basic information
            $table->json('brand')->nullable();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('priceSale', 10, 2)->nullable();
            $table->decimal('taxes', 10, 2)->default(0);

            // Media
            $table->string('coverUrl')->nullable(); // Make it nullable

            // Attributes
            $table->json('tags')->nullable();
            $table->json('sizes')->nullable();
            $table->json('colors')->nullable();
            $table->json('gender')->nullable();

            // Inventory
            $table->string('inventoryType')->default('In Stock');
            $table->integer('quantity')->default(0);
            $table->integer('available')->default(0);
            $table->integer('totalSold')->default(0);

            // Ratings and Reviews
            $table->float('totalRatings')->default(0);
            $table->integer('totalReviews')->default(0);

            // Labels
            $table->json('newLabel')->nullable();
            $table->json('saleLabel')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
