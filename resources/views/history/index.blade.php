@extends('layouts.app')

@section('title', 'Değerlendirme Geçmişi')

@section('content')
<x-page-header 
    title="Değerlendirme Geçmişi" 
    subtitle="Geçmişte verdiğiniz onay, revizyon ve red kararlarını görüntüleyin."
/>

<x-section-card class="mb-4">
    <form action="{{ route('history.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label for="q" class="kf-form-label mb-1">Arama</label>
            <input type="text" name="q" id="q" class="form-control kf-form-control"
                   value="{{ $filters['q'] ?? '' }}" placeholder="Kaizen kodu veya başlık...">
        </div>
        <div class="col-12 col-md-3">
            <label for="action" class="kf-form-label mb-1">İşlem Türü</label>
            <select name="action" id="action" class="form-select kf-form-control">
                <option value="">Tüm İşlemler</option>
                <option value="{{ \App\Enums\WorkflowAction::APPROVE->value }}" {{ ($filters['action'] ?? '') === \App\Enums\WorkflowAction::APPROVE->value ? 'selected' : '' }}>Onaylandı</option>
                <option value="{{ \App\Enums\WorkflowAction::REQUEST_REVISION->value }}" {{ ($filters['action'] ?? '') === \App\Enums\WorkflowAction::REQUEST_REVISION->value ? 'selected' : '' }}>Revizyon İstendi</option>
                <option value="{{ \App\Enums\WorkflowAction::REJECT->value }}" {{ ($filters['action'] ?? '') === \App\Enums\WorkflowAction::REJECT->value ? 'selected' : '' }}>Reddedildi</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="date_from" class="kf-form-label mb-1">Başlangıç</label>
            <input type="date" name="date_from" id="date_from" class="form-control kf-form-control"
                   value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="col-6 col-md-2">
            <label for="date_to" class="kf-form-label mb-1">Bitiş</label>
            <input type="date" name="date_to" id="date_to" class="form-control kf-form-control"
                   value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="kf-btn kf-btn-primary flex-grow-1 w-100">Filtrele</button>
            @if(request()->anyFilled(['q', 'action', 'date_from', 'date_to']))
                <a href="{{ route('history.index') }}" class="kf-btn kf-btn-secondary px-3" title="Temizle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </a>
            @endif
        </div>
    </form>
</x-section-card>

@if($reviewedTransitions->isEmpty())
    <x-empty-state 
        title="Geçmişiniz boş" 
        description="Onay süreçlerinde değerlendirme yaptığınızda geçmişiniz burada görünecek."
    >
        <x-slot:icon>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
        </x-slot:icon>
    </x-empty-state>
@else
    <div class="kf-table-shell">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Kaizen</th>
                        <th scope="col" class="d-none d-md-table-cell">Aşama</th>
                        <th scope="col">Yaptığım İşlem</th>
                        <th scope="col" class="d-none d-sm-table-cell">İşlem Tarihi</th>
                        <th scope="col" class="d-none d-lg-table-cell">Güncel Durum</th>
                        <th scope="col" class="text-end">İncele</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviewedTransitions as $transition)
                        @php
                            $kaizen = $transition->kaizen;
                            $badgeVariant = $transition->action->badgeVariant();
                            $actionPastLabel = match($transition->action) {
                                \App\Enums\WorkflowAction::APPROVE => 'Onaylandı',
                                \App\Enums\WorkflowAction::REQUEST_REVISION => 'Revizyon İstendi',
                                \App\Enums\WorkflowAction::REJECT => 'Reddedildi',
                                default => $transition->action->label()
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 200px;" title="{{ $kaizen->title }}">{{ $kaizen->title }}</div>
                                <div class="font-monospace text-muted small fw-bold mt-1">{{ $kaizen->code }}</div>
                                <div class="text-secondary small mt-1 d-md-none">{{ $kaizen->category->name ?? '-' }}</div>
                            </td>
                            <td class="d-none d-md-table-cell text-secondary small fw-medium">
                                {{ $transition->fromStage?->name ?? '-' }}
                            </td>
                            <td>
                                <span class="badge" style="background-color: var(--kf-{{ $badgeVariant }}-soft, var(--kf-primary-soft)); color: var(--kf-{{ $badgeVariant }}, var(--kf-primary)); font-weight: 600;">
                                    {{ $actionPastLabel }}
                                </span>
                            </td>
                            <td class="d-none d-sm-table-cell text-secondary small">
                                {{ $transition->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <x-status-badge :status="$kaizen->status" />
                            </td>
                            <td class="text-end">
                                @can('view', $kaizen)
                                    <a href="{{ route('kaizens.show', $kaizen) }}"
                                       class="kf-btn kf-btn-secondary"
                                       style="padding: 0.35rem 0.75rem; font-size: 0.85rem;"
                                       aria-label="{{ $kaizen->code }} detaylarını görüntüle">
                                        İncele
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size: 0.85rem;">&#8212;</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($reviewedTransitions->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $reviewedTransitions->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
