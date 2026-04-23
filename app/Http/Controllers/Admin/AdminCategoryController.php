<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCategoryController extends AdminController
{
    public function index(Request $request)
    {
        $query = Category::withCount('services')->latest();

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%'.$this->escapeLike($search).'%';
            $query->where(function ($categoryQuery) use ($like) {
                $categoryQuery
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('group_name', 'like', $like)
                    ->orWhere('icon', 'like', $like);
            });
        }

        return response()->json(
            $query->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'group_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $category = Category::create($validated);

        $this->logActivity($request, 'category.created', $category, null, [
            'name' => $category->name,
        ]);

        return response()->json($category, 201);
    }

    public function show(Category $category)
    {
        $category->loadCount('services');

        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
            'group_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:2000',
        ]);

        $previous = $category->only(['name', 'slug', 'group_name', 'icon']);
        $category->update(collect($validated)->except('reason')->all());

        $this->logActivity(
            $request,
            'category.updated',
            $category,
            $validated['reason'] ?? null,
            [
                'before' => $previous,
                'after' => $category->only(['name', 'slug', 'group_name', 'icon']),
            ]
        );

        return response()->json($category->fresh());
    }

    public function destroy(Request $request, Category $category)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        if ($category->services()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that still has services.',
            ], 422);
        }

        $this->logActivity($request, 'category.deleted', $category, $validated['reason'] ?? null, [
            'name' => $category->name,
            'slug' => $category->slug,
        ]);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
