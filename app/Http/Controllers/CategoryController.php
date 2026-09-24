<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display categories.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(
                'category_name',
                'like',
                '%' . $search . '%'
            );
        }

        // Status
        if ($request->status === 'active') {
            $query->where('is_active', true);
        }

        if ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $categories = $query
            ->orderBy('category_name')
            ->paginate(10)
            ->withQueryString();

        return view(
            'categories.index',
            compact('categories')
        );
    }

    /**
     * Kept for route compatibility.
     *
     * Creation now happens using the modal
     * on categories.index.
     */
    public function create()
    {
        return redirect()
            ->route('categories.index');
    }

    /**
     * Store category.
     */
    public function store(
        Request $request
    ) {
        $validated =
            $request->validate([
                'category_name' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:categories,category_name',
                ],
            ]);

        $category =
            Category::create([
                'category_name' =>
                    trim(
                        $validated[
                            'category_name'
                        ]
                    ),

                'is_active' =>
                    true,
            ]);

        ActivityLog::create([
            'user_id' =>
                auth()->id(),

            'module' =>
                'CATEGORY',

            'action' =>
                'CREATE',

            'description' =>
                "Created category '{$category->category_name}'.",

            'reference_type' =>
                'Category',

            'reference_id' =>
                $category->category_id,

            'created_at' =>
                now(),
        ]);

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Category created successfully.'
            );
    }

    /**
     * Kept for route compatibility.
     *
     * Editing now happens using the modal
     * on categories.index.
     */
    public function edit(
        Category $category
    ) {
        return redirect()
            ->route('categories.index');
    }

    /**
     * Update category.
     */
    public function update(
        Request $request,
        Category $category
    ) {
        $validated =
            $request->validate([
                'category_name' => [
                    'required',
                    'string',
                    'max:100',

                    Rule::unique(
                        'categories',
                        'category_name'
                    )->ignore(
                        $category->category_id,
                        'category_id'
                    ),
                ],
            ]);

        $oldName =
            $category->category_name;

        $category->update([
            'category_name' =>
                trim(
                    $validated[
                        'category_name'
                    ]
                ),
        ]);

        ActivityLog::create([
            'user_id' =>
                auth()->id(),

            'module' =>
                'CATEGORY',

            'action' =>
                'UPDATE',

            'description' =>
                "Updated category from '{$oldName}' to '{$category->category_name}'.",

            'reference_type' =>
                'Category',

            'reference_id' =>
                $category->category_id,

            'created_at' =>
                now(),
        ]);

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Category updated successfully.'
            );
    }

    /**
     * Archive category.
     */
    public function deactivate(
        Category $category
    ) {
        if (!$category->is_active) {
            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    'This category is already archived.'
                );
        }

        $category->update([
            'is_active' =>
                false,
        ]);

        ActivityLog::create([
            'user_id' =>
                auth()->id(),

            'module' =>
                'CATEGORY',

            'action' =>
                'UPDATE',

            'description' =>
                "Archived category '{$category->category_name}'.",

            'reference_type' =>
                'Category',

            'reference_id' =>
                $category->category_id,

            'created_at' =>
                now(),
        ]);

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Category archived successfully.'
            );
    }

    /**
     * Restore category.
     */
    public function activate(
        Category $category
    ) {
        if ($category->is_active) {
            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    'This category is already active.'
                );
        }

        $category->update([
            'is_active' =>
                true,
        ]);

        ActivityLog::create([
            'user_id' =>
                auth()->id(),

            'module' =>
                'CATEGORY',

            'action' =>
                'UPDATE',

            'description' =>
                "Restored category '{$category->category_name}'.",

            'reference_type' =>
                'Category',

            'reference_id' =>
                $category->category_id,

            'created_at' =>
                now(),
        ]);

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Category restored successfully.'
            );
    }
}