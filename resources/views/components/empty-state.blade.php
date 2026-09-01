@props(['title', 'description' => null, 'icon' => null])

<div class="kf-empty-state">
    @if($icon)
        <div class="kf-empty-icon text-muted mb-3">
            {{ $icon }}
        </div>
    @else
        <div class="kf-empty-icon text-muted mb-3">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
    @endif
    
    <h3 class="kf-empty-title">{{ $title }}</h3>
    
    @if($description)
        <p class="kf-empty-desc">{{ $description }}</p>
    @endif
    
    @if(isset($action))
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
