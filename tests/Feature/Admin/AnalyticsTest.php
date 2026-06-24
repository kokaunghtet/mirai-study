<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('admin.analytics'));
        $this->assertTrue(\Route::has('admin.analytics.data'));
    }

    public function test_guest_cannot_access_analytics(): void
    {
        $response = $this->get(route('admin.analytics'));
        $response->assertRedirect();
    }

    public function test_regular_user_cannot_access_analytics(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.analytics'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_analytics_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics'));
        $response->assertOk();
    }

    public function test_analytics_data_returns_valid_json_shape(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.analytics.data', ['range' => '30d']));
        $response->assertOk();
        $response->assertJsonStructure([
            'labels',
            'registrations',
            'papers',
            'questions',
            'quizAttempts',
            'passRates',
            'kpis' => [
                'totalUsers',
                'totalPapers',
                'quizAttempts',
                'passRate',
                'newUsersThisPeriod',
                'newPapersThisPeriod',
            ],
            'performanceTable',
        ]);
    }

    public function test_analytics_data_7d_has_7_labels(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.analytics.data', ['range' => '7d']));
        $response->assertOk();
        $json = $response->json();
        $this->assertCount(7, $json['labels']);
        $this->assertCount(7, $json['registrations']);
    }

    public function test_analytics_data_30d_has_30_labels(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.analytics.data', ['range' => '30d']));
        $response->assertOk();
        $json = $response->json();
        $this->assertCount(30, $json['labels']);
        $this->assertCount(30, $json['registrations']);
    }

    public function test_analytics_data_90d_has_weekly_labels(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.analytics.data', ['range' => '90d']));
        $response->assertOk();
        $json = $response->json();
        $this->assertGreaterThanOrEqual(12, count($json['labels']));
        $this->assertLessThanOrEqual(14, count($json['labels']));
    }

    public function test_custom_range_daily_when_lte_30_days(): void
    {
        $from = Carbon::now('UTC')->subDays(29)->format('Y-m-d');
        $to = Carbon::now('UTC')->format('Y-m-d');
        $response = $this->actingAs($this->admin)->getJson(
            route('admin.analytics.data', compact('from', 'to'))
        );
        $response->assertOk();
        $json = $response->json();
        $this->assertCount(30, $json['labels']);
    }

    public function test_custom_range_weekly_when_gt_30_days(): void
    {
        $from = Carbon::now('UTC')->subDays(59)->format('Y-m-d');
        $to = Carbon::now('UTC')->format('Y-m-d');
        $response = $this->actingAs($this->admin)->getJson(
            route('admin.analytics.data', compact('from', 'to'))
        );
        $response->assertOk();
        $json = $response->json();
        $this->assertLessThan(60, count($json['labels']));
    }

    public function test_invalid_date_range_returns_422(): void
    {
        $from = Carbon::now('UTC')->format('Y-m-d');
        $to = Carbon::now('UTC')->subDays(1)->format('Y-m-d');
        $response = $this->actingAs($this->admin)->getJson(
            route('admin.analytics.data', compact('from', 'to'))
        );
        $response->assertStatus(422);
    }

    public function test_empty_quiz_data_returns_empty_performance_table(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.analytics.data', ['range' => '30d']));
        $response->assertOk();
        $json = $response->json();
        $this->assertEquals([], $json['performanceTable']);
    }

    public function test_kpis_total_users_matches_db_count(): void
    {
        User::factory()->count(3)->create();
        $response = $this->actingAs($this->admin)->getJson(route('admin.analytics.data', ['range' => '30d']));
        $response->assertOk();
        $json = $response->json();
        $this->assertEquals(User::count(), $json['kpis']['totalUsers']);
    }
}
