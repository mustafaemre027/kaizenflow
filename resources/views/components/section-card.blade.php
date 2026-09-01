@props(['title' => null, 'description' => null, 'noPadding' => false])

<div {{ $attributes->merge(['class' => 'kf-card']) }}>
    @if($title || isset($headerActions))
        <div class="kf-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                @if($title)
                    <h3 class="kf-card-title">{{ $title }}</h3>
                @endif
                @if($description)
                    <p class="text-muted small mb-0 mt-1">{{ $description }}</p>
                @endif
            </div>
            
            @if(isset($headerActions))
                <div class="d-flex gap-2">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="kf-card-body {{ $noPadding ? 'p-0' : '' }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
        <div class="kf-card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
