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
        $admin = User::create([
            'username' => 'admin',
            'display_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make(config('app.seed_admin_password')),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $mod = User::create([
            'username' => 'moderator',
            'display_name' => 'Moderator',
            'email' => 'mod@example.com',
            'password' => Hash::make(config('app.seed_mod_password')),
            'role' => 'moderator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        foreach ([$admin, $mod] as $user) {
            UserPreference::create([
                'user_id' => $user->id,
                'theme_mode' => 'light',
                'accent_color' => 'venom',
                'show_liked_posts' => true,
            ]);

            PomodoroSetting::create([
                'user_id' => $user->id,
            ]);
        }
    }
}
