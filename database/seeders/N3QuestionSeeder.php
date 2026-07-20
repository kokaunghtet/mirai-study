<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\Question;
use Illuminate\Database\Seeder;

class N3QuestionSeeder extends Seeder
{
    /**
     * Import the real JLPT N1 questions from plain-text banks.
     */
    public function run(): void
    {
        $category = ExamCategory::where('name', 'JLPT')->first();
        $level = $category
            ? ExamLevel::where('category_id', $category->id)->where('code', 'N3')->first()
            : null;

        if (! $level) {
            $this->command?->warn('N3QuestionSeeder: JLPT not found — run ExamSeeder first. Skipped.');

            return;
        }

        $banks = [
            'kanji' => base_path('wiki/jlpt/N3kanji.txt'),
            'vocab' => base_path('wiki/jlpt/N3vocabulary.txt'),
            'grammar' => base_path('wiki/jlpt/N3grammar.txt'),
        ];

        foreach ($banks as $section => $path) {
            if (! is_file($path)) {
                $this->command?->warn("N3QuestionSeeder: {$path} missing — skipped {$section}.");

                continue;
            }

            $questions = $this->parse(file_get_contents($path));

            Question::where('category_id', $category->id)
                ->where('level_id', $level->id)
                ->where('section', $section)
                ->delete();

            $importedCount = 0;
            foreach ($questions as $q) {
                Question::create([
                    'category_id' => $category->id,
                    'level_id' => $level->id,
                    'section' => $section,
                    'text' => $q['text'],
                    'option_a' => $q['a'],
                    'option_b' => $q['b'],
                    'option_c' => $q['c'],
                    'option_d' => $q['d'],
                    'answer' => $q['answer'],
                ]);
                $importedCount++;
            }

            $this->command?->info("N3QuestionSeeder: imported {$importedCount} {$section} questions.");
        }
    }

    /**
     * Parse a question bank into structured rows safely without altering text files.
     */
    private function parse(string $raw): array
    {
        // 1. Normalize all line breaks to standard \n
        $raw = preg_replace('/\R/u', "\n", $raw);

        // 2. Remove all those long dashed lines
        $raw = preg_replace('/-{3,}/u', '', $raw);

        // 3. Convert all full-width numbers and symbols to standard half-width text safely
        $raw = mb_convert_kana($raw, 'as', 'UTF-8');
        $raw = str_replace('．', '.', $raw);

        // 4. Match every question block safely
        // FIXED: Added the 'u' modifier to ensure correct multi-byte evaluation
        $pattern = '/(?:\b|\s)(\d+)\.\s*(.+?)(correct\s+answer\s*[:\-]\s*[A-Da-d\s]+)/isu';

        $out = [];
        if (preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $body = $match[2];
                $footer = $match[3];

                if (preg_match('/correct\s+answer\s*[:\-]\s*([A-Da-d])/i', $footer, $ansMatch)) {
                    $answer = strtoupper(trim($ansMatch[1]));
                } else {
                    continue;
                }

                $patternA = '/(?:\b|\s)\(?[aあ][:\)]\s*/ui';
                $patternB = '/(?:\b|\s)\(?[bい][:\)]\s*/ui';
                $patternC = '/(?:\b|\s)\(?[cう][:\)]\s*/ui';
                $patternD = '/(?:\b|\s)\(?[dえ][:\)]\s*/ui';

                $partsA = preg_split($patternA, $body, 2) ?: [];
                if (count($partsA) < 2) {
                    continue;
                }
                $text = $partsA[0];

                $partsB = preg_split($patternB, $partsA[1], 2) ?: [];
                if (count($partsB) < 2) {
                    continue;
                }
                $a = $partsB[0];

                $partsC = preg_split($patternC, $partsB[1], 2) ?: [];
                if (count($partsC) < 2) {
                    continue;
                }
                $b = $partsC[0];

                $partsD = preg_split($patternD, $partsC[1], 2) ?: [];
                if (count($partsD) < 2) {
                    continue;
                }
                $c = $partsD[0];
                $d = $partsD[1];

                $out[] = [
                    'text' => $this->clean($text),
                    'a' => $this->clean($a),
                    'b' => $this->clean($b),
                    'c' => $this->clean($c),
                    'd' => $this->clean($d),
                    'answer' => $answer,
                ];
            }
        }

        return $out;
    }

    private function clean(string $s): string
    {
        // FIXED: Added 'u' modifier here as well to safely compress Japanese spaces
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
