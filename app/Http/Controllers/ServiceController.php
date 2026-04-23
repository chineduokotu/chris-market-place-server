<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query()
            ->where('services.status', Service::STATUS_APPROVED)
            ->leftJoin('users', 'services.user_id', '=', 'users.id')
            ->leftJoin('categories', 'services.category_id', '=', 'categories.id')
            ->select([
                'services.id',
                'services.user_id',
                'services.category_id',
                'services.title',
                'services.description',
                'services.location',
                'services.price',
                'services.image',
                'services.image_public_id',
                'services.created_at',
                'services.updated_at',
                'users.name as provider_name',
                'users.current_role as provider_current_role',
                'users.email_verified_at as provider_email_verified_at',
                'users.phone as provider_phone',
                'users.whatsapp_number as provider_whatsapp_number',
                'categories.id as category_ref_id',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'categories.group_name as category_group_name',
                'categories.icon as category_icon',
            ])
            ->selectSub(
                Review::query()
                    ->selectRaw('AVG(rating)')
                    ->whereColumn('reviews.service_id', 'services.id'),
                'reviews_avg_rating'
            )
            ->selectSub(
                Review::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('reviews.service_id', 'services.id'),
                'reviews_count'
            );

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(function ($searchQuery) use ($like) {
                $searchQuery
                    ->where('services.title', 'like', $like)
                    ->orWhere('services.description', 'like', $like)
                    ->orWhere('services.location', 'like', $like)
                    ->orWhere('categories.name', 'like', $like)
                    ->orWhere('categories.slug', 'like', $like)
                    ->orWhere('users.name', 'like', $like);
            });
        }

        $categoryFilter = $request->input('category_id', $request->input('category'));
        if (! is_null($categoryFilter) && $categoryFilter !== '') {
            if (is_numeric($categoryFilter)) {
                $query->where('services.category_id', (int) $categoryFilter);
            } else {
                $normalizedCategory = strtolower(trim((string) $categoryFilter));
                $query->where(function ($categoryQuery) use ($normalizedCategory) {
                    $categoryQuery
                        ->whereRaw('LOWER(categories.name) = ?', [$normalizedCategory])
                        ->orWhereRaw('LOWER(categories.slug) = ?', [$normalizedCategory]);
                });
            }
        }

        $locationFilter = trim((string) $request->input('location', ''));
        if ($locationFilter !== '') {
            $locationLike = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $locationFilter).'%';
            $query->where('services.location', 'like', $locationLike);
        }

        if ($request->input('verified') == 1) {
            $query->whereNotNull('users.email_verified_at')
                ->where(function ($verifiedQuery) {
                    $verifiedQuery
                        ->whereNotNull('users.phone')
                        ->orWhereNotNull('users.whatsapp_number');
                });
        }

        if ($request->has('provider_id')) {
            $query->where('services.user_id', $request->provider_id);
        }

        $perPage = max(1, min((int) $request->input('per_page', 12), 48));
        $sort = $request->input('sort', 'recent');
        if ($sort === 'price_low_high') {
            $query->orderBy('services.price', 'asc')->orderByDesc('services.id');
        } elseif ($sort === 'price_high_low') {
            $query->orderBy('services.price', 'desc')->orderByDesc('services.id');
        } else {
            $query->latest('services.created_at');
        }

        $services = $query->paginate($perPage);
        $services->getCollection()->transform(function ($service) {
            $emailVerifiedAt = $service->provider_email_verified_at;

            $provider = new User();
            $provider->forceFill([
                'id' => $service->user_id,
                'name' => $service->provider_name,
                'current_role' => $service->provider_current_role,
                'email_verified_at' => $emailVerifiedAt,
                'phone' => $service->provider_phone,
                'whatsapp_number' => $service->provider_whatsapp_number,
            ]);

            $serviceCategory = null;
            if ($service->category_ref_id) {
                $serviceCategory = new Category();
                $serviceCategory->forceFill([
                    'id' => $service->category_ref_id,
                    'name' => $service->category_name,
                    'slug' => $service->category_slug,
                    'group_name' => $service->category_group_name,
                    'icon' => $service->category_icon,
                ]);
            }

            $service->setRelation('user', $provider);
            $service->setRelation('category', $serviceCategory);
            $service->rating = $service->reviews_avg_rating ? (float) $service->reviews_avg_rating : null;
            $service->review_count = $service->reviews_count ?? 0;

            unset(
                $service->provider_name,
                $service->provider_current_role,
                $service->provider_email_verified_at,
                $service->provider_phone,
                $service->provider_whatsapp_number,
                $service->category_ref_id,
                $service->category_name,
                $service->category_slug,
                $service->category_group_name,
                $service->category_icon
            );

            return $service;
        });

        return response()->json($services);
    }

    public function show($id)
    {
        $serviceQuery = Service::where('status', Service::STATUS_APPROVED)->with(['user', 'category']);
        $serviceQuery->withAvg('reviews', 'rating')->withCount('reviews');
        $service = $serviceQuery->findOrFail($id);
        $service->rating = $service->reviews_avg_rating ? (float) $service->reviews_avg_rating : null;
        $service->review_count = $service->reviews_count ?? 0;

        return response()->json($service);
    }

    public function store(Request $request)
    {
        if ($request->user()->current_role !== 'provider') {
            return response()->json(['message' => 'Only providers can create services'], 403);
        }

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $serviceData = $request->only(['category_id', 'title', 'description', 'price']);
        $serviceData['location'] = trim((string) $request->input('location', '')) ?: null;

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $upload = $this->uploadServiceImage($request->file('image'));
                $serviceData['image'] = $upload['url'];
                $serviceData['image_public_id'] = $upload['public_id'] ?? null;
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Image upload failed'], 500);
            }
        }

        $service = $request->user()->services()->create($serviceData);

        return response()->json($service->load(['user', 'category']), 201);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $oldImage = $service->image;
        $oldPublicId = $service->image_public_id;

        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $updateData = $request->only(['category_id', 'title', 'description', 'price']);
        if ($request->has('location')) {
            $updateData['location'] = trim((string) $request->input('location', '')) ?: null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $upload = $this->uploadServiceImage($request->file('image'));
                $updateData['image'] = $upload['url'];
                $updateData['image_public_id'] = $upload['public_id'] ?? null;
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Image upload failed'], 500);
            }
        }

        $service->update($updateData);

        if ($request->hasFile('image')) {
            try {
                $this->deleteStoredImage($oldImage, $oldPublicId);
            } catch (\Throwable $e) {
                // Best-effort cleanup; do not fail the request.
            }
        }

        return response()->json($service->load(['user', 'category']));
    }

    public function destroy(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($service->image || $service->image_public_id) {
            try {
                $this->deleteStoredImage($service->image, $service->image_public_id);
            } catch (\Throwable $e) {
                // Best-effort cleanup; do not fail the request.
            }
        }

        $service->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }

    public function myServices(Request $request)
    {
        $services = $request->user()->services()->with('category')->latest()->get();

        return response()->json($services);
    }

    private function uploadServiceImage($file): array
    {
        if ($this->hasCloudinaryConfig()) {
            $cloudinary = app(CloudinaryService::class);

            return $cloudinary->uploadServiceImage($file);
        }

        $path = $file->store('services', 'public');

        return [
            'url' => Storage::disk('public')->url($path),
            'public_id' => null,
        ];
    }

    private function deleteStoredImage(?string $imageUrl, ?string $publicId): void
    {
        if ($publicId && $this->hasCloudinaryConfig()) {
            app(CloudinaryService::class)->deleteImage($publicId);

            return;
        }

        if (! $imageUrl) {
            return;
        }

        $storagePrefix = Storage::disk('public')->url('');
        if (str_starts_with($imageUrl, $storagePrefix)) {
            $relativePath = ltrim(substr($imageUrl, strlen($storagePrefix)), '/');
            if ($relativePath !== '') {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }

    private function hasCloudinaryConfig(): bool
    {
        return filled(config('cloudinary.cloud_name'))
            && filled(config('cloudinary.api_key'))
            && filled(config('cloudinary.api_secret'));
    }
}
