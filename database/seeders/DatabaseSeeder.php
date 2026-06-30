<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ExamSeeder::class,
            IpQuestionSeeder::class,
            FeQuestionSeeder::class,
            N1QuestionSeeder::class,
            N2QuestionSeeder::class,
            N3QuestionSeeder::class,
            N4QuestionSeeder::class,
            N5QuestionSeeder::class,
        ]);
    }
}
