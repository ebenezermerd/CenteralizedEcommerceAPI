<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mfa_backup_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('salt')->nullable(); // Make salt nullable
            $table->boolean('used')->default(false);
            $table->timestamps();
        });

        Schema::create('mfa_remembered_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_token');
            $table->string('device_signature');  // Added device signature
            $table->string('ip_address');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfa_backup_codes');
        Schema::dropIfExists('mfa_remembered_devices');
    }
};
