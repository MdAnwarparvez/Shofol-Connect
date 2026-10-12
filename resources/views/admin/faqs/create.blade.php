@extends('layouts.app-bootstrap')

@section('title', 'Add New FAQ — Admin Panel')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Breadcrumb & Header -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.faqs.index') }}" class="text-decoration-none">FAQs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add New FAQ</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-question-circle-fill text-primary me-2"></i>Create New FAQ
                </h2>
                <p class="text-muted mb-0">
                    Add an authoritative, verified question and answer to ground AI responses and assist users.
                </p>
            </div>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to FAQs
            </a>
        </div>

        <!-- Form Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger shadow-sm mb-4">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please check the form errors below:</div>
                        <ul class="mb-0 small ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.faqs.store') }}">
                    @csrf

                    <div class="row g-3">
                        <!-- Shofol Module -->
                        <div class="col-md-6">
                            <label for="shofol_module_id" class="form-label fw-semibold">
                                Parent Shofol Module <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('shofol_module_id') is-invalid @enderror" id="shofol_module_id" name="shofol_module_id" required>
                                <option value="">-- Select Module --</option>
                                @foreach($modules as $m)
                                    <option value="{{ $m->id }}" {{ (string) old('shofol_module_id') === (string) $m->id ? 'selected' : '' }}>
                                        {{ $m->name }} ({{ $m->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('shofol_module_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Select the business department or module this FAQ belongs to.</div>
                        </div>

                        <!-- Knowledge Category (Optional) -->
                        <div class="col-md-6">
                            <label for="knowledge_category_id" class="form-label fw-semibold">
                                Knowledge Category <span class="text-muted small fw-normal">(Optional)</span>
                            </label>
                            <select class="form-select @error('knowledge_category_id') is-invalid @enderror" id="knowledge_category_id" name="knowledge_category_id">
                                <option value="">-- No Category (General / Module-wide) --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" data-module-id="{{ $cat->shofol_module_id }}" {{ (string) old('knowledge_category_id') === (string) $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('knowledge_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Categories dynamically filter based on the chosen module above.</div>
                        </div>

                        <!-- Question -->
                        <div class="col-12">
                            <label for="question" class="form-label fw-semibold">
                                Frequently Asked Question <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('question') is-invalid @enderror" id="question" name="question" value="{{ old('question') }}" placeholder="e.g. What are the standard customer invoice payment terms and grace period?" required maxlength="500">
                            @error('question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">State the question clearly from a user's perspective.</div>
                        </div>

                        <!-- Slug -->
                        <div class="col-12">
                            <label for="slug" class="form-label fw-semibold">
                                URL Slug <span class="text-muted small fw-normal">(Optional — auto-generated if left blank)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted small">/faqs/</span>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror font-monospace" id="slug" name="slug" value="{{ old('slug') }}" placeholder="standard-invoice-payment-terms-grace-period" maxlength="255">
                            </div>
                            @error('slug')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Unique identifier used in URLs and API responses.</div>
                        </div>

                        <!-- Answer -->
                        <div class="col-12">
                            <label for="answer" class="form-label fw-semibold">
                                Authoritative Answer / Policy Content <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('answer') is-invalid @enderror" id="answer" name="answer" rows="6" placeholder="Provide a concise, verified answer grounded in company policy..." required>{{ old('answer') }}</textarea>
                            @error('answer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Write clear, factual policy details. This content will directly ground AI assistant answers.</div>
                        </div>

                        <!-- Keywords -->
                        <div class="col-md-8">
                            <label for="keywords" class="form-label fw-semibold">
                                Search & Retrieval Keywords <span class="text-muted small fw-normal">(Optional, comma-separated)</span>
                            </label>
                            <input type="text" class="form-control @error('keywords') is-invalid @enderror" id="keywords" name="keywords" value="{{ old('keywords') }}" placeholder="e.g. invoice, billing, net 30, grace period, late fees" maxlength="500">
                            @error('keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Add synonyms and search terms to boost lexical relevance in AI chat retrieval.</div>
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-4">
                            <label for="sort_order" class="form-label fw-semibold">
                                Sort Order
                            </label>
                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" step="1">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Lower numbers appear first within the module.</div>
                        </div>

                        <!-- Active Status Checkbox -->
                        <div class="col-12">
                            <div class="form-check form-switch p-2 bg-light rounded border">
                                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">
                                    Active in Knowledge Base & AI Retrieval
                                </label>
                                <div class="text-muted small ms-4">
                                    When active, this FAQ is eligible for AI chat grounding and public FAQ listings. Inactive FAQs are preserved in drafts.
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Actions -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary px-4">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Save FAQ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const moduleSelect = document.getElementById('shofol_module_id');
    const categorySelect = document.getElementById('knowledge_category_id');
    const questionInput = document.getElementById('question');
    const slugInput = document.getElementById('slug');

    if (!moduleSelect || !categorySelect) return;

    function filterCategories() {
        const selectedModuleId = moduleSelect.value;
        const categoryOptions = categorySelect.querySelectorAll('option[data-module-id]');

        categoryOptions.forEach(opt => {
            const optModuleId = opt.getAttribute('data-module-id');
            if (!selectedModuleId || optModuleId === selectedModuleId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        // Reset category selection if currently hidden
        const selectedOpt = categorySelect.selectedOptions[0];
        if (selectedOpt && selectedOpt.style.display === 'none') {
            categorySelect.value = '';
        }
    }

    moduleSelect.addEventListener('change', filterCategories);
    filterCategories();

    // Auto-generate slug suggestion if slug is empty
    if (questionInput && slugInput) {
        questionInput.addEventListener('blur', function () {
            if (!slugInput.value.trim() && questionInput.value.trim()) {
                slugInput.value = questionInput.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .trim()
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    }
});
</script>
@endsection
