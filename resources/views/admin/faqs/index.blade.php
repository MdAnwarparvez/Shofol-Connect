@extends('layouts.app-bootstrap')

@section('title', 'FAQ Management — Admin Panel')

@section('content')
<!-- Page Header & Action Buttons -->
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">FAQs</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-1">
            <i class="bi bi-question-circle-fill text-primary me-2"></i>Dedicated FAQ Management
        </h2>
        <p class="text-muted mb-0">
            Curate verified frequently asked questions, manage topic hierarchy, and control AI retrieval knowledge grounding.
        </p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-tags me-1"></i> Categories
        </a>
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New FAQ
        </a>
    </div>
</div>

<!-- Quick Statistics Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total FAQs -->
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total FAQs</div>
                        <div class="fs-3 fw-bold text-dark">{{ $totalCount }}</div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded lh-1">
                        <i class="bi bi-question-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active FAQs -->
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

    <!-- Inactive FAQs -->
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
        <form method="GET" action="{{ route('admin.faqs.index') }}" class="row g-2 align-items-end">
            <!-- Search Query -->
            <div class="col-md-3">
                <label for="searchQuery" class="form-label small fw-semibold mb-1">Search FAQs</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="searchQuery" name="q" value="{{ request('q') }}" placeholder="Question, answer, keywords, slug...">
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

            <!-- Category Filter -->
            <div class="col-md-2">
                <label for="filterCategory" class="form-label small fw-semibold mb-1">Category</label>
                <select class="form-select" id="filterCategory" name="category_id">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" data-module-id="{{ $cat->shofol_module_id }}" {{ ((string) request('category_id') === (string) $cat->id || request('category') === $cat->slug) ? 'selected' : '' }}>
                            {{ $cat->name }}
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
                @if(request()->filled('q') || request()->filled('module_id') || request()->filled('module') || request()->filled('category_id') || request()->filled('category') || (request()->filled('status') && in_array(request('status'), ['active', 'inactive', '1', '0'], true)))
                    <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- FAQs Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-list-nested me-1"></i>Configured FAQs ({{ $faqs->total() }})
        </h6>
        <span class="text-muted small">Organized by module hierarchy & sort order</span>
    </div>

    <div class="card-body p-0">
        @if($faqs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 60px;" class="text-center">Order</th>
                            <th style="min-width: 250px;">Question & Slug</th>
                            <th>Parent Module & Category</th>
                            <th style="max-width: 320px;">Answer Excerpt</th>
                            <th class="text-center">Status</th>
                            <th>Last Updated</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faqs as $faq)
                            <tr>
                                <td class="text-center font-monospace fw-bold text-muted">
                                    {{ $faq->sort_order }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $faq->question }}</div>
                                    <small class="text-muted font-monospace">{{ $faq->slug }}</small>
                                    @if(!empty($faq->keywords))
                                        <div class="mt-1">
                                            @foreach(explode(',', $faq->keywords) as $kw)
                                                <span class="badge bg-light text-muted border small py-0">{{ trim($kw) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($faq->shofolModule)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-block mb-1">
                                            <i class="bi {{ $faq->shofolModule->icon ?: 'bi-grid' }} me-1"></i>{{ $faq->shofolModule->name }} ({{ $faq->shofolModule->code }})
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary d-inline-block mb-1">Unassigned</span>
                                    @endif

                                    @if($faq->knowledgeCategory)
                                        <div>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                <i class="bi bi-tag me-1"></i>{{ $faq->knowledgeCategory->name }}
                                            </span>
                                        </div>
                                    @else
                                        <div>
                                            <span class="badge bg-light text-muted border">General / Module-wide</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-muted" style="max-width: 320px;">
                                        {{ Str::limit($faq->answer, 140, '...') }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($faq->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="bi bi-slash-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="small text-muted text-nowrap">
                                    {{ $faq->updated_at->format('M d, Y') }}
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="{{ route('admin.faqs.toggle', $faq) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $faq->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $faq->is_active ? 'Deactivate FAQ' : 'Activate FAQ' }}">
                                                <i class="bi {{ $faq->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Button -->
                                        <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-primary" title="Edit FAQ">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- Delete Button with Confirmation -->
                                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete FAQ: \'{{ addslashes($faq->question) }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete FAQ">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($faqs->hasPages())
                <div class="card-footer bg-white py-3 border-top">
                    {{ $faqs->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-question-circle fs-1 d-block mb-3 text-secondary opacity-50"></i>
                <h5>No FAQs Found</h5>
                <p class="mb-3">No frequently asked questions matched your search or filter criteria.</p>
                <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Add FAQ
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterModule = document.getElementById('filterModule');
    const filterCategory = document.getElementById('filterCategory');
    if (!filterModule || !filterCategory) return;

    function syncCategoryFilter() {
        const selectedModuleId = filterModule.value;
        const categoryOptions = filterCategory.querySelectorAll('option[data-module-id]');

        categoryOptions.forEach(opt => {
            const optModuleId = opt.getAttribute('data-module-id');
            if (!selectedModuleId || optModuleId === selectedModuleId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        // Reset category selection if currently hidden
        const selectedOpt = filterCategory.selectedOptions[0];
        if (selectedOpt && selectedOpt.style.display === 'none') {
            filterCategory.value = '';
        }
    }

    filterModule.addEventListener('change', syncCategoryFilter);
    syncCategoryFilter();
});
</script>
@endsection
