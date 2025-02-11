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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('password');
            $table->string('phone');
            $table->string('sex');
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('image')->nullable();
            $table->string('city')->nullable();
            $table->string('address');
            $table->text('about')->nullable();
            $table->timestamps();
            $table->rememberToken();
            $table->string('email')->unique();
            $table->boolean('verified')->default(false);
            $table->string('zip_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('status', ['active', 'banned', 'pending', 'rejected'])->default('pending');
            $table->string('mfa_secret')->nullable(); // Add this line
            $table->boolean('is_mfa_enabled')->default(false); // Add this line
            $table->timestamp('mfa_verified_at')->nullable(); // Add this line
            $table->string('email_otp')->nullable(); // Add this line
            $table->timestamp('email_otp_expires_at')->nullable(); // Add this line
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
