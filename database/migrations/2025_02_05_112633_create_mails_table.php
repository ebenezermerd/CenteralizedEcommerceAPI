<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mail_labels', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('mails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users');
            $table->string('folder')->default('inbox');
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_unread')->default(true);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_important')->default(false);
            $table->timestamps();
        });

        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('mail_label_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mail_label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

              // Add composite index for better query performance
            $table->index(['mail_id', 'mail_label_id']);
        });

        Schema::create('mail_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->bigInteger('size');
            $table->string('type');
            $table->string('path');
            $table->string('preview')->nullable();
            $table->timestamps();
        });


    // Insert default system labels
    DB::table('mail_labels')->insert([
        [
            'type' => 'system',
            'name' => 'inbox',
            'color' => '#00AB55',
            'created_at' => now(),
        ],
        [
            'type' => 'system',
            'name' => 'sent',
            'color' => '#00B8D9',
            'created_at' => now(),
        ],
        [
            'type' => 'system',
            'name' => 'draft',
            'color' => '#FFC107',
            'created_at' => now(),
        ],
        [
            'type' => 'system',
            'name' => 'trash',
            'color' => '#FF4123',
            'created_at' => now(),
        ],
        [
            'type' => 'system',
            'name' => 'spam',
            'color' => '#919EAB',
            'created_at' => now(),
        ],
    ]);
    }

    public function down()
    {
        Schema::dropIfExists('mail_attachments');
        Schema::dropIfExists('mail_label_assignments');
        Schema::dropIfExists('mail_recipients');
        Schema::dropIfExists('mails');
        Schema::dropIfExists('mail_labels');
    }
};
