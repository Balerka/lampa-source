<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_storages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('class_type', 32);
            $table->longText('payload')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'name']);
            $table->index(['user_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_storages');
    }
};
