@props(['title', 'subtitle' => null])

<div class="kf-page-header">
    <div>
        <h1 class="kf-page-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="kf-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="d-flex align-items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
