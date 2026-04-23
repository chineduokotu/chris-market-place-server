<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use Illuminate\Http\Request;

class AdminBookingController extends AdminController
{
    public function index(Request $request)
    {
        $query = Booking::with([
            'service:id,title',
            'seeker:id,name,email,status',
            'provider:id,name,email,status',
        ])->latest();

        foreach (['status', 'service_id', 'seeker_id', 'provider_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%'.$this->escapeLike($search).'%';
            $query->where(function ($bookingQuery) use ($like) {
                $bookingQuery
                    ->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('title', 'like', $like))
                    ->orWhereHas('seeker', fn ($seekerQuery) => $seekerQuery->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        return response()->json(
            $query->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function show(Booking $booking)
    {
        return response()->json(
            $booking->load(['service.category', 'service.user', 'seeker', 'provider'])
        );
    }

    public function updateAdminNote(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:5000',
            'reason' => 'nullable|string|max:2000',
        ]);

        $booking->update([
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        $this->logActivity(
            $request,
            'booking.admin_note_updated',
            $booking,
            $validated['reason'] ?? null,
            [
                'has_note' => filled($booking->admin_note),
            ]
        );

        return response()->json($booking->fresh(['service', 'seeker', 'provider']));
    }
}
