<?php

/*
|--------------------------------------------------------------------------
| Quiz catalog
|--------------------------------------------------------------------------
|
| Single source of truth for the quiz section. The seeder, the QuizController
| (selection + validation), and the quiz index view all read this so the
| category → level → section tree never drifts between them.
|
| Section *codes* (kanji, technology, …) are stored on `questions.section` and
| `quiz_attempts.section`; the *labels* here are only for display.
|
*/

$jlptSections = [
    'kanji' => 'Kanji',
    'vocab' => 'Vocabulary',
    'grammar' => 'Grammar',
];

return [

    // Number of questions a user may choose to answer.
    'counts' => [10, 20, 40, 60],

    // Questions generated per (category, level, section) pool by ExamSeeder.
    // Must be >= max(counts) so every choice can be filled.
    'seed_per_pool' => 60,

    // Percentage needed for the "Pass" label on the result page.
    'pass_mark' => 60,

    // category name => [ label, levels => [ level code => [ label, sections ] ] ]
    'catalog' => [

        'JLPT' => [
            'label' => 'JLPT',
            'blurb' => 'Japanese Language Proficiency Test',
            'levels' => [
                'N1' => ['label' => 'N1', 'sections' => $jlptSections],
                'N2' => ['label' => 'N2', 'sections' => $jlptSections],
                'N3' => ['label' => 'N3', 'sections' => $jlptSections],
                'N4' => ['label' => 'N4', 'sections' => $jlptSections],
                'N5' => ['label' => 'N5', 'sections' => $jlptSections],
            ],
        ],

        'ITPEC' => [
            'label' => 'ITPEC',
            'blurb' => 'IT Professionals Examination',
            'levels' => [
                'IP' => [
                    'label' => 'IP — IT Passport',
                    'sections' => [],
                ],
                'FE' => [
                    'label' => 'FE — Fundamental Engineer',
                    'sections' => [
                        'technology' => 'Technology',
                        'strategy' => 'Strategy',
                    ],
                ],
            ],
        ],

    ],
];
