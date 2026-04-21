<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function indexByService($serviceId)
    {

        $reviews = Review::with('seeker:id,name')
            ->where('service_id', $serviceId)
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'seeker' => [
                        'id' => $review->seeker?->id,
                        'name' => $review->seeker?->name,
                    ],
                ];
            });

        return response()->json($reviews);
    }

    public function indexByProvider($providerId)
    {

        $reviews = Review::with('seeker:id,name')
            ->where('provider_id', $providerId)
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'seeker' => [
                        'id' => $review->seeker?->id,
                        'name' => $review->seeker?->name,
                    ],
                ];
            });

        return response()->json($reviews);
    }

    public function store(Request $request)
    {

        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $booking = Booking::with('service')->findOrFail($request->booking_id);

        if ($booking->seeker_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'Booking must be completed before reviewing.'], 400);
        }

        $exists = Review::where('booking_id', $booking->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Review already submitted.'], 409);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'service_id' => $booking->service_id,
            'provider_id' => $booking->provider_id,
            'seeker_id' => $booking->seeker_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review, 201);
    }
}
