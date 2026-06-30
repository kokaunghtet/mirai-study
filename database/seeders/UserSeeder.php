<?php

namespace Database\Seeders;

use App\Models\PomodoroSetting;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'display_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make(config('app.seed_admin_password')),
        ]);

        $mod = User::factory()->moderator()->create([
            'username' => 'moderator',
            'display_name' => 'Moderator',
            'email' => 'mod@example.com',
            'password' => Hash::make(config('app.seed_mod_password')),
        ]);

        User::whereIn('id', [$admin->id, $mod->id])->each(function (User $user) {
            UserPreference::create([
                'user_id' => $user->id,
                'theme_mode' => 'light',
                'accent_color' => 'venom',
                'show_liked_posts' => true,
            ]);

            PomodoroSetting::create([
                'user_id' => $user->id,
            ]);
        });
    }
}
