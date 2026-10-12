@extends('layouts.app-bootstrap')

@section('title', 'Edit Knowledge Article — Shofol Connect')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.knowledge-articles.index') }}" class="text-decoration-none">Knowledge Base</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.knowledge-articles.show', $knowledgeArticle) }}" class="text-decoration-none text-truncate d-inline-block" style="max-width: 200px;">{{ $knowledgeArticle->title }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Knowledge Article
                    </h5>
                    <small class="text-muted">Article ID #{{ $knowledgeArticle->id }} &bull; Last updated {{ $knowledgeArticle->updated_at->diffForHumans() }}</small>
                </div>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.knowledge-articles.show', $knowledgeArticle) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to List
                    </a>
                </div>
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

                <form method="POST" action="{{ route('admin.knowledge-articles.update', $knowledgeArticle) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Module Selection -->
                        <div class="col-md-6">
                            <label for="module" class="form-label fw-semibold">
                                Module / Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('module') is-invalid @enderror" id="module" name="module" required>
                                @foreach($modules as $m)
                                    <option value="{{ $m }}" {{ old('module', $knowledgeArticle->module) === $m ? 'selected' : '' }}>
                                        {{ $m }}
                                    </option>
                                @endforeach
                            </select>
                            @error('module')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label for="category" class="form-label fw-semibold">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                <option value="">-- Select Category --</option>
                                @php
                                    $selectedCat = old('category', $knowledgeArticle->category);
                                    $foundInGroups = false;
                                @endphp
                                @foreach($categoriesByModule as $modCode => $catList)
                                    <optgroup label="{{ $modCode }}" data-module="{{ $modCode }}">
                                        @foreach($catList as $catName)
                                            @if($selectedCat === $catName)
                                                @php $foundInGroups = true; @endphp
                                            @endif
                                            <option value="{{ $catName }}" data-module="{{ $modCode }}" {{ $selectedCat === $catName ? 'selected' : '' }}>
                                                {{ $catName }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach

                                @if(!empty($selectedCat) && !$foundInGroups)
                                    <option value="{{ $selectedCat }}" data-module="{{ $knowledgeArticle->module }}" selected>
                                        {{ $selectedCat }} (Current / Legacy)
                                    </option>
                                @endif
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Categories are automatically filtered based on the selected module.</div>
                        </div>

                        <!-- Title -->
                        <div class="col-12">
                            <label for="title" class="form-label fw-semibold">
                                Article Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $knowledgeArticle->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Primary Question -->
                        <div class="col-12">
                            <label for="question" class="form-label fw-semibold">
                                Primary Question / User Query
                            </label>
                            <textarea class="form-control @error('question') is-invalid @enderror" id="question" name="question" rows="2">{{ old('question', $knowledgeArticle->question) }}</textarea>
                            @error('question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Content / Answer -->
                        <div class="col-12">
                            <label for="content" class="form-label fw-semibold">
                                Content / Answer <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="8" required>{{ old('content', $knowledgeArticle->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Keywords -->
                        <div class="col-md-8">
                            <label for="keywords" class="form-label fw-semibold">
                                Keywords / Search Tags
                            </label>
                            <input type="text" class="form-control @error('keywords') is-invalid @enderror" id="keywords" name="keywords" value="{{ old('keywords', $knowledgeArticle->keywords) }}">
                            @error('keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">
                                Publication Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}" {{ old('status', $knowledgeArticle->status) === $s ? 'selected' : '' }}>
                                        {{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-danger" onclick="if(confirm('Are you sure you want to delete this article?')) { document.getElementById('delete-form').submit(); }">
                            <i class="bi bi-trash me-1"></i>Delete Article
                        </button>

                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.knowledge-articles.show', $knowledgeArticle) }}" class="btn btn-light border px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i>Update Article
                            </button>
                        </div>
                    </div>
                </form>

                <form id="delete-form" method="POST" action="{{ route('admin.knowledge-articles.destroy', $knowledgeArticle) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const moduleSelect = document.getElementById('module');
    const categorySelect = document.getElementById('category');
    if (!moduleSelect || !categorySelect) return;

    function filterCategories() {
        const selectedModule = moduleSelect.value;
        const optgroups = categorySelect.querySelectorAll('optgroup');

        optgroups.forEach(group => {
            const groupModule = group.getAttribute('data-module');
            if (!selectedModule || groupModule === selectedModule) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });

        // If currently selected category belongs to a hidden optgroup, reset selection
        const selectedOption = categorySelect.selectedOptions[0];
        if (selectedOption && selectedOption.value) {
            const parentGroup = selectedOption.parentElement;
            if (parentGroup && parentGroup.tagName === 'OPTGROUP' && parentGroup.style.display === 'none') {
                categorySelect.value = '';
            }
        }
    }

    moduleSelect.addEventListener('change', filterCategories);
    filterCategories();
});
</script>
@endsection
