<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('initial_submissions')->cascadeOnDelete();
            $table->string('item_type')->index();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->json('snapshot')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_items');
    }
};
