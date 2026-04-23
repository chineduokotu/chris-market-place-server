<?php

namespace App\Http\Controllers\Admin;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReviewController extends AdminController
{
    public function index(Request $request)
    {
        $query = Review::with([
            'service:id,title',
            'provider:id,name,email,status',
            'seeker:id,name,email,status',
        ])->latest();

        foreach (['status', 'provider_id', 'service_id', 'rating'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%'.$this->escapeLike($search).'%';
            $query->where(function ($reviewQuery) use ($like) {
                $reviewQuery
                    ->where('comment', 'like', $like)
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('title', 'like', $like))
                    ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('name', 'like', $like))
                    ->orWhereHas('seeker', fn ($seekerQuery) => $seekerQuery->where('name', 'like', $like));
            });
        }

        return response()->json(
            $query->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function show(Review $review)
    {
        return response()->json($review->load(['service', 'provider', 'seeker', 'booking']));
    }

    public function updateStatus(Request $request, Review $review)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Review::STATUS_VISIBLE,
                Review::STATUS_HIDDEN,
                Review::STATUS_FLAGGED,
            ])],
            'moderation_note' => 'nullable|string|max:2000',
            'reason' => 'nullable|string|max:2000',
        ]);

        $previousStatus = $review->status;

        $review->update([
            'status' => $validated['status'],
            'moderation_note' => $validated['moderation_note'] ?? ($validated['reason'] ?? null),
        ]);

        $this->logActivity(
            $request,
            'review.status_updated',
            $review,
            $validated['reason'] ?? null,
            [
                'from' => $previousStatus,
                'to' => $review->status,
            ]
        );

        return response()->json($review->fresh(['service', 'provider', 'seeker']));
    }
}
