<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('icon', 32)->default('l_1');
            $table->boolean('main')->default(false);
            $table->boolean('child')->default(false);
            $table->unsignedTinyInteger('age')->default(18);
            $table->unsignedBigInteger('bookmarks_version')->default(0);
            $table->unsignedBigInteger('timelines_version')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
