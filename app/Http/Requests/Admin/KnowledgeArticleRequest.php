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
        return [
            'module' => ['required', 'string', Rule::in(KnowledgeArticle::MODULES)],
            'category' => ['required', 'string', 'max:100'],
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
