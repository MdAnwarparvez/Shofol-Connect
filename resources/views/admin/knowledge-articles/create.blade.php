@extends('layouts.app-bootstrap')

@section('title', 'Add Knowledge Article — Shofol Connect')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.knowledge-articles.index') }}" class="text-decoration-none">Knowledge Base</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add New Article</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-file-earmark-plus text-primary me-2"></i>Create New Knowledge Article
                    </h5>
                    <small class="text-muted">Enter verified department guidelines or Q&A documentation.</small>
                </div>
                <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to List
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

                <form method="POST" action="{{ route('admin.knowledge-articles.store') }}">
                    @csrf

                    <div class="row g-3">
                        <!-- Module Selection -->
                        <div class="col-md-6">
                            <label for="module" class="form-label fw-semibold">
                                Module / Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('module') is-invalid @enderror" id="module" name="module" required>
                                <option value="">-- Select Module --</option>
                                @foreach($modules as $m)
                                    <option value="{{ $m }}" {{ old('module') === $m ? 'selected' : '' }}>
                                        {{ $m }}
                                    </option>
                                @endforeach
                            </select>
                            @error('module')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Select which business department this knowledge belongs to.</div>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label for="category" class="form-label fw-semibold">
                                Category <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}" placeholder="e.g. Leave Policy, Tax Deduction, Lead Scoring" required>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Subcategory or functional topic within the module.</div>
                        </div>

                        <!-- Title -->
                        <div class="col-12">
                            <label for="title" class="form-label fw-semibold">
                                Article Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="e.g. Annual Leave Entitlement and Application Procedure" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Primary Question -->
                        <div class="col-12">
                            <label for="question" class="form-label fw-semibold">
                                Primary Question / User Query
                            </label>
                            <textarea class="form-control @error('question') is-invalid @enderror" id="question" name="question" rows="2" placeholder="e.g. How do employees apply for annual leave, and what is the approval timeframe?">{{ old('question') }}</textarea>
                            @error('question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">The primary question users ask AI regarding this topic (improves RAG search matching).</div>
                        </div>

                        <!-- Content / Answer -->
                        <div class="col-12">
                            <label for="content" class="form-label fw-semibold">
                                Content / Answer <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="8" placeholder="Provide complete, structured answer and documentation here..." required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">The authoritative information used to answer user questions and provide knowledge search results.</div>
                        </div>

                        <!-- Keywords -->
                        <div class="col-md-8">
                            <label for="keywords" class="form-label fw-semibold">
                                Keywords / Search Tags
                            </label>
                            <input type="text" class="form-control @error('keywords') is-invalid @enderror" id="keywords" name="keywords" value="{{ old('keywords') }}" placeholder="e.g. leave, vacation, PTO, sick leave, hr policy">
                            @error('keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Comma-separated keywords or synonyms for AI semantic & keyword lookup.</div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">
                                Publication Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}" {{ old('status', 'active') === $s ? 'selected' : '' }}>
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

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-light border px-4">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i>Save Article
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
