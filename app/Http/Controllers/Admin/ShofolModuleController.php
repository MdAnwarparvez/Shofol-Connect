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
        $query = ShofolModule::query()->withCount(['knowledgeArticles', 'categories', 'faqs']);

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
     * Remove the specified module from storage if not referenced by knowledge articles, categories, or FAQs.
     */
    public function destroy(ShofolModule $module): RedirectResponse
    {
        $parts = $module->getReferencingRecordDescriptions();

        if (!empty($parts)) {
            $last = array_pop($parts);
            $reason = count($parts) > 0 ? implode(', ', $parts) . ' and ' . $last : $last;

            return redirect()
                ->route('admin.modules.index')
                ->with('error', "Cannot delete module \"{$module->name}\" ({$module->code}) because {$reason} currently referencing it. Please reassign or delete the referenced items first.");
        }

        try {
            $name = $module->name;
            $code = $module->code;
            $module->delete();

            return redirect()
                ->route('admin.modules.index')
                ->with('success', "Module \"{$name}\" ({$code}) was deleted successfully.");
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.modules.index')
                ->with('error', "Failed to delete module \"{$module->name}\" ({$module->code}) due to an unexpected database error. Please ensure all related records are removed first.");
        }
    }
}
