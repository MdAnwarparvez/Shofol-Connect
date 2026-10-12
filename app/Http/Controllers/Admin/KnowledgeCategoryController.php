<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KnowledgeCategoryRequest;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeCategoryController extends Controller
{
    /**
     * Display a listing of knowledge categories.
     */
    public function index(Request $request): View
    {
        $search = $request->input('q');
        $module = $request->input('module_id') ?: $request->input('module');
        $status = $request->input('status');

        $query = KnowledgeCategory::query()
            ->with('shofolModule')
            ->withCount('knowledgeArticles')
            ->search($search)
            ->forModule($module)
            ->forStatus($status);

        $categories = $query->ordered()->paginate(15)->withQueryString();

        $modules = ShofolModule::ordered()->get();
        $totalCount = KnowledgeCategory::count();
        $activeCount = KnowledgeCategory::active()->count();
        $inactiveCount = KnowledgeCategory::where('is_active', false)->count();

        return view('admin.categories.index', compact(
            'categories',
            'modules',
            'totalCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        $modules = ShofolModule::active()->ordered()->get();

        return view('admin.categories.create', compact('modules'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(KnowledgeCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $category = KnowledgeCategory::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was created successfully under module \"{$category->shofolModule->name}\".");
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(KnowledgeCategory $category): View
    {
        $category->loadCount('knowledgeArticles');
        $modules = ShofolModule::ordered()->get();

        return view('admin.categories.edit', compact('category', 'modules'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(KnowledgeCategoryRequest $request, KnowledgeCategory $category): RedirectResponse
    {
        $validated = $request->validated();
        $oldName = $category->name;
        $newName = $validated['name'];

        // If category name changes, cascade update to existing articles referencing it
        if ($oldName !== $newName) {
            KnowledgeArticle::where('category', $oldName)->update(['category' => $newName]);
        }

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was updated successfully.");
    }

    /**
     * Toggle the active status of the specified category.
     */
    public function toggle(KnowledgeCategory $category): RedirectResponse
    {
        $category->update([
            'is_active' => !$category->is_active,
        ]);

        $statusLabel = $category->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Category \"{$category->name}\" was {$statusLabel} successfully.");
    }

    /**
     * Remove the specified category from storage if not referenced by knowledge articles.
     */
    public function destroy(KnowledgeCategory $category): RedirectResponse
    {
        $articleCount = $category->knowledgeArticles()->count();

        if ($articleCount > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', "Cannot delete category \"{$category->name}\" because {$articleCount} knowledge " . ($articleCount === 1 ? 'article is' : 'articles are') . " currently referencing it. Please reassign or delete the referenced articles first.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category \"{$name}\" was deleted successfully.");
    }
}
