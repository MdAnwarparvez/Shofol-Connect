@extends('layouts.app-bootstrap')

@section('title', 'Shofol Module Management — Admin Panel')

@section('content')
<!-- Page Header & Action Button -->
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modules</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-1">
            <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Shofol Module Management
        </h2>
        <p class="text-muted mb-0">
            Configure dynamic enterprise business modules, department codes, icons, and activation lifecycle for the Knowledge Base.
        </p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
        <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-journal-bookmark me-1"></i> Knowledge Base
        </a>
        <a href="{{ route('admin.modules.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Module
        </a>
    </div>
</div>

<!-- Quick Statistics Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total Modules -->
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Modules</div>
                        <div class="fs-3 fw-bold text-dark">{{ $totalCount }}</div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded lh-1">
                        <i class="bi bi-grid-3x3-gap fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Modules -->
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

    <!-- Inactive Modules -->
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

<!-- Search & Status Filter Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.modules.index') }}" class="row g-2 align-items-end">
            <div class="col-md-7">
                <label for="searchQuery" class="form-label small fw-semibold mb-1">Search Modules</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="searchQuery" name="q" value="{{ request('q') }}" placeholder="Search by module name, code, or description...">
                </div>
            </div>

            <div class="col-md-3">
                <label for="filterStatus" class="form-label small fw-semibold mb-1">Status Filter</label>
                <select class="form-select" id="filterStatus" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                @if(request()->hasAny(['q', 'status']))
                    <a href="{{ route('admin.modules.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Modules Listing Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-list-ul me-1"></i>Configured Modules ({{ $modules->total() }})
        </h6>
        <span class="text-muted small">Ordered by display priority</span>
    </div>

    <div class="card-body p-0">
        @if($modules->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 70px;" class="text-center">Order</th>
                            <th>Module & Code</th>
                            <th>Description</th>
                            <th class="text-center">Articles</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $module)
                            <tr>
                                <td class="text-center font-monospace fw-bold text-muted">
                                    {{ $module->sort_order }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded p-2 lh-1 text-center" style="width: 36px; height: 36px;">
                                            <i class="bi {{ $module->icon ?: 'bi-grid' }} fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $module->name }}</div>
                                            <span class="badge bg-dark-subtle text-dark border font-monospace">
                                                {{ $module->code }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-muted" style="max-width: 350px;">
                                        {{ $module->description ?: 'No description provided.' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.knowledge-articles.index', ['module' => $module->code]) }}" class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-decoration-none">
                                        <i class="bi bi-file-text me-1"></i>{{ $module->knowledge_articles_count }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if($module->is_active)
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
                                        <form method="POST" action="{{ route('admin.modules.toggle', $module) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $module->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $module->is_active ? 'Deactivate Module' : 'Activate Module' }}">
                                                <i class="bi {{ $module->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Button -->
                                        <a href="{{ route('admin.modules.edit', $module) }}" class="btn btn-sm btn-outline-primary" title="Edit Module">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- Safe Delete Form with Reference Warning -->
                                        @php
                                            $refItems = [];
                                            if (($module->knowledge_articles_count ?? 0) > 0) {
                                                $refItems[] = $module->knowledge_articles_count . ' ' . \Illuminate\Support\Str::plural('knowledge article', $module->knowledge_articles_count);
                                            }
                                            if (($module->categories_count ?? 0) > 0) {
                                                $refItems[] = $module->categories_count . ' ' . \Illuminate\Support\Str::plural('knowledge category', $module->categories_count);
                                            }
                                            if (($module->faqs_count ?? 0) > 0) {
                                                $refItems[] = $module->faqs_count . ' ' . \Illuminate\Support\Str::plural('FAQ', $module->faqs_count);
                                            }
                                            $hasReferences = count($refItems) > 0;
                                            $refText = count($refItems) > 1
                                                ? implode(', ', array_slice($refItems, 0, -1)) . ' and ' . end($refItems)
                                                : ($refItems[0] ?? '');
                                        @endphp

                                        @if($hasReferences)
                                            <button type="button" class="btn btn-sm btn-outline-danger opacity-50" title="Cannot delete: referenced by {{ $refText }}" onclick="alert('Cannot delete module \'{{ addslashes($module->name) }}\' ({{ $module->code }}) because {{ addslashes($refText) }} currently reference it. Please reassign or delete the referenced items first.');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete module \'{{ addslashes($module->name) }}\' ({{ $module->code }})?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Module">
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

            @if($modules->hasPages())
                <div class="card-footer bg-white py-3 border-top">
                    {{ $modules->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-grid fs-1 d-block mb-3 text-secondary opacity-50"></i>
                <h5>No Modules Found</h5>
                <p class="mb-3">No Shofol business modules matched your search or filter criteria.</p>
                <a href="{{ route('admin.modules.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Add Module
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
