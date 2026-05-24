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
            $table->text('text');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->enum('answer', ['A', 'B', 'C', 'D']);
            $table->text('explanation')->nullable();
            $table->timestamps();
            $table->index('category_id');
            $table->index('level_id');
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
