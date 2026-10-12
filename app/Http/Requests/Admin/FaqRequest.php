<?php

namespace App\Http\Requests\Admin;

use App\Models\Faq;
use App\Models\KnowledgeCategory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        } elseif ($this->isMethod('POST')) {
            // Default active on initial creation if not explicitly unchecked
            $this->merge(['is_active' => true]);
        } else {
            // Unchecked checkbox on update means false
            $this->merge(['is_active' => false]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $faq = $this->route('faq');
        $faqId = $faq instanceof Faq ? $faq->id : (is_numeric($faq) ? (int) $faq : null);
        $currentCategoryId = $faq instanceof Faq ? $faq->knowledge_category_id : ($faqId ? Faq::find($faqId)?->knowledge_category_id : null);

        return [
            'shofol_module_id' => [
                'required',
                'integer',
                'exists:shofol_modules,id',
            ],
            'knowledge_category_id' => [
                'nullable',
                'integer',
                'exists:knowledge_categories,id',
                function (string $attribute, mixed $value, Closure $fail) use ($currentCategoryId) {
                    if (empty($value)) {
                        return;
                    }

                    $moduleId = (int) $this->input('shofol_module_id');
                    $category = KnowledgeCategory::find($value);

                    if (!$category) {
                        $fail('The selected category does not exist.');
                        return;
                    }

                    // 1. Verify category belongs to the selected module
                    if ((int) $category->shofol_module_id !== $moduleId) {
                        $fail('The selected category does not belong to the selected module.');
                        return;
                    }

                    // 2. Allow existing category during update even if currently inactive
                    if ($currentCategoryId !== null && (int) $currentCategoryId === (int) $value) {
                        return;
                    }

                    // 3. Newly selected categories must be active
                    if (!$category->is_active) {
                        $fail('The selected category is inactive. Please choose an active category or activate it first.');
                    }
                },
            ],
            'question' => ['required', 'string', 'max:500'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('faqs', 'slug')->ignore($faqId),
            ],
            'answer' => ['required', 'string'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'shofol_module_id' => 'Shofol Module',
            'knowledge_category_id' => 'Knowledge Category',
            'question' => 'Question',
            'slug' => 'URL Slug',
            'answer' => 'Answer',
            'keywords' => 'Search Keywords',
            'is_active' => 'Active Status',
            'sort_order' => 'Sort Order',
        ];
    }
}
