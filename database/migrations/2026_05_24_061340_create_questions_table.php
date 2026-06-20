<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('exam_categories')->cascadeOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('exam_levels')->nullOnDelete();
            // 3rd tier within a level: JLPT kanji/vocab/grammar, ITPEC FE technology/strategy.
            // Null when the level has no sections (e.g. ITPEC IP). See config/quiz.php.
            $table->string('section')->nullable();
            $table->text('text');
            // Real exam choices can be full sentences (>255 chars), so use text.
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->enum('answer', ['A', 'B', 'C', 'D']);
            $table->text('explanation')->nullable();
            $table->timestamps();
            $table->index('category_id');
            $table->index('level_id');
            // Pool lookups filter on all three — keep them index-covered.
            $table->index(['category_id', 'level_id', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
