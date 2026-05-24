<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPreference;
use App\Models\PomodoroSetting;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1 fixed admin — you can login with this
        $admin = User::factory()->admin()->create([
            'username'     => 'admin',
            'display_name' => 'Admin',
            'email'        => 'admin@example.com',
        ]);

        // 1 fixed moderator
        $mod = User::factory()->moderator()->create([
            'username'     => 'moderator',
            'display_name' => 'Moderator',
            'email'        => 'mod@example.com',
        ]);

        // 1 fixed regular user — for your own testing
        $you = User::factory()->create([
            'username'     => 'testuser',
            'display_name' => 'Test User',
            'email'        => 'test@example.com',
        ]);

        // 20 random users
        $users = User::factory(20)->create();

        // Give all users default preferences and pomodoro settings
        User::all()->each(function (User $user) {
            UserPreference::create([
                'user_id'      => $user->id,
                'theme_mode'   => 'light',
                'accent_color' => 'aurora',
            ]);

            PomodoroSetting::create([
                'user_id' => $user->id,
            ]);
        });
    }
}
