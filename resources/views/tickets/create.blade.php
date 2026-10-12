@extends('layouts.app-bootstrap')

@section('title', 'Submit Support Ticket — Shofol Connect')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}" class="text-decoration-none">Support Tickets</a></li>
                <li class="breadcrumb-item active" aria-current="page">New Ticket</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-ticket-plus text-primary me-2"></i>Submit Support Ticket
                    </h5>
                    <small class="text-muted">Describe your issue or request assistance from human staff.</small>
                </div>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to My Tickets
                </a>
            </div>

            <div class="card-body p-4">
                @if($conversationId)
                    <div class="alert alert-info border-info-subtle shadow-xs mb-4 d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-robot fs-4 text-info lh-1"></i>
                        <div>
                            <strong>Escalated from AI Support Chat (Session #{{ $conversationId }}):</strong>
                            <div class="small">We pre-filled the subject and transcript below. Please review and edit before submitting.</div>
                        </div>
                    </div>
                @endif

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

                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf
                    @if($conversationId)
                        <input type="hidden" name="conversation_id" value="{{ $conversationId }}">
                    @endif

                    <div class="row g-3">
                        <!-- Subject -->
                        <div class="col-12">
                            <label for="subject" class="form-label fw-semibold">
                                Subject / Summary <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('subject') is-invalid @enderror"
                                id="subject"
                                name="subject"
                                value="{{ old('subject', $prefillSubject) }}"
                                placeholder="Brief summary of your inquiry or problem..."
                                required
                            >
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Department / Category -->
                        <div class="col-md-6">
                            <label for="category" class="form-label fw-semibold">
                                Category / Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category', $prefillCategory) === $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Select the department that best matches your issue.</div>
                        </div>

                        <!-- Priority -->
                        <div class="col-md-6">
                            <label for="priority" class="form-label fw-semibold">
                                Priority Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                @foreach($priorities as $prio)
                                    <option value="{{ $prio }}" {{ old('priority', 'Medium') === $prio ? 'selected' : '' }}>
                                        {{ $prio }}
                                    </option>
                                @endforeach
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Set priority based on business impact.</div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">
                                Detailed Description <span class="text-danger">*</span>
                            </label>
                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="8"
                                placeholder="Describe your question or issue in detail, including what you've tried or any error messages..."
                                required
                            >{{ old('description', $prefillDescription) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('tickets.index') }}" class="btn btn-light border px-4">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send-check me-1"></i>Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
