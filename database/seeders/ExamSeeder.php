<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\Question;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Build the category → level → section tree from config/quiz.php and seed
     * enough questions per pool that any 20/40/60 quiz can be filled.
     */
    public function run(): void
    {
        $perPool = (int) config('quiz.seed_per_pool', 60);

        foreach (config('quiz.catalog') as $categoryName => $categoryDef) {
            $category = ExamCategory::create(['name' => $categoryName]);

            foreach ($categoryDef['levels'] as $levelCode => $levelDef) {
                $level = ExamLevel::create([
                    'category_id' => $category->id,
                    'code' => $levelCode,
                    'name' => $levelDef['label'],
                ]);

                $sections = $levelDef['sections'] ?? [];

                if (empty($sections)) {
                    // Level with no 3rd tier (e.g. ITPEC IP): one flat pool.
                    $this->seedPool($category->id, $level->id, null, "{$categoryName} {$levelCode}", $perPool);

                    continue;
                }

                // One pool per section (JLPT kanji/vocab/grammar, FE technology/strategy).
                foreach ($sections as $sectionCode => $sectionLabel) {
                    $this->seedPool($category->id, $level->id, $sectionCode, "{$levelCode} {$sectionLabel}", $perPool);
                }
            }
        }
    }

    /**
     * Create $count placeholder questions for one pool. The $tag is prefixed
     * onto the question text so seeded data is recognisable in the UI.
     */
    private function seedPool(int $categoryId, int $levelId, ?string $section, string $tag, int $count): void
    {
        Question::factory($count)
            // Closure state runs per model, so each row gets its own Faker text.
            ->state(fn () => ['text' => "[{$tag}] ".fake()->sentence().'?'])
            ->create([
                'category_id' => $categoryId,
                'level_id' => $levelId,
                'section' => $section,
            ]);
    }
}
