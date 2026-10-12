@extends('layouts.app-bootstrap')

@section('title', 'Edit Module — Shofol Module Management')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}" class="text-decoration-none">Modules</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit: {{ $module->name }}</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Shofol Module
                    </h5>
                    <small class="text-muted">Modify configuration, status, or sorting priority for <strong>{{ $module->name }}</strong>.</small>
                </div>
                <a href="{{ route('admin.modules.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Modules
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

                @if($module->knowledge_articles_count > 0)
                    <div class="alert alert-info d-flex align-items-center gap-2 shadow-sm mb-4">
                        <i class="bi bi-info-circle-fill fs-5 text-info"></i>
                        <div>
                            <strong>Knowledge Base Reference:</strong> Currently,
                            <span class="badge bg-primary text-white">{{ $module->knowledge_articles_count }}</span>
                            knowledge {{ Str::plural('article', $module->knowledge_articles_count) }} reference this module.
                            If you update the module code, all linked articles will automatically remain synchronized.
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.modules.update', $module) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Module Name -->
                        <div class="col-md-7">
                            <label for="name" class="form-label fw-semibold">
                                Module Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $module->name) }}"
                                   required>
                            <div class="form-text small">Descriptive business title shown in dashboard and reports.</div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Module Code / Slug -->
                        <div class="col-md-5">
                            <label for="code" class="form-label fw-semibold">
                                Module Code / Key <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control font-monospace @error('code') is-invalid @enderror"
                                   id="code"
                                   name="code"
                                   value="{{ old('code', $module->code) }}"
                                   required>
                            <div class="form-text small">Unique key (changing this cascades to referenced articles).</div>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Bootstrap Icon Class -->
                        <div class="col-md-6">
                            <label for="icon" class="form-label fw-semibold">
                                Bootstrap Icon Class
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi {{ $module->icon ?: 'bi-stars' }}"></i></span>
                                <input type="text"
                                       class="form-control font-monospace @error('icon') is-invalid @enderror"
                                       id="icon"
                                       name="icon"
                                       value="{{ old('icon', $module->icon) }}"
                                       placeholder="e.g. bi-box-seam">
                            </div>
                            <div class="form-text small">Bootstrap Icons identifier (e.g. <code>bi-box-seam</code>).</div>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-6">
                            <label for="sort_order" class="form-label fw-semibold">
                                Display Sort Order
                            </label>
                            <input type="number"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   id="sort_order"
                                   name="sort_order"
                                   value="{{ old('sort_order', $module->sort_order) }}"
                                   min="0"
                                   max="9999">
                            <div class="form-text small">Lower numbers appear first in lists and navigation.</div>
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">
                                Module Description & Scope
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3">{{ old('description', $module->description) }}</textarea>
                            <div class="form-text small">Provides context for administrators and support agents.</div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Is Active Switch -->
                        <div class="col-12">
                            <div class="form-check form-switch p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-3"
                                       type="checkbox"
                                       role="switch"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $module->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">
                                    Active for Knowledge Base Articles
                                </label>
                                <div class="text-muted small ps-5">
                                    When active, this module is offered in article creation dropdowns. Existing articles with inactive modules remain preserved.
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.modules.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Update Module
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
