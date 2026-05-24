<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\Question;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        // JLPT
        $jlpt = ExamCategory::create(['name' => 'JLPT']);
        $jlptLevels = ['N1', 'N2', 'N3', 'N4', 'N5'];

        foreach ($jlptLevels as $level) {
            $examLevel = ExamLevel::create([
                'category_id' => $jlpt->id,
                'code'        => $level,
                'name'        => 'JLPT ' . $level,
            ]);

            // 10 questions per level
            Question::factory(10)->create([
                'category_id' => $jlpt->id,
                'level_id'    => $examLevel->id,
            ]);
        }

        // ITPEC
        $itpec = ExamCategory::create(['name' => 'ITPEC']);
        $itpecLevels = [
            ['code' => 'FE',  'name' => 'Fundamental Information Technology Engineer'],
            ['code' => 'IP',  'name' => 'Information Technology Passport'],
        ];

        foreach ($itpecLevels as $level) {
            $examLevel = ExamLevel::create([
                'category_id' => $itpec->id,
                'code'        => $level['code'],
                'name'        => $level['name'],
            ]);

            Question::factory(10)->create([
                'category_id' => $itpec->id,
                'level_id'    => $examLevel->id,
            ]);
        }
    }
}
