<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'admin'])->get('/api/test-admin-only', fn () => response()->json([
            'message' => 'ok',
        ]));
    }

    public function test_admin_route_requires_authentication(): void
    {
        $this->getJson('/api/test-admin-only')
            ->assertStatus(401);
    }

    public function test_admin_route_rejects_non_admin_users(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user)->getJson('/api/test-admin-only')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }

    public function test_admin_route_allows_admin_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/test-admin-only')
            ->assertOk()
            ->assertJson([
                'message' => 'ok',
            ]);
    }

    public function test_user_factory_admin_state_sets_expected_defaults(): void
    {
        $admin = User::factory()->admin()->make();

        $this->assertTrue($admin->is_admin);
        $this->assertSame(User::STATUS_ACTIVE, $admin->status);
    }
}
