@extends('layouts.app')

@section('title', 'Değerlendirme Geçmişi')

@section('content')
<div class="kf-list-page">
    {{-- Page Header --}}
    <div class="kf-list-header">
        <div>
            <span class="kf-list-context">ONAY YÖNETİMİ</span>
            <h1 class="kf-list-title">Değerlendirme Geçmişi</h1>
            <p class="kf-list-desc">Geçmişte verdiğiniz onay, revizyon ve red kararlarını görüntüleyin.</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="kf-list-filter-panel mb-4">
        <form action="{{ route('history.index') }}" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="q" class="form-label">Arama</label>
                    <input type="text" name="q" id="q" class="kf-form-control"
                           value="{{ $filters['q'] ?? '' }}" placeholder="Kaizen kodu veya başlık...">
                </div>
                <div class="col-12 col-md-3">
                    <label for="action" class="form-label">İşlem Türü</label>
                    <select name="action" id="action" class="kf-form-control">
                        <option value="">Tüm işlemler</option>
                        @foreach(\App\Enums\WorkflowAction::reviewActions() as $act)
                            <option value="{{ $act->value }}" {{ ($filters['action'] ?? '') === $act->value ? 'selected' : '' }}>
                                {{ $act->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="date_from" class="form-label">Başlangıç</label>
                    <input type="date" name="date_from" id="date_from" class="kf-form-control"
                           value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-12 col-md-2">
                    <label for="date_to" class="form-label">Bitiş</label>
                    <input type="date" name="date_to" id="date_to" class="kf-form-control"
                           value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="kf-btn kf-btn-primary w-100">Filtrele</button>
                </div>
            </div>
            @if(!empty(array_filter([$filters['q'] ?? null, $filters['action'] ?? null, $filters['date_from'] ?? null, $filters['date_to'] ?? null])))
                <div class="mt-2">
                    <a href="{{ route('history.index') }}" class="text-decoration-none text-muted" style="font-size: 0.9rem; font-weight: 500;">
                        Filtreleri Temizle
                    </a>
                </div>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="kf-list-surface">
        @if($reviewedTransitions->isEmpty())
            <div class="kf-list-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" style="opacity:0.4; margin-bottom: 1rem;">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <h3 class="kf-list-empty-title">Henüz tamamladığınız bir değerlendirme bulunmuyor.</h3>
                <p class="kf-list-empty-desc">Onay süreçlerinde değerlendirme yaptığınızda geçmişiniz burada görünecek.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table kf-list-table" aria-label="Değerlendirme Geçmişim">
                    <thead>
                        <tr>
                            <th scope="col">Kaizen</th>
                            <th scope="col" class="kf-hide-mobile">Aşama</th>
                            <th scope="col">Yaptığım İşlem</th>
                            <th scope="col" class="kf-hide-mobile">İşlem Tarihi</th>
                            <th scope="col" class="kf-hide-mobile">Son Kaizen Durumu</th>
                            <th scope="col" class="text-end">İncele</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviewedTransitions as $transition)
                            @php
                                $kaizen = $transition->kaizen;
                                $badgeVariant = $transition->action->badgeVariant();
                                $kStatusVariant = match($kaizen->status) {
                                    \App\Enums\KaizenStatus::APPROVED => 'success',
                                    \App\Enums\KaizenStatus::REJECTED => 'danger',
                                    \App\Enums\KaizenStatus::REVISION_REQUESTED => 'warning',
                                    \App\Enums\KaizenStatus::SUBMITTED => 'primary',
                                    \App\Enums\KaizenStatus::IN_PROGRESS, \App\Enums\KaizenStatus::COMPLETED => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold" style="color: var(--kf-primary-dark); font-family: monospace; font-size: 0.9rem;">{{ $kaizen->code }}</div>
                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $kaizen->title }}">{{ $kaizen->title }}</div>
                                    <div class="text-secondary" style="font-size: 0.8rem;">{{ $kaizen->category->name ?? '-' }} &middot; {{ $kaizen->department->name ?? '-' }}</div>
                                </td>
                                <td class="kf-hide-mobile text-secondary" style="font-size: 0.9rem;">
                                    {{ $transition->fromStage?->name ?? '-' }}
                                </td>
                                <td>
                                    <span class="kf-badge kf-badge-{{ $badgeVariant }}" aria-label="İşlem: {{ $transition->action->label() }}">
                                        {{ $transition->action->label() }}
                                    </span>
                                </td>
                                <td class="kf-hide-mobile text-secondary" style="font-size: 0.9rem;">
                                    {{ $transition->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="kf-hide-mobile">
                                    <span class="kf-badge kf-badge-{{ $kStatusVariant }}" aria-label="Kaizen durumu: {{ $kaizen->status->label() }}">
                                        {{ $kaizen->status->label() }}
                                    </span>
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
                <div class="kf-list-pagination">
                    <div class="kf-list-summary">
                        {{ $reviewedTransitions->firstItem() }}&ndash;{{ $reviewedTransitions->lastItem() }} / {{ $reviewedTransitions->total() }} kayıt
                    </div>
                    <div>{{ $reviewedTransitions->links() }}</div>
                </div>
            @else
                <div class="kf-list-pagination" style="justify-content: center;">
                    <span class="kf-list-summary">Toplam {{ $reviewedTransitions->total() }} kayıt gösteriliyor.</span>
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
