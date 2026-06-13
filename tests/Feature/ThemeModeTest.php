<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_persist_theme_mode(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('settings.theme-mode'), ['theme_mode' => 'dark'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'theme_mode' => 'dark',
        ]);
    }

    public function test_invalid_theme_mode_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('settings.theme-mode'), ['theme_mode' => 'rainbow'])
            ->assertStatus(422);
    }

    public function test_guest_cannot_persist_theme_mode(): void
    {
        $this->patchJson(route('settings.theme-mode'), ['theme_mode' => 'dark'])
            ->assertUnauthorized();
    }
}
