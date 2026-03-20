<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['user', 'category']);

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where(function ($searchQuery) use ($like) {
                $searchQuery
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhereHas('category', function ($categoryQuery) use ($like) {
                        $categoryQuery->where('name', 'like', $like);
                    })
                    ->orWhereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where('name', 'like', $like);
                    });
            });
        }

        $categoryFilter = $request->input('category_id', $request->input('category'));
        if (!is_null($categoryFilter) && $categoryFilter !== '') {
            if (is_numeric($categoryFilter)) {
                $query->where('category_id', (int) $categoryFilter);
            } else {
                $normalizedCategory = strtolower(trim((string) $categoryFilter));
                $query->whereHas('category', function ($categoryQuery) use ($normalizedCategory) {
                    $categoryQuery
                        ->whereRaw('LOWER(name) = ?', [$normalizedCategory])
                        ->orWhereRaw('LOWER(slug) = ?', [$normalizedCategory]);
                });
            }
        }

        $locationFilter = trim((string) $request->input('location', ''));
        if ($locationFilter !== '') {
            $locationLike = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $locationFilter) . '%';
            $query->where('location', 'like', $locationLike);
        }

        if ($request->has('provider_id')) {
            $query->where('user_id', $request->provider_id);
        }
        if (Schema::hasTable('reviews')) {
            $query->withAvg('reviews', 'rating')->withCount('reviews');
        }

        $perPage = max(1, min((int) $request->input('per_page', 12), 48));
        $sort = $request->input('sort', 'recent');
        if ($sort === 'price_low_high') {
            $query->orderBy('price', 'asc')->orderByDesc('id');
        } elseif ($sort === 'price_high_low') {
            $query->orderBy('price', 'desc')->orderByDesc('id');
        } else {
            $query->latest();
        }

        $services = $query->paginate($perPage);
        if (Schema::hasTable('reviews')) {
            $services->getCollection()->transform(function ($service) {
                $service->rating = $service->reviews_avg_rating ? (float) $service->reviews_avg_rating : null;
                $service->review_count = $service->reviews_count ?? 0;
                return $service;
            });
        }
        return response()->json($services);
    }

    public function show($id)
    {
        $serviceQuery = Service::with(['user', 'category']);
        if (Schema::hasTable('reviews')) {
            $serviceQuery->withAvg('reviews', 'rating')->withCount('reviews');
        }
        $service = $serviceQuery->findOrFail($id);
        if (Schema::hasTable('reviews')) {
            $service->rating = $service->reviews_avg_rating ? (float) $service->reviews_avg_rating : null;
            $service->review_count = $service->reviews_count ?? 0;
        }
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
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $serviceData = $request->only(['category_id', 'title', 'description']);
        $serviceData['location'] = trim((string) $request->input('location', '')) ?: null;
        $serviceData['price'] = 0; // Set default price to 0 since we're removing it from UI

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
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $updateData = $request->only(['category_id', 'title', 'description']);
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

        if (!$imageUrl) {
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
