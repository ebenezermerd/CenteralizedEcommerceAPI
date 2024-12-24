<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('product_id');

            $table->foreignId('user_id')->constrained();
            $table->string('name');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->integer('helpful')->default(0);
            $table->string('avatar_url')->nullable();
            $table->timestamp('posted_at')->useCurrent();
            $table->boolean('is_purchased')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->foreign('product_id')
            ->references('id')
            ->on('products')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
