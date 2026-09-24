<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('novel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'novel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
