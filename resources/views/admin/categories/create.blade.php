@extends('layouts.app-bootstrap')

@section('title', 'Add New Knowledge Category — Admin Panel')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}" class="text-decoration-none">Categories</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add New Category</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-folder-plus text-primary me-2"></i>Create New Knowledge Category
                    </h5>
                    <small class="text-muted">Define a functional topic classification belonging to a specific Shofol business module.</small>
                </div>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Categories
                </a>
            </div>

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

                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf

                    <div class="row g-3">
                        <!-- Parent Shofol Module -->
                        <div class="col-md-6">
                            <label for="shofol_module_id" class="form-label fw-semibold">
                                Parent Shofol Module <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('shofol_module_id') is-invalid @enderror" id="shofol_module_id" name="shofol_module_id" required>
                                <option value="">-- Select Module --</option>
                                @foreach($modules as $m)
                                    <option value="{{ $m->id }}" {{ old('shofol_module_id', request('module_id')) == $m->id ? 'selected' : '' }}>
                                        {{ $m->name }} ({{ $m->code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Each category strictly belongs to one primary business module.</div>
                            @error('shofol_module_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Category Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">
                                Category Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Leave Policy, Tax Deductions"
                                   required>
                            <div class="form-text small">Unique category name within the selected module.</div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- URL Slug -->
                        <div class="col-md-6">
                            <label for="slug" class="form-label fw-semibold">
                                URL Slug (Optional)
                            </label>
                            <input type="text"
                                   class="form-control font-monospace @error('slug') is-invalid @enderror"
                                   id="slug"
                                   name="slug"
                                   value="{{ old('slug') }}"
                                   placeholder="leave-policy">
                            <div class="form-text small">Auto-generated from category name if left blank.</div>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Display Sort Order -->
                        <div class="col-md-6">
                            <label for="sort_order" class="form-label fw-semibold">
                                Display Sort Order
                            </label>
                            <input type="number"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   id="sort_order"
                                   name="sort_order"
                                   value="{{ old('sort_order', 10) }}"
                                   min="0"
                                   max="9999">
                            <div class="form-text small">Lower numbers appear first within the module's category list.</div>
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">
                                Description & Topic Scope
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Summary of operational policies, guidelines, and FAQs grouped under this topic...">{{ old('description') }}</textarea>
                            <div class="form-text small">Guides knowledge base authors on appropriate article placement.</div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Active Toggle -->
                        <div class="col-12">
                            <div class="form-check form-switch p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-3"
                                       type="checkbox"
                                       role="switch"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">
                                    Active for Knowledge Base Articles
                                </label>
                                <div class="text-muted small ps-5">
                                    When active, this category is selectable in article creation dropdowns for its module.
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i> Create Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
