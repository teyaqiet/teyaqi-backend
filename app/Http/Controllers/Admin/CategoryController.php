<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\ActivityLogger;

class CategoryController extends Controller
{
    /**
     * Display categories list
     */
    public function index()
    {
        $categories = Category::withCount('questions')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show create page
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = Category::create([
            'name' => [
                'en' => $validated['name_en'],
                'am' => $validated['name_am'] ?? null,
            ],
            'description' => [
                'en' => $validated['description_en'] ?? null,
                'am' => $validated['description_am'] ?? null,
            ],
            'slug' => Str::slug($validated['name_en']),
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? '#6366f1',
            'image_url' => $validated['image_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Log activity
        ActivityLogger::log('created', 'Created new category: ' . $validated['name_en'], $category);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display category details with statistics
     */
    public function show(Category $category)
    {
        $category->loadCount('questions');

        // Paginate questions safely (fetches all available columns)
        $questions = $category->questions()
            ->latest()
            ->paginate(10);

        // Calculate all statistics in a single query
        $rawStats = $category->questions()
            ->selectRaw('
                COUNT(*) as total_questions,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_questions,
                SUM(CASE WHEN difficulty = "easy" THEN 1 ELSE 0 END) as easy_questions,
                SUM(CASE WHEN difficulty = "medium" THEN 1 ELSE 0 END) as medium_questions,
                SUM(CASE WHEN difficulty = "hard" THEN 1 ELSE 0 END) as hard_questions
            ')
            ->first();

        $stats = [
            'total_questions'  => (int) ($rawStats->total_questions ?? 0),
            'active_questions' => (int) ($rawStats->active_questions ?? 0),
            'easy_questions'   => (int) ($rawStats->easy_questions ?? 0),
            'medium_questions' => (int) ($rawStats->medium_questions ?? 0),
            'hard_questions'   => (int) ($rawStats->hard_questions ?? 0),
        ];

        return view('admin.categories.show', compact(
            'category',
            'questions',
            'stats'
        ));
    }

    /**
     * Show edit page
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update category
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => [
                'en' => $validated['name_en'],
                'am' => $validated['name_am'] ?? null,
            ],
            'description' => [
                'en' => $validated['description_en'] ?? null,
                'am' => $validated['description_am'] ?? null,
            ],
            'slug' => Str::slug($validated['name_en']),
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? '#6366f1',
            'image_url' => $validated['image_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Log activity
        ActivityLogger::log('updated', 'Updated category: ' . $validated['name_en'], $category);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete category
     */
    public function destroy(Category $category)
    {
        // Prevent deleting category with questions
        if ($category->questions()->exists()) {
            return back()->with(
                'error',
                'Cannot delete category because it contains questions.'
            );
        }

        $categoryName = is_array($category->name) ? ($category->name['en'] ?? 'Unknown') : $category->name;

        $category->delete();

        // Log activity
        ActivityLogger::log('deleted', 'Deleted category: ' . $categoryName);

        return back()->with(
            'success',
            'Category deleted successfully.'
        );
    }
}