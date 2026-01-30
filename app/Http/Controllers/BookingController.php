<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        if ($request->user()->current_role !== 'seeker') {
            return response()->json(['message' => 'Only seekers can create bookings'], 403);
        }

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'notes' => 'nullable|string',
        ]);

        $service = Service::findOrFail($request->service_id);

        if ($service->user_id === $request->user()->id) {
            return response()->json(['message' => 'Cannot book your own service'], 400);
        }

        $booking = Booking::create([
            'service_id' => $service->id,
            'seeker_id' => $request->user()->id,
            'provider_id' => $service->user_id,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return response()->json($booking->load(['service', 'seeker', 'provider']), 201);
    }

    public function myRequests(Request $request)
    {
        $bookings = Booking::where('seeker_id', $request->user()->id)
            ->with(['service.category', 'provider', 'service.user'])
            ->latest()
            ->get()
            ->map(function ($booking) {
                if ($booking->status !== 'accepted') {
                    $booking->provider->makeHidden(['phone', 'whatsapp_number']);
                }
                return $booking;
            });

        return response()->json($bookings);
    }

    public function myJobs(Request $request)
    {
        $bookings = Booking::where('provider_id', $request->user()->id)
            ->with(['service.category', 'seeker', 'service.user'])
            ->latest()
            ->get();
        return response()->json($bookings);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        Gate::authorize('update', $booking);

        $request->validate([
            'status' => 'required|in:accepted,rejected,completed',
        ]);

        $newStatus = $request->status;
        $user = $request->user();

        if ($newStatus === 'accepted' || $newStatus === 'rejected') {
            if ($booking->status !== 'pending') {
                return response()->json(['message' => 'Booking is not pending'], 400);
            }
        }

        if ($newStatus === 'completed') {
            if ($booking->status !== 'accepted') {
                return response()->json(['message' => 'Booking must be accepted first'], 400);
            }
        }

        $booking->update(['status' => $newStatus]);

        $booking->load(['service', 'seeker', 'provider']);

        if ($booking->status !== 'accepted') {
            $booking->provider->makeHidden(['phone', 'whatsapp_number']);
        }

        return response()->json($booking);
    }
}
