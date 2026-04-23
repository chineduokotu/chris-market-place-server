<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_admin_dashboard_returns_metrics(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->create(['current_role' => 'provider']);
        $seeker = User::factory()->create(['current_role' => 'seeker']);
        $category = Category::factory()->create();

        $approvedService = Service::factory()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'status' => Service::STATUS_APPROVED,
        ]);
        $hiddenService = Service::factory()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'status' => Service::STATUS_HIDDEN,
        ]);

        $pendingBooking = Booking::create([
            'service_id' => $approvedService->id,
            'seeker_id' => $seeker->id,
            'provider_id' => $provider->id,
            'status' => 'pending',
        ]);

        $completedBooking = Booking::create([
            'service_id' => $hiddenService->id,
            'seeker_id' => $seeker->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
        ]);

        Review::create([
            'booking_id' => $completedBooking->id,
            'service_id' => $hiddenService->id,
            'provider_id' => $provider->id,
            'seeker_id' => $seeker->id,
            'rating' => 4,
            'status' => Review::STATUS_HIDDEN,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('metrics.users.admins', 1)
            ->assertJsonPath('metrics.users.providers', 1)
            ->assertJsonPath('metrics.services.total', 2)
            ->assertJsonPath('metrics.services.hidden', 1)
            ->assertJsonPath('metrics.bookings.pending', 1)
            ->assertJsonPath('metrics.reviews.hidden', 1);
    }

    public function test_admin_can_suspend_user_and_audit_is_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$user->id}/status", [
                'status' => User::STATUS_SUSPENDED,
                'reason' => 'Repeated abuse reports',
            ])
            ->assertOk()
            ->assertJsonPath('status', User::STATUS_SUSPENDED);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => User::STATUS_SUSPENDED,
        ]);

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'target_type' => 'users',
            'target_id' => $user->id,
            'action' => 'user.status_updated',
        ]);
    }

    public function test_admin_can_hide_service_and_public_listing_excludes_it(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->create(['current_role' => 'provider']);
        $category = Category::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'status' => Service::STATUS_APPROVED,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/services/{$service->id}/status", [
                'status' => Service::STATUS_HIDDEN,
                'reason' => 'Policy violation',
            ])
            ->assertOk()
            ->assertJsonPath('status', Service::STATUS_HIDDEN);

        $publicResponse = $this->getJson('/api/services');

        $serviceIds = collect($publicResponse->json('data'))->pluck('id')->all();

        $this->assertNotContains($service->id, $serviceIds);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'target_type' => 'services',
            'target_id' => $service->id,
            'action' => 'service.status_updated',
        ]);
    }

    public function test_admin_cannot_delete_category_that_still_has_services(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->create(['current_role' => 'provider']);
        $category = Category::factory()->create();

        Service::factory()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertStatus(422);
    }

    public function test_admin_can_hide_review_and_public_endpoints_exclude_it(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->create(['current_role' => 'provider']);
        $seeker = User::factory()->create(['current_role' => 'seeker']);
        $category = Category::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
        ]);

        $booking = Booking::create([
            'service_id' => $service->id,
            'seeker_id' => $seeker->id,
            'provider_id' => $provider->id,
            'status' => 'completed',
        ]);

        $review = Review::create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'provider_id' => $provider->id,
            'seeker_id' => $seeker->id,
            'rating' => 5,
            'comment' => 'Great work',
            'status' => Review::STATUS_VISIBLE,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/reviews/{$review->id}/status", [
                'status' => Review::STATUS_HIDDEN,
                'reason' => 'Abusive content',
            ])
            ->assertOk()
            ->assertJsonPath('status', Review::STATUS_HIDDEN);

        $response = $this->getJson("/api/services/{$service->id}/reviews");
        $reviewIds = collect($response->json())->pluck('id')->all();

        $this->assertNotContains($review->id, $reviewIds);
    }

    public function test_admin_can_add_booking_note_and_view_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->create(['current_role' => 'provider']);
        $seeker = User::factory()->create(['current_role' => 'seeker']);
        $category = Category::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
        ]);

        $booking = Booking::create([
            'service_id' => $service->id,
            'seeker_id' => $seeker->id,
            'provider_id' => $provider->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/bookings/{$booking->id}/admin-note", [
                'admin_note' => 'Monitor this booking',
                'reason' => 'Escalated by support',
            ])
            ->assertOk()
            ->assertJsonPath('admin_note', 'Monitor this booking');

        $logsResponse = $this->actingAs($admin)
            ->getJson('/api/admin/audit-logs');

        $logsResponse->assertOk()
            ->assertJsonPath('data.0.action', 'booking.admin_note_updated');
    }
}
