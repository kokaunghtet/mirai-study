<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\Question;
use Illuminate\Database\Seeder;

class IpQuestionSeeder extends Seeder
{
    /**
     * Import the real FE Technology / FE Strategy questions from the plain-text
     * banks in knowledge/ into their quiz pools (ITPEC → FE → technology|strategy),
     * replacing the Faker placeholders ExamSeeder created for those two pools.
     *
     * Idempotent: each pool is wiped then re-imported, so re-running this seeder
     * (or migrate:fresh --seed) never duplicates rows.
     */
    public function run(): void
    {
        $category = ExamCategory::where('name', 'ITPEC')->first();
        $level = $category
            ? ExamLevel::where('category_id', $category->id)->where('code', 'IP')->first()
            : null;

        if (! $level) {
            $this->command?->warn('IpQuestionSeeder: ITPEC/IP not found — run ExamSeeder first. Skipped.');

            return;
        }

        $banks = [
            '' => base_path('knowledge/ipquiz.txt'),
        ];

        foreach ($banks as $section => $path) {
            $section = $section === '' ? null : $section;

            if (! is_file($path)) {
                $this->command?->warn("IpQuestionSeeder: {$path} missing - skipped IP.");

                continue;
            }

            $questions = $this->parse(file_get_contents($path));

            // Replace the whole pool so the import is idempotent.
            Question::pool($category->id, $level->id, $section)->delete();

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
            }

            $this->command?->info('IpQuestionSeeder: imported '.count($questions).' IP questions.');
        }
    }

    /**
     * Parse a question bank into structured rows.
     *
     * The source files are hand-typed and inconsistent: question separators are
     * sometimes missing, choices use either "(a)" or "a)" markers, and a choice
     * may sit on its own line or run inline with the next one. So we split on the
     * "Q<n>." question markers (always present), cut each block at its "Ans:"
     * line, and slice the four choices by finding their markers in order.
     *
     * @return array<int, array{text:string,a:string,b:string,c:string,d:string,answer:string}>
     */
    private function parse(string $raw): array
    {
        $raw = preg_replace('/\R/', "\n", $raw);
        // One chunk per question: split right before each "Q<n>." marker.
        $chunks = preg_split('/(?=\bQ\d+\s*\.)/m', $raw, -1, PREG_SPLIT_NO_EMPTY);

        $out = [];

        foreach ($chunks as $chunk) {
            if (! preg_match('/Ans\s*:\s*[^A-Da-d\r\n]*([A-Da-d])\s*\)?/', $chunk, $m)) {
                continue; // header/footer or stray text, not a question
            }
            $answer = strtoupper($m[1]);

            // Everything before the answer line is question text + the 4 choices.
            $body = preg_split('/Ans\s*:/', $chunk, 2)[0];

            // Find the four choice markers in order: (a)…(d) or a)…d).
            $offset = 0;
            $pos = [];
            foreach (['a', 'b', 'c', 'd'] as $letter) {
                if (! preg_match('/\(?'.$letter.'\)/', $body, $mm, PREG_OFFSET_CAPTURE, $offset)) {
                    continue 2; // malformed block — skip it
                }
                $pos[$letter] = [$mm[0][1], $mm[0][1] + strlen($mm[0][0])];
                $offset = $pos[$letter][1];
            }

            $text = substr($body, 0, $pos['a'][0]);
            $a = substr($body, $pos['a'][1], $pos['b'][0] - $pos['a'][1]);
            $b = substr($body, $pos['b'][1], $pos['c'][0] - $pos['b'][1]);
            $c = substr($body, $pos['c'][1], $pos['d'][0] - $pos['c'][1]);
            $d = substr($body, $pos['d'][1]);

            // Drop the leading "Q<n>." label from the question text.
            $text = preg_replace('/^\s*Q\d+\s*\.\s*/', '', $text);

            $out[] = [
                'text' => $this->clean($text),
                'a' => $this->clean($a),
                'b' => $this->clean($b),
                'c' => $this->clean($c),
                'd' => $this->clean($d),
                'answer' => $answer,
            ];
        }

        return $out;
    }

    /** Collapse internal whitespace/newlines to single spaces and trim. */
    private function clean(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}
