<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table): void {
            // ponytail: bookmarks stay non-unique so one chapter can have multiple saved positions
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('chapter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
