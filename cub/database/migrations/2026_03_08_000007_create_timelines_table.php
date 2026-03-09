<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timelines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('hash', 191);
            $table->unsignedTinyInteger('percent')->default(0);
            $table->unsignedInteger('time')->default(0);
            $table->unsignedInteger('duration')->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['profile_id', 'hash']);
            $table->index(['profile_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timelines');
    }
};
