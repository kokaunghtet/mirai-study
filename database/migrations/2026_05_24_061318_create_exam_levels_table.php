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
        Schema::create('exam_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('exam_categories')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->timestamps();
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_levels');
    }
};
