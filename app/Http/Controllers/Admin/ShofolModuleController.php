<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShofolModuleRequest;
use App\Models\KnowledgeArticle;
use App\Models\ShofolModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShofolModuleController extends Controller
{
    /**
     * Display a listing of the Shofol modules.
     */
    public function index(Request $request): View
    {
        $query = ShofolModule::query()->withCount('knowledgeArticles');

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = strtolower(trim((string) $request->input('status')));
            if ($status === 'active' || $status === '1') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0') {
                $query->where('is_active', false);
            }
        }

        $modules = $query->ordered()->paginate(15)->withQueryString();

        $totalCount = ShofolModule::count();
        $activeCount = ShofolModule::active()->count();
        $inactiveCount = ShofolModule::where('is_active', false)->count();

        return view('admin.modules.index', compact(
            'modules',
            'totalCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new module.
     */
    public function create(): View
    {
        return view('admin.modules.create');
    }

    /**
     * Store a newly created module in storage.
     */
    public function store(ShofolModuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $module = ShofolModule::create($validated);

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Module \"{$module->name}\" ({$module->code}) was created successfully.");
    }

    /**
     * Show the form for editing the specified module.
     */
    public function edit(ShofolModule $module): View
    {
        $module->loadCount('knowledgeArticles');

        return view('admin.modules.edit', compact('module'));
    }

    /**
     * Update the specified module in storage.
     */
    public function update(ShofolModuleRequest $request, ShofolModule $module): RedirectResponse
    {
        $validated = $request->validated();
        $oldCode = $module->code;
        $newCode = $validated['code'];

        // If the module code changes, keep existing knowledge articles synchronized
        if ($oldCode !== $newCode) {
            KnowledgeArticle::where('module', $oldCode)->update(['module' => $newCode]);
        }

        $module->update($validated);

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Module \"{$module->name}\" ({$module->code}) was updated successfully.");
    }

    /**
     * Toggle the active status of the specified module.
     */
    public function toggle(ShofolModule $module): RedirectResponse
    {
        $module->update([
            'is_active' => !$module->is_active,
        ]);

        $statusLabel = $module->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Module \"{$module->name}\" ({$module->code}) was {$statusLabel} successfully.");
    }

    /**
     * Remove the specified module from storage if not referenced by knowledge articles.
     */
    public function destroy(ShofolModule $module): RedirectResponse
    {
        $articleCount = $module->knowledgeArticles()->count();
        $faqCount = $module->faqs()->count();

        if ($articleCount > 0 || $faqCount > 0) {
            $parts = [];
            if ($articleCount > 0) {
                $parts[] = "{$articleCount} knowledge " . ($articleCount === 1 ? 'article is' : 'articles are');
            }
            if ($faqCount > 0) {
                $parts[] = "{$faqCount} " . ($faqCount === 1 ? 'FAQ is' : 'FAQs are');
            }
            return redirect()
                ->route('admin.modules.index')
                ->with('error', "Cannot delete module \"{$module->name}\" ({$module->code}) because " . implode(' and ', $parts) . " currently referencing it. Please reassign or delete the referenced items first.");
        }

        $name = $module->name;
        $code = $module->code;
        $module->delete();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Module \"{$name}\" ({$code}) was deleted successfully.");
    }
}
