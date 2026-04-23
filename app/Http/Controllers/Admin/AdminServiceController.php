<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminServiceController extends AdminController
{
    public function index(Request $request)
    {
        $query = Service::with(['user:id,name,email,status,current_role', 'category:id,name,slug'])
            ->withCount('reviews')
            ->latest();

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%'.$this->escapeLike($search).'%';
            $query->where(function ($serviceQuery) use ($like) {
                $serviceQuery
                    ->where('title', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $like))
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', $like));
            });
        }

        foreach (['status', 'category_id', 'user_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('provider_id')) {
            $query->where('user_id', $request->input('provider_id'));
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.$this->escapeLike((string) $request->input('location')).'%');
        }

        return response()->json(
            $query->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function show(Service $service)
    {
        $service->load(['user', 'category'])
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return response()->json($service);
    }

    public function updateStatus(Request $request, Service $service)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Service::STATUS_PENDING,
                Service::STATUS_APPROVED,
                Service::STATUS_HIDDEN,
                Service::STATUS_REJECTED,
            ])],
            'moderation_note' => 'nullable|string|max:2000',
            'reason' => 'nullable|string|max:2000',
        ]);

        $previousStatus = $service->status;

        $service->update([
            'status' => $validated['status'],
            'moderation_note' => $validated['moderation_note'] ?? ($validated['reason'] ?? null),
        ]);

        $this->logActivity(
            $request,
            'service.status_updated',
            $service,
            $validated['reason'] ?? null,
            [
                'from' => $previousStatus,
                'to' => $service->status,
            ]
        );

        return response()->json($service->fresh(['user', 'category']));
    }

    public function destroy(Request $request, Service $service)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        $this->logActivity(
            $request,
            'service.deleted',
            $service,
            $validated['reason'] ?? null,
            [
                'title' => $service->title,
            ]
        );

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully.',
        ]);
    }
}
