<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filenames encode an AM/PM sitting and whether the PDF is the question
     * paper or its answer key. Store both so papers can be filtered later.
     */
    public function up(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $table->string('part')->nullable()->after('session');     // AM | PM
            $table->string('doc_type')->nullable()->after('part');    // question | answer
        });
    }

    public function down(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $table->dropColumn(['part', 'doc_type']);
        });
    }
};
