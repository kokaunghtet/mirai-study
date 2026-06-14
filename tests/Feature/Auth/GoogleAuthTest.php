<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Make Socialite return a fake Google user instead of calling Google.
     */
    private function fakeGoogleUser(array $attributes): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn($attributes['id'] ?? 'google-123');
        $googleUser->shouldReceive('getEmail')->andReturn($attributes['email'] ?? 'jane@gmail.com');
        $googleUser->shouldReceive('getName')->andReturn($attributes['name'] ?? 'Jane Doe');
        $googleUser->shouldReceive('getAvatar')->andReturn($attributes['avatar'] ?? 'https://example.com/a.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_new_google_user_is_created_and_logged_in(): void
    {
        $this->fakeGoogleUser([
            'id' => 'google-abc',
            'email' => 'newcomer@gmail.com',
            'name' => 'New Comer',
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('feed.index', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'newcomer@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-abc', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{3,30}$/', $user->username);
    }

    public function test_existing_email_account_is_linked_not_duplicated(): void
    {
        $existing = User::factory()->create([
            'email' => 'jane@gmail.com',
            'google_id' => null,
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-xyz',
            'email' => 'jane@gmail.com',
            'name' => 'Jane Doe',
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('feed.index', absolute: false));
        $this->assertAuthenticatedAs($existing->fresh());

        // Same row, now linked — no second user created.
        $this->assertSame(1, User::where('email', 'jane@gmail.com')->count());
        $this->assertSame('google-xyz', $existing->fresh()->google_id);
    }

    public function test_username_collision_gets_a_unique_suffix(): void
    {
        User::factory()->create([
            'username' => 'jane',
            'email' => 'jane.smith@example.com',
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-new',
            'email' => 'jane@gmail.com',
            'name' => 'Jane Other',
        ]);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'jane@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertNotSame('jane', $user->username);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{3,30}$/', $user->username);
    }
}
