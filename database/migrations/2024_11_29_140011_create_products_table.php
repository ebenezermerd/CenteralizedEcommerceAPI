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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('name');
            $table->string('code');
            $table->decimal('price', 8, 2);
            $table->decimal('taxes', 8, 2);
            $table->json('tags');
            $table->json('sizes');
            $table->string('publish');
            $table->json('gender');
            $table->string('cover_url');
            $table->json('images');
            $table->json('colors');
            $table->integer('quantity');
            $table->string('category');
            $table->integer('available');
            $table->integer('total_sold');
            $table->text('description');
            $table->integer('total_ratings');
            $table->integer('total_reviews');
            $table->timestamp('created_at')->useCurrent();
            $table->string('inventory_type');
            $table->text('sub_description');
            $table->decimal('price_sale', 8, 2)->nullable();
            $table->json('sale_label');
            $table->json('new_label');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};