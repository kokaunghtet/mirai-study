<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Build the category → level → section tree from config/quiz.php and seed
     * enough questions per pool that any 20/40/60 quiz can be filled.
     */
    public function run(): void
    {
        foreach (config('quiz.catalog') as $categoryName => $categoryDef) {
            $category = ExamCategory::create(['name' => $categoryName]);

            foreach ($categoryDef['levels'] as $levelCode => $levelDef) {
                ExamLevel::create([
                    'category_id' => $category->id,
                    'code' => $levelCode,
                    'name' => $levelDef['label'],
                ]);
            }
        }
    }
}
