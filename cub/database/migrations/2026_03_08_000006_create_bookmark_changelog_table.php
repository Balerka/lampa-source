<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmark_changelog', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('version');
            $table->string('action', 20);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->longText('data')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmark_changelog');
    }
};
