<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends AdminController
{
    public function index(Request $request)
    {
        $query = User::query()->latest();

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%'.$this->escapeLike($search).'%';
            $query->where(function ($userQuery) use ($like) {
                $userQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('whatsapp_number', 'like', $like);
            });
        }

        if ($request->filled('role')) {
            $query->where('current_role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->has('is_admin') && $request->input('is_admin') !== '') {
            $query->where('is_admin', filter_var($request->input('is_admin'), FILTER_VALIDATE_BOOL));
        }

        if ($request->filled('verification_level')) {
            $level = (string) $request->input('verification_level');

            if ($level === 'verified') {
                $query->whereNotNull('email_verified_at')
                    ->where(function ($verifiedQuery) {
                        $verifiedQuery->whereNotNull('phone')
                            ->orWhereNotNull('whatsapp_number');
                    });
            } elseif ($level === 'basic') {
                $query->whereNotNull('email_verified_at')
                    ->where(function ($basicQuery) {
                        $basicQuery->whereNull('phone')
                            ->whereNull('whatsapp_number');
                    });
            } elseif ($level === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        return response()->json(
            $query->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function show(User $user)
    {
        $user->loadCount(['services', 'bookingsAsSeeker', 'bookingsAsProvider', 'adminActivityLogs']);

        return response()->json([
            'user' => $user,
            'recent_services' => $user->services()->with('category:id,name,slug')->latest()->limit(5)->get(),
            'recent_bookings_as_seeker' => Booking::with(['service:id,title', 'provider:id,name,email'])
                ->where('seeker_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(),
            'recent_bookings_as_provider' => Booking::with(['service:id,title', 'seeker:id,name,email'])
                ->where('provider_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(),
            'recent_reviews' => Review::with(['service:id,title', 'seeker:id,name', 'provider:id,name'])
                ->where(function ($reviewQuery) use ($user) {
                    $reviewQuery->where('provider_id', $user->id)
                        ->orWhere('seeker_id', $user->id);
                })
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                User::STATUS_ACTIVE,
                User::STATUS_SUSPENDED,
                User::STATUS_BANNED,
            ])],
            'reason' => 'nullable|string|max:2000',
        ]);

        if ($user->is($request->user()) && $validated['status'] !== User::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'You cannot change your own admin account status.',
            ], 422);
        }

        $previousStatus = $user->status;
        $user->update([
            'status' => $validated['status'],
        ]);

        $this->logActivity(
            $request,
            'user.status_updated',
            $user,
            $validated['reason'] ?? null,
            [
                'from' => $previousStatus,
                'to' => $user->status,
            ]
        );

        return response()->json($user->fresh());
    }

    public function updateAdminAccess(Request $request, User $user)
    {
        $validated = $request->validate([
            'is_admin' => 'required|boolean',
            'reason' => 'nullable|string|max:2000',
        ]);

        $newValue = (bool) $validated['is_admin'];

        if ($user->is($request->user()) && ! $newValue) {
            return response()->json([
                'message' => 'You cannot revoke your own admin access.',
            ], 422);
        }

        $previousValue = (bool) $user->is_admin;
        $user->update([
            'is_admin' => $newValue,
        ]);

        $this->logActivity(
            $request,
            'user.admin_access_updated',
            $user,
            $validated['reason'] ?? null,
            [
                'from' => $previousValue,
                'to' => $newValue,
            ]
        );

        return response()->json($user->fresh());
    }
}
