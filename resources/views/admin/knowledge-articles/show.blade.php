@extends('layouts.app-bootstrap')

@section('title', $knowledgeArticle->title . ' — Knowledge Base')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-3 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.knowledge-articles.index') }}" class="text-decoration-none">Knowledge Base</a></li>
                <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 300px;">{{ $knowledgeArticle->title }}</li>
            </ol>
        </nav>

        @php
            $moduleBadgeClass = match($knowledgeArticle->module) {
                'HRM' => 'bg-primary-subtle text-primary border border-primary-subtle',
                'Payroll' => 'bg-success-subtle text-success border border-success-subtle',
                'CRM' => 'bg-info-subtle text-info border border-info-subtle',
                'Accounting' => 'bg-warning-subtle text-warning border border-warning-subtle',
                default => 'bg-secondary-subtle text-secondary',
            };
        @endphp

        <div class="card shadow-sm border-0 mb-4">
            <!-- Article Header -->
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge {{ $moduleBadgeClass }} px-2 py-1 fs-6">
                        <i class="bi bi-tag-fill me-1"></i>{{ $knowledgeArticle->module }}
                    </span>
                    <span class="badge bg-light text-dark border px-2 py-1 fs-6">
                        {{ $knowledgeArticle->category }}
                    </span>
                    @if($knowledgeArticle->status === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i>Active
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                            <i class="bi bi-dash-circle-fill me-1"></i>Inactive
                        </span>
                    @endif
                    <span class="text-muted small">Article #{{ $knowledgeArticle->id }}</span>
                </div>

                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.knowledge-articles.edit', $knowledgeArticle) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                    <form method="POST" action="{{ route('admin.knowledge-articles.destroy', $knowledgeArticle) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this article?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <!-- Title -->
                <h2 class="fw-bold text-dark mb-3">
                    {{ $knowledgeArticle->title }}
                </h2>

                <!-- Author & Timestamps Metadata -->
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small pb-3 mb-4 border-bottom">
                    <div>
                        <i class="bi bi-person me-1"></i>
                        Created by: <strong>{{ $knowledgeArticle->creator->name ?? 'System Admin' }}</strong>
                    </div>
                    <div>
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ $knowledgeArticle->created_at->format('M d, Y h:i A') }}
                    </div>
                    @if($knowledgeArticle->updated_at != $knowledgeArticle->created_at)
                        <div>
                            <i class="bi bi-clock-history me-1"></i>
                            Updated {{ $knowledgeArticle->updated_at->diffForHumans() }}
                        </div>
                    @endif
                </div>

                <!-- Primary Question Callout -->
                @if($knowledgeArticle->question)
                    <div class="alert alert-primary bg-primary-subtle border-primary-subtle shadow-xs mb-4" role="alert">
                        <div class="d-flex gap-2">
                            <i class="bi bi-patch-question-fill text-primary fs-4 lh-1"></i>
                            <div>
                                <div class="text-uppercase small fw-bold text-primary mb-1">Primary User Query (RAG Seed)</div>
                                <div class="fs-6 fw-semibold text-dark">{{ $knowledgeArticle->question }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Content / Answer Body -->
                <div class="mb-4">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Content / Answer</div>
                    <div class="p-3 p-md-4 rounded bg-light border text-dark fs-6" style="white-space: pre-wrap; line-height: 1.7;">{{ $knowledgeArticle->content }}</div>
                </div>

                <!-- Keywords / Tags -->
                @if($knowledgeArticle->keywords)
                    <div class="pt-3 border-top">
                        <div class="text-muted small fw-bold text-uppercase mb-2">Search Keywords & Tags</div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach(array_map('trim', explode(',', $knowledgeArticle->keywords)) as $kw)
                                @if(!empty($kw))
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-hash text-muted"></i>{{ $kw }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Card Footer -->
            <div class="card-footer bg-light py-3 border-top d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.knowledge-articles.index', ['module' => $knowledgeArticle->module]) }}" class="small text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>More {{ $knowledgeArticle->module }} Articles
                </a>
                <span class="text-muted small">Ready for RAG Knowledge Search in Module 4</span>
            </div>
        </div>
    </div>
</div>
@endsection
