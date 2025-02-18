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

        // Add a trigger to ensure only one default address
        DB::unprepared('
            CREATE TRIGGER ensure_single_default_address
            BEFORE INSERT ON mega_company_addresses
            FOR EACH ROW
            BEGIN
                IF NEW.is_default = 1 THEN
                    UPDATE mega_company_addresses SET is_default = 0 WHERE is_default = 1;
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER ensure_single_default_address_update
            BEFORE UPDATE ON mega_company_addresses
            FOR EACH ROW
            BEGIN
                IF NEW.is_default = 1 AND OLD.is_default = 0 THEN
                    UPDATE mega_company_addresses SET is_default = 0 WHERE is_default = 1;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers first
        DB::unprepared('DROP TRIGGER IF EXISTS ensure_single_default_address');
        DB::unprepared('DROP TRIGGER IF EXISTS ensure_single_default_address_update');

        Schema::dropIfExists('mega_company_addresses');
    }
};
