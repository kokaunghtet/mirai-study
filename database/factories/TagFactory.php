<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    private static array $tags = [
        'JLPT',
        'ITPEC',
        'Programming',
        'Study Tips',
        'Productivity',
        'Notes',
    ];

    private static int $index = 0;

    public function definition(): array
    {
        return [
            'name' => self::$tags[self::$index++],
        ];
    }
}
