<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with 3-level hierarchy
     */
    public function index()
    {
        // Load top-level categories with subcategories and sub-subcategories
        $categories = Category::where('level', 0)
            ->with(['subcategories.subcategories.products', 'subcategories.products'])
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create(Request $request)
    {
        $parentId = $request->query('parent_id');
        $parentCategory = $parentId ? Category::find($parentId) : null;

        // Get all categories that can be parents (level 0 and level 1)
        $topLevelCategories = Category::where('level', 0)->orderBy('name')->get();
        $subCategories = Category::where('level', 1)->with('parent')->orderBy('name')->get();

        return view('admin.categories.create', compact('topLevelCategories', 'subCategories', 'parentId', 'parentCategory'));
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) {
                    // Only allow level 0 or level 1 categories as parents
                    $query->where('level', '<', 2);
                }),
            ],
        ]);

        // Calculate level based on parent
        $level = 0;
        if ($validated['parent_id']) {
            $parent = Category::find($validated['parent_id']);
            $level = $parent->level + 1;

            // Prevent creating beyond level 2
            if ($level > 2) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Cannot create more than 3 levels of categories.');
            }
        }

        $validated['level'] = $level;
        Category::create($validated);

        $typeNames = ['Category', 'Subcategory', 'Sub-subcategory'];
        $message = $typeNames[$level] . ' created successfully.';

        return redirect()->route('admin.categories.index')->with('success', $message);
    }

    /**
     * Show the form for editing a category
     */
    public function edit(Category $category)
    {
        $category->load('parent.parent', 'subcategories');

        // Get potential parents based on current level
        // A category can move to a different parent at the same or higher level
        $potentialParents = collect();

        if ($category->level > 0) {
            // Can become a top-level category or move under another parent
            if ($category->level === 1) {
                // Sub-category can become top-level only
                $potentialParents = collect(); // No parent options, just none
            } else {
                // Sub-sub-category can move to another sub-category
                $potentialParents = Category::where('level', 1)
                    ->where('id', '!=', $category->parent_id)
                    ->with('parent')
                    ->orderBy('name')
                    ->get();
            }
        }

        $topLevelCategories = Category::where('level', 0)
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        $subCategories = Category::where('level', 1)
            ->where('id', '!=', $category->id)
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'topLevelCategories', 'subCategories'));
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id'),
                function ($attribute, $value, $fail) use ($category) {
                    if ($value === $category->id) {
                        $fail('A category cannot be its own parent.');
                    }

                    // Check if the new parent would create a circular reference
                    if ($value) {
                        $newParent = Category::find($value);
                        $current = $newParent;
                        while ($current) {
                            if ($current->id === $category->id) {
                                $fail('Cannot set a descendant as the parent.');
                                return;
                            }
                            $current = $current->parent;
                        }

                        // Check level constraints
                        if ($newParent->level >= 2) {
                            $fail('Cannot add children to a sub-subcategory.');
                            return;
                        }
                    }
                },
            ],
        ]);

        // Calculate new level if parent changed
        if (array_key_exists('parent_id', $validated)) {
            if ($validated['parent_id']) {
                $parent = Category::find($validated['parent_id']);
                $validated['level'] = $parent->level + 1;
            } else {
                $validated['level'] = 0;
            }

            // Update children levels if this category's level changed
            if ($validated['level'] !== $category->level) {
                $this->updateChildrenLevels($category, $validated['level']);
            }
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        // Check if category has subcategories
        if ($category->subcategories()->count() > 0) {
            $childType = $category->level === 0 ? 'subcategories' : 'sub-subcategories';
            return redirect()->route('admin.categories.index')
                ->with('error', "Cannot delete category with {$childType}. Delete them first.");
        }

        // Check if category has products (only level 2 should have products)
        if ($category->products()->count() > 0) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Cannot delete category with products. Delete or reassign products first.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }

    /**
     * Recursively update children levels when a category's level changes
     */
    private function updateChildrenLevels(Category $category, int $newParentLevel): void
    {
        foreach ($category->subcategories as $child) {
            $child->level = $newParentLevel + 1;
            $child->save();
            $this->updateChildrenLevels($child, $child->level);
        }
    }
}
