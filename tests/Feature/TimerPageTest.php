<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_timer_page(): void
    {
        $response = $this->get(route('timer.index'));

        $response->assertOk();
        $response->assertSee('pomodoroTimer', false);
    }

    public function test_authenticated_user_can_view_timer_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('timer.index'));

        $response->assertOk();
    }

    public function test_focus_pill_renders_on_non_timer_pages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('feed.index'));

        $response->assertOk();
        $response->assertSee('focusPill', false);
    }

    public function test_focus_pill_not_rendered_on_timer_page(): void
    {
        $response = $this->get(route('timer.index'));

        $response->assertOk();
        $response->assertDontSee('focusPill', false);
    }
}
