@extends('layouts.app-bootstrap')

@section('title', 'Knowledge Base Management — Shofol Connect')

@section('styles')
<style>
    .kb-module-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
    }
    .kb-module-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .kb-module-card.is-active {
        box-shadow: 0 0 0 2px var(--brand-primary), 0 0.5rem 1rem rgba(13, 110, 253, 0.15) !important;
    }
    [data-bs-theme="dark"] .kb-module-card.is-active {
        box-shadow: 0 0 0 2px var(--brand-primary), 0 0.5rem 1rem rgba(59, 130, 246, 0.25) !important;
    }
    [data-bs-theme="dark"] .btn-outline-secondary {
        color: #cbd5e1;
        border-color: #475569;
    }
    [data-bs-theme="dark"] .btn-outline-secondary:hover {
        background-color: #334155;
        border-color: #64748b;
        color: #ffffff;
    }
</style>
@endsection

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Knowledge Base</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">
            <i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Knowledge Base Management
        </h2>
        <p class="text-muted mb-0 small">
            Author and maintain verified documentation for HRM, Payroll, CRM, and Accounting.
        </p>
    </div>
    <div>
        <a href="{{ route('admin.knowledge-articles.create') }}" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle"></i>
            <span>Add New Article</span>
        </a>
    </div>
</div>

@php
    $selectedModule = request('module');
@endphp

<!-- Department Overview Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.knowledge-articles.index', ['module' => 'HRM']) }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-primary h-100 kb-module-card position-relative {{ $selectedModule === 'HRM' ? 'is-active' : '' }}" title="Filter by HRM">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">HRM</div>
                    @if($selectedModule === 'HRM')
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fs-4 fw-bold text-body">{{ $moduleCounts['HRM'] ?? 0 }}</div>
                    <div class="bg-primary-subtle text-primary p-2 rounded lh-1">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('admin.knowledge-articles.index', ['module' => 'Payroll']) }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-success h-100 kb-module-card position-relative {{ $selectedModule === 'Payroll' ? 'is-active' : '' }}" title="Filter by Payroll">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">Payroll</div>
                    @if($selectedModule === 'Payroll')
                        <span class="badge bg-success-subtle text-success border border-success-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fs-4 fw-bold text-body">{{ $moduleCounts['Payroll'] ?? 0 }}</div>
                    <div class="bg-success-subtle text-success p-2 rounded lh-1">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('admin.knowledge-articles.index', ['module' => 'CRM']) }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-info h-100 kb-module-card position-relative {{ $selectedModule === 'CRM' ? 'is-active' : '' }}" title="Filter by CRM">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">CRM</div>
                    @if($selectedModule === 'CRM')
                        <span class="badge bg-info-subtle text-info border border-info-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fs-4 fw-bold text-body">{{ $moduleCounts['CRM'] ?? 0 }}</div>
                    <div class="bg-info-subtle text-info p-2 rounded lh-1">
                        <i class="bi bi-person-lines-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('admin.knowledge-articles.index', ['module' => 'Accounting']) }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-warning h-100 kb-module-card position-relative {{ $selectedModule === 'Accounting' ? 'is-active' : '' }}" title="Filter by Accounting">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">Accounting</div>
                    @if($selectedModule === 'Accounting')
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fs-4 fw-bold text-body">{{ $moduleCounts['Accounting'] ?? 0 }}</div>
                    <div class="bg-warning-subtle text-warning p-2 rounded lh-1">
                        <i class="bi bi-calculator-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.knowledge-articles.index') }}" class="row g-2 align-items-end">
            <!-- Search Query -->
            <div class="col-md-5">
                <label for="searchQuery" class="form-label small fw-semibold mb-1">Search Keywords or Content</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="searchQuery" name="q" value="{{ request('q') }}" placeholder="Title, question, keywords, category...">
                </div>
            </div>

            <!-- Module Filter -->
            <div class="col-md-3">
                <label for="filterModule" class="form-label small fw-semibold mb-1">Filter by Module</label>
                <select class="form-select" id="filterModule" name="module">
                    <option value="">All Modules</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-2">
                <label for="filterStatus" class="form-label small fw-semibold mb-1">Filter by Status</label>
                <select class="form-select" id="filterStatus" name="status">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1 shadow-sm w-100" title="Apply Filters">
                    <i class="bi bi-funnel"></i>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['q', 'module', 'status']))
                    <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center px-3" title="Reset Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Articles Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-card-list me-1 text-primary"></i> Articles List
            <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $articles->total() }} total</span>
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th scope="col" style="width: 60px;">ID</th>
                    <th scope="col" style="width: 120px;">Module</th>
                    <th scope="col">Title & Primary Question</th>
                    <th scope="col">Category</th>
                    <th scope="col" style="width: 100px;">Status</th>
                    <th scope="col">Created By</th>
                    <th scope="col" class="text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    @php
                        $moduleBadgeClass = match($article->module) {
                            'HRM' => 'bg-primary-subtle text-primary border border-primary-subtle',
                            'Payroll' => 'bg-success-subtle text-success border border-success-subtle',
                            'CRM' => 'bg-info-subtle text-info border border-info-subtle',
                            'Accounting' => 'bg-warning-subtle text-warning border border-warning-subtle',
                            default => 'bg-secondary-subtle text-secondary',
                        };
                    @endphp
                    <tr>
                        <td class="text-muted small">#{{ $article->id }}</td>
                        <td>
                            <span class="badge {{ $moduleBadgeClass }} fw-semibold px-2 py-1">
                                {{ $article->module }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.knowledge-articles.show', $article) }}" class="fw-bold text-decoration-none text-body d-block">
                                {{ $article->title }}
                            </a>
                            @if($article->question)
                                <div class="text-muted small text-truncate" style="max-width: 420px;">
                                    <i class="bi bi-question-circle me-1"></i>{{ $article->question }}
                                </div>
                            @endif
                            @if($article->keywords)
                                <div class="mt-1">
                                    @foreach(array_slice(array_map('trim', explode(',', $article->keywords)), 0, 3) as $tag)
                                        <span class="badge bg-body-secondary text-secondary border small">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-body-secondary text-body border">{{ $article->category }}</span>
                        </td>
                        <td>
                            @if($article->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check2-circle me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="bi bi-dash-circle me-1"></i>Inactive
                                </span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            <div>{{ $article->creator->name ?? 'System' }}</div>
                            <div class="text-muted small">{{ $article->created_at->format('M d, Y') }}</div>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.knowledge-articles.show', $article) }}" class="btn btn-outline-primary" title="View Article">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.knowledge-articles.edit', $article) }}" class="btn btn-outline-secondary" title="Edit Article">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.knowledge-articles.destroy', $article) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this article: \'{{ addslashes($article->title) }}\'?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete Article">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                <h5 class="fw-semibold">No knowledge articles found</h5>
                                <p class="small mb-3">
                                    @if(request()->hasAny(['q', 'module', 'status']))
                                        No articles matched your active filters. Try clearing your search parameters.
                                    @else
                                        Start adding knowledge articles for HRM, Payroll, CRM, and Accounting.
                                    @endif
                                </p>
                                @if(request()->hasAny(['q', 'module', 'status']))
                                    <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-sm btn-outline-secondary me-2">Clear Filters</a>
                                @endif
                                <a href="{{ route('admin.knowledge-articles.create') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Add First Article
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
        <div class="card-footer py-3 border-top d-flex justify-content-center">
            {{ $articles->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
