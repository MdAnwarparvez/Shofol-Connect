<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    /**
     * Display a paginated listing of FAQs with filtering and search.
     */
    public function index(Request $request): View
    {
        $search = $request->input('q');
        $module = $request->input('module_id') ?: $request->input('module');
        $category = $request->input('category_id') ?: $request->input('category');
        $status = $request->input('status');

        $query = Faq::query()
            ->with(['shofolModule', 'knowledgeCategory'])
            ->search($search)
            ->forModule($module)
            ->forCategory($category)
            ->forStatus($status);

        $faqs = $query->ordered()->paginate(15)->withQueryString();

        $modules = ShofolModule::ordered()->get();
        $categories = KnowledgeCategory::ordered()->get();
        $totalCount = Faq::count();
        $activeCount = Faq::active()->count();
        $inactiveCount = Faq::where('is_active', false)->count();

        return view('admin.faqs.index', compact(
            'faqs',
            'modules',
            'categories',
            'totalCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new FAQ.
     */
    public function create(): View
    {
        $modules = ShofolModule::active()->ordered()->get();
        $categories = KnowledgeCategory::active()->ordered()->get();

        return view('admin.faqs.create', compact('modules', 'categories'));
    }

    /**
     * Store a newly created FAQ in storage.
     */
    public function store(FaqRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()?->id;

        $faq = Faq::create($validated);

        try {
            app(\App\Services\EmbeddingService::class)->indexFaq($faq);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to auto-index FAQ: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', "FAQ \"{$faq->question}\" was created successfully under module \"{$faq->shofolModule->name}\".");
    }

    /**
     * Display the specified FAQ (redirects to edit).
     */
    public function show(Faq $faq): RedirectResponse
    {
        return redirect()->route('admin.faqs.edit', $faq);
    }

    /**
     * Show the form for editing the specified FAQ.
     */
    public function edit(Faq $faq): View
    {
        $faq->load(['shofolModule', 'knowledgeCategory']);
        $modules = ShofolModule::ordered()->get();

        // Include all active categories, plus the FAQ's currently assigned category if it is inactive
        $categories = KnowledgeCategory::query()
            ->where('is_active', true)
            ->orWhere('id', $faq->knowledge_category_id)
            ->ordered()
            ->get();

        return view('admin.faqs.edit', compact('faq', 'modules', 'categories'));
    }

    /**
     * Update the specified FAQ in storage.
     */
    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validated();

        $wasContentChanged = $faq->fill($validated)->isDirty([
            'question', 'answer', 'keywords', 'shofol_module_id', 'knowledge_category_id',
        ]);

        $faq->update($validated);

        if ($wasContentChanged) {
            $indexed = false;
            try {
                $indexed = app(\App\Services\EmbeddingService::class)->indexFaq($faq, true);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to auto-reindex FAQ: ' . $e->getMessage());
            }

            // Invalidate stale embedding if content changed but re-indexing failed or unconfigured
            if (!$indexed) {
                $faq->updateQuietly(['embedding' => null]);
            }
        }

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', "FAQ \"{$faq->question}\" was updated successfully.");
    }

    /**
     * Toggle the active status of the specified FAQ.
     */
    public function toggle(Faq $faq): RedirectResponse
    {
        $faq->update([
            'is_active' => !$faq->is_active,
        ]);

        $statusLabel = $faq->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "FAQ \"{$faq->question}\" was {$statusLabel} successfully.");
    }

    /**
     * Remove the specified FAQ from storage.
     */
    public function destroy(Faq $faq): RedirectResponse
    {
        $question = $faq->question;
        $faq->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', "FAQ \"{$question}\" was deleted successfully.");
    }
}
