<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_category', function (Blueprint $table) {
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->primary(['brand_id', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('brand_category');
        Schema::dropIfExists('brands');
    }
}; 