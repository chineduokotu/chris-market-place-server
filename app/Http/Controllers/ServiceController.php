<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['user', 'category']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $services = $query->latest()->paginate(12);
        return response()->json($services);
    }

    public function show($id)
    {
        $service = Service::with(['user', 'category'])->findOrFail($id);
        return response()->json($service);
    }

    public function store(Request $request, CloudinaryService $cloudinary)
    {
        if ($request->user()->current_role !== 'provider') {
            return response()->json(['message' => 'Only providers can create services'], 403);
        }

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $serviceData = $request->only(['category_id', 'title', 'description']);
        $serviceData['price'] = 0; // Set default price to 0 since we're removing it from UI

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $upload = $cloudinary->uploadServiceImage($request->file('image'));
                $serviceData['image'] = $upload['url'];
                $serviceData['image_public_id'] = $upload['public_id'];
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Image upload failed'], 500);
            }
        }

        $service = $request->user()->services()->create($serviceData);

        return response()->json($service->load(['user', 'category']), 201);
    }

    public function update(Request $request, $id, CloudinaryService $cloudinary)
    {
        $service = Service::findOrFail($id);
        $oldPublicId = $service->image_public_id;

        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $updateData = $request->only(['category_id', 'title', 'description']);

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $upload = $cloudinary->uploadServiceImage($request->file('image'));
                $updateData['image'] = $upload['url'];
                $updateData['image_public_id'] = $upload['public_id'];
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Image upload failed'], 500);
            }
        }

        $service->update($updateData);

        if ($request->hasFile('image')) {
            try {
                $cloudinary->deleteImage($oldPublicId);
            } catch (\Throwable $e) {
                // Best-effort cleanup; do not fail the request.
            }
        }

        return response()->json($service->load(['user', 'category']));
    }

    public function destroy(Request $request, $id, CloudinaryService $cloudinary)
    {
        $service = Service::findOrFail($id);

        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($service->image_public_id) {
            try {
                $cloudinary->deleteImage($service->image_public_id);
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
}
