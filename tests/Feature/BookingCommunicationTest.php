<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;

class BookingCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_details_are_hidden_for_pending_bookings()
    {
        $seeker = User::factory()->create(['current_role' => 'seeker']);
        $provider = User::factory()->create(['current_role' => 'provider', 'phone' => '123456789', 'whatsapp_number' => '987654321']);
        $service = Service::factory()->create(['user_id' => $provider->id]);
        $booking = Booking::create([
            'service_id' => $service->id,
            'seeker_id' => $seeker->id,
            'provider_id' => $provider->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($seeker)->getJson('/api/my-requests');

        $response->assertStatus(200)
            ->assertJsonMissing(['phone' => '123456789'])
            ->assertJsonMissing(['whatsapp_number' => '987654321']);
    }

    public function test_contact_details_are_revealed_for_accepted_bookings()
    {
        $seeker = User::factory()->create(['current_role' => 'seeker']);
        $provider = User::factory()->create(['current_role' => 'provider', 'phone' => '123456789', 'whatsapp_number' => '987654321']);
        $service = Service::factory()->create(['user_id' => $provider->id]);
        $booking = Booking::create([
            'service_id' => $service->id,
            'seeker_id' => $seeker->id,
            'provider_id' => $provider->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($seeker)->getJson('/api/my-requests');

        $response->assertStatus(200)
            ->assertJsonFragment(['phone' => '123456789'])
            ->assertJsonFragment(['whatsapp_number' => '987654321'])
            ->assertJsonFragment(['whatsapp_link' => 'https://wa.me/987654321']);
    }

    public function test_only_provider_can_accept_booking()
    {
        $seeker = User::factory()->create(['current_role' => 'seeker']);
        $provider = User::factory()->create(['current_role' => 'provider']);
        $otherUser = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $provider->id]);
        $booking = Booking::create([
            'service_id' => $service->id,
            'seeker_id' => $seeker->id,
            'provider_id' => $provider->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($otherUser)->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'accepted']);

        $response->assertStatus(403);
    }
}
