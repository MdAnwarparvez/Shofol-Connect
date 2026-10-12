<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShofolModuleRequest extends FormRequest
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
        $moduleId = $this->route('module')?->id ?? $this->route('module');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shofol_modules', 'name')->ignore($moduleId),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('shofol_modules', 'code')->ignore($moduleId),
            ],
            'icon' => ['nullable', 'string', 'max:100'],
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
        $this->merge([
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
            'name' => 'Module Name',
            'code' => 'Module Code / Slug',
            'icon' => 'Icon Class',
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
            'code.regex' => 'The module code must contain only letters, numbers, dashes, and underscores.',
        ];
    }
}
