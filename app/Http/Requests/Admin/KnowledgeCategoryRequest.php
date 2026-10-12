<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KnowledgeCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? $this->route('category');
        $moduleId = $this->input('shofol_module_id');

        return [
            'shofol_module_id' => ['required', 'exists:shofol_modules,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('knowledge_categories', 'name')
                    ->where('shofol_module_id', $moduleId)
                    ->ignore($categoryId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('knowledge_categories', 'slug')
                    ->where('shofol_module_id', $moduleId)
                    ->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');
        if (empty($slug) && $this->filled('name')) {
            $slug = Str::slug($this->input('name'));
        }

        $this->merge([
            'slug' => $slug ? Str::lower($slug) : null,
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
        ]);
    }

    /**
     * Custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'shofol_module_id' => 'Shofol Module',
            'name' => 'Category Name',
            'slug' => 'URL Slug',
            'description' => 'Description',
            'is_active' => 'Active Status',
            'sort_order' => 'Sort Order',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A category with this name already exists within the selected module.',
            'slug.unique' => 'A category with this slug already exists within the selected module.',
            'slug.regex' => 'The category slug must contain only lowercase letters, numbers, and dashes.',
        ];
    }
}
