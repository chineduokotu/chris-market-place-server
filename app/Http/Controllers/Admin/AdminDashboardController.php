<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminActivityLog;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;

class AdminDashboardController extends AdminController
{
    public function index()
    {
        return response()->json([
            'metrics' => [
                'users' => [
                    'total' => User::count(),
                    'admins' => User::where('is_admin', true)->count(),
                    'providers' => User::where('current_role', 'provider')->count(),
                    'seekers' => User::where('current_role', 'seeker')->count(),
                    'active' => User::where('status', User::STATUS_ACTIVE)->count(),
                    'suspended' => User::where('status', User::STATUS_SUSPENDED)->count(),
                    'banned' => User::where('status', User::STATUS_BANNED)->count(),
                ],
                'services' => [
                    'total' => Service::count(),
                    'approved' => Service::where('status', Service::STATUS_APPROVED)->count(),
                    'pending' => Service::where('status', Service::STATUS_PENDING)->count(),
                    'hidden' => Service::where('status', Service::STATUS_HIDDEN)->count(),
                    'rejected' => Service::where('status', Service::STATUS_REJECTED)->count(),
                ],
                'bookings' => [
                    'total' => Booking::count(),
                    'pending' => Booking::where('status', 'pending')->count(),
                    'accepted' => Booking::where('status', 'accepted')->count(),
                    'rejected' => Booking::where('status', 'rejected')->count(),
                    'completed' => Booking::where('status', 'completed')->count(),
                ],
                'reviews' => [
                    'total' => Review::count(),
                    'visible' => Review::where('status', Review::STATUS_VISIBLE)->count(),
                    'hidden' => Review::where('status', Review::STATUS_HIDDEN)->count(),
                    'flagged' => Review::where('status', Review::STATUS_FLAGGED)->count(),
                ],
            ],
            'recent' => [
                'users' => User::latest()->limit(5)->get(),
                'services' => Service::with(['user:id,name', 'category:id,name,slug'])
                    ->latest()
                    ->limit(5)
                    ->get(),
                'activity' => AdminActivityLog::with('admin:id,name,email')
                    ->latest()
                    ->limit(10)
                    ->get(),
            ],
        ]);
    }
}
