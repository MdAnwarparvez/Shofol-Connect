<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KnowledgeArticleRequest;
use App\Models\KnowledgeArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeArticleController extends Controller
{
    /**
     * Display a listing of the knowledge articles.
     */
    public function index(Request $request): View
    {
        $query = KnowledgeArticle::query()
            ->with('creator')
            ->search($request->input('q'))
            ->forModule($request->input('module'))
            ->forStatus($request->input('status'))
            ->latest();

        $articles = $query->paginate(10)->withQueryString();

        $modules = KnowledgeArticle::MODULES;
        $statuses = KnowledgeArticle::STATUSES;

        // Module article count summary for quick statistics
        $moduleCounts = KnowledgeArticle::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->pluck('count', 'module')
            ->toArray();

        return view('admin.knowledge-articles.index', compact(
            'articles',
            'modules',
            'statuses',
            'moduleCounts'
        ));
    }

    /**
     * Show the form for creating a new knowledge article.
     */
    public function create(): View
    {
        $modules = KnowledgeArticle::MODULES;
        $statuses = KnowledgeArticle::STATUSES;

        return view('admin.knowledge-articles.create', compact('modules', 'statuses'));
    }

    /**
     * Store a newly created knowledge article in storage.
     */
    public function store(KnowledgeArticleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        $article = KnowledgeArticle::create($validated);

        return redirect()
            ->route('admin.knowledge-articles.index')
            ->with('success', "Knowledge article \"{$article->title}\" was created successfully.");
    }

    /**
     * Display the specified knowledge article.
     */
    public function show(KnowledgeArticle $knowledgeArticle): View
    {
        $knowledgeArticle->load('creator');

        return view('admin.knowledge-articles.show', compact('knowledgeArticle'));
    }

    /**
     * Show the form for editing the specified knowledge article.
     */
    public function edit(KnowledgeArticle $knowledgeArticle): View
    {
        $modules = KnowledgeArticle::MODULES;
        $statuses = KnowledgeArticle::STATUSES;

        return view('admin.knowledge-articles.edit', compact(
            'knowledgeArticle',
            'modules',
            'statuses'
        ));
    }

    /**
     * Update the specified knowledge article in storage.
     */
    public function update(KnowledgeArticleRequest $request, KnowledgeArticle $knowledgeArticle): RedirectResponse
    {
        $knowledgeArticle->update($request->validated());

        return redirect()
            ->route('admin.knowledge-articles.show', $knowledgeArticle)
            ->with('success', "Knowledge article \"{$knowledgeArticle->title}\" was updated successfully.");
    }

    /**
     * Remove the specified knowledge article from storage.
     */
    public function destroy(KnowledgeArticle $knowledgeArticle): RedirectResponse
    {
        $title = $knowledgeArticle->title;
        $knowledgeArticle->delete();

        return redirect()
            ->route('admin.knowledge-articles.index')
            ->with('success', "Knowledge article \"{$title}\" was deleted successfully.");
    }
}
