@extends('layouts.app-bootstrap')

@section('title', 'Knowledge Category Management — Admin Panel')

@section('content')
<!-- Page Header & Action Button -->
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Categories</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-1">
            <i class="bi bi-tags-fill text-primary me-2"></i>Knowledge Category Management
        </h2>
        <p class="text-muted mb-0">
            Structure departmental knowledge policies into module-linked categories, manage topic hierarchy, and control publishing lifecycle.
        </p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
        <a href="{{ route('admin.modules.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-grid-3x3-gap me-1"></i> Modules
        </a>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Category
        </a>
    </div>
</div>

<!-- Quick Statistics Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total Categories -->
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Categories</div>
                        <div class="fs-3 fw-bold text-dark">{{ $totalCount }}</div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded lh-1">
                        <i class="bi bi-tags fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Categories -->
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Active in KB</div>
                        <div class="fs-3 fw-bold text-success">{{ $activeCount }}</div>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded lh-1">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inactive Categories -->
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 border-start border-4 border-secondary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Inactive / Archived</div>
                        <div class="fs-3 fw-bold text-secondary">{{ $inactiveCount }}</div>
                    </div>
                    <div class="bg-secondary-subtle text-secondary p-3 rounded lh-1">
                        <i class="bi bi-pause-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="row g-2 align-items-end">
            <!-- Search Query -->
            <div class="col-md-5">
                <label for="searchQuery" class="form-label small fw-semibold mb-1">Search Categories</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="searchQuery" name="q" value="{{ request('q') }}" placeholder="Category name, slug, or description...">
                </div>
            </div>

            <!-- Module Filter -->
            <div class="col-md-3">
                <label for="filterModule" class="form-label small fw-semibold mb-1">Filter by Module</label>
                <select class="form-select" id="filterModule" name="module_id">
                    <option value="">All Modules</option>
                    @foreach($modules as $m)
                        <option value="{{ $m->id }}" {{ ((string) request('module_id') === (string) $m->id || request('module') === $m->code) ? 'selected' : '' }}>
                            {{ $m->name }} ({{ $m->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-2">
                <label for="filterStatus" class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select" id="filterStatus" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" {{ in_array(request('status'), ['active', '1'], true) ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ in_array(request('status'), ['inactive', '0'], true) ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                @if(request()->filled('q') || request()->filled('module_id') || request()->filled('module') || (request()->filled('status') && in_array(request('status'), ['active', 'inactive', '1', '0'], true)))
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Categories Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-list-nested me-1"></i>Configured Categories ({{ $categories->total() }})
        </h6>
        <span class="text-muted small">Grouped by module hierarchy</span>
    </div>

    <div class="card-body p-0">
        @if($categories->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 70px;" class="text-center">Order</th>
                            <th>Category & Slug</th>
                            <th>Parent Module</th>
                            <th>Description</th>
                            <th class="text-center">Articles</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td class="text-center font-monospace fw-bold text-muted">
                                    {{ $category->sort_order }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $category->name }}</div>
                                    <small class="text-muted font-monospace">{{ $category->slug }}</small>
                                </td>
                                <td>
                                    @if($category->shofolModule)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi {{ $category->shofolModule->icon ?: 'bi-grid' }} me-1"></i>{{ $category->shofolModule->name }} ({{ $category->shofolModule->code }})
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-muted" style="max-width: 300px;">
                                        {{ $category->description ?: 'No description provided.' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.knowledge-articles.index', ['q' => $category->name]) }}" class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-decoration-none">
                                        <i class="bi bi-file-text me-1"></i>{{ $category->knowledge_articles_count }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if($category->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="bi bi-slash-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="{{ route('admin.categories.toggle', $category) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $category->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $category->is_active ? 'Deactivate Category' : 'Activate Category' }}">
                                                <i class="bi {{ $category->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Button -->
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary" title="Edit Category">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- Safe Delete Form with Reference Warning -->
                                        @if($category->knowledge_articles_count > 0)
                                            <button type="button" class="btn btn-sm btn-outline-danger opacity-50" title="Cannot delete: referenced by {{ $category->knowledge_articles_count }} knowledge articles" onclick="alert('Cannot delete category \'{{ $category->name }}\' because {{ $category->knowledge_articles_count }} knowledge articles currently reference it. Please reassign or delete the articles first.');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete category \'{{ $category->name }}\'?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Category">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($categories->hasPages())
                <div class="card-footer bg-white py-3 border-top">
                    {{ $categories->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-tags fs-1 d-block mb-3 text-secondary opacity-50"></i>
                <h5>No Categories Found</h5>
                <p class="mb-3">No knowledge categories matched your search or module filter.</p>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Add Category
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
