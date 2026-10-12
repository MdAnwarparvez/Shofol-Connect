<?php

namespace App\Http\Requests\Admin;

use App\Models\KnowledgeArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->route('knowledge_article');
        $currentModule = $article instanceof KnowledgeArticle
            ? $article->module
            : ($article ? KnowledgeArticle::find($article)?->module : null);

        $allowedModules = KnowledgeArticle::getAvailableModules($currentModule);
        $currentCategory = $article instanceof KnowledgeArticle
            ? $article->category
            : ($article ? KnowledgeArticle::find($article)?->category : null);

        return [
            'module' => ['required', 'string', Rule::in($allowedModules)],
            'category' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) use ($currentCategory) {
                    // Always allow current category during updates to prevent data loss on legacy articles
                    if ($currentCategory !== null && $currentCategory === $value) {
                        return;
                    }

                    $submittedModule = (string) $this->input('module');
                    if (empty($submittedModule)) {
                        return;
                    }

                    // If dynamic categories exist for this module, verify category belongs to it and is active
                    $moduleHasCategories = \App\Models\KnowledgeCategory::whereHas('shofolModule', function ($q) use ($submittedModule) {
                        $q->where('code', $submittedModule);
                    })->exists();

                    if ($moduleHasCategories) {
                        $isValid = \App\Models\KnowledgeCategory::active()
                            ->where('name', $value)
                            ->whereHas('shofolModule', function ($q) use ($submittedModule) {
                                $q->where('code', $submittedModule);
                            })
                            ->exists();

                        if (!$isValid) {
                            $fail("The selected category \"{$value}\" does not belong to the \"{$submittedModule}\" module or is inactive.");
                        }
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'question' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(KnowledgeArticle::STATUSES)],
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'module' => 'Module / Department',
            'category' => 'Category',
            'title' => 'Article Title',
            'question' => 'Primary Question',
            'content' => 'Content / Answer',
            'keywords' => 'Search Keywords',
            'status' => 'Status',
        ];
    }
}
