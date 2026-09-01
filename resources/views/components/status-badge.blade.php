@props(['status'])

@php
    $class = 'kf-badge-draft';
    $label = $status;
    
    if ($status instanceof \App\Enums\KaizenStatus) {
        $val = $status->value;
        $label = $status->label();
    } elseif (is_string($status)) {
        $val = $status;
    } else {
        $val = 'draft';
    }
    
    $class = match($val) {
        'draft' => 'kf-badge-draft',
        'submitted' => 'kf-badge-info',
        'manager_review' => 'kf-badge-warning',
        'approved' => 'kf-badge-primary',
        'in_progress' => 'kf-badge-warning',
        'completed' => 'kf-badge-success',
        'rejected' => 'kf-badge-danger',
        'revision_requested' => 'kf-badge-warning',
        default => 'kf-badge-draft',
    };
@endphp

<span class="kf-badge {{ $class }}">
    {{ $label }}
</span>
