<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mega_company_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('full_address');
            $table->string('phone_number');
            $table->string('email');
            $table->enum('type', ['office', 'warehouse', 'branch'])->default('office');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mega_company_addresses');
    }
};
