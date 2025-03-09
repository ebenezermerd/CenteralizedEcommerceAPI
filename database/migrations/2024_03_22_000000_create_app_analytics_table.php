<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'active_users', 'installations', 'downloads'
            $table->string('platform')->nullable(); // 'android', 'ios'
            $table->integer('count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_analytics');
    }
};
