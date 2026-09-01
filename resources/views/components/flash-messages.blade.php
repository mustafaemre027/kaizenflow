@if (session('success'))
    <div class="alert d-flex align-items-center alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="background-color: var(--kf-success-soft); color: var(--kf-success-text);">
        <svg width="20" height="20" class="me-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
        </svg>
        <div class="fw-medium">{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert d-flex align-items-center alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="background-color: var(--kf-danger-soft); color: var(--kf-danger-text);">
        <svg width="20" height="20" class="me-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div class="fw-medium">{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
@endif

@if (session('status'))
    <div class="alert d-flex align-items-center alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="background-color: var(--kf-info-soft); color: var(--kf-info-text);">
        <svg width="20" height="20" class="me-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div class="fw-medium">{{ session('status') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
@endif
