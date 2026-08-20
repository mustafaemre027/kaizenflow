@extends('layouts.app')

@section('title', 'Islem Gecmisi')

@section('content')
<div class="kf-list-page">
    {{-- Page Header --}}
    <div class="kf-list-header">
        <div>
            <span class="kf-list-context">GECMIS ARSIVI</span>
            <h1 class="kf-list-title">Islem Gecmisi</h1>
            <p class="kf-list-desc">Kaizen ve degerlendirme sureclerinizdeki gecmis islemleri goruntuleyin.</p>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="mb-4">
        <ul class="nav kf-history-tabs" role="tablist" aria-label="Gecmis sekmeleri">
            <li class="nav-item" role="presentation">
                <a href="{{ route('history.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'created'])) }}"
                   class="kf-history-tab {{ $activeTab === 'created' ? 'active' : '' }}"
                   role="tab"
                   aria-selected="{{ $activeTab === 'created' ? 'true' : 'false' }}"
                   id="tab-created"
                   aria-controls="panel-created">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    Olusturduklarim
                </a>
            </li>
            @if($canAccessReviewedHistory)
            <li class="nav-item" role="presentation">
                <a href="{{ route('history.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'reviewed'])) }}"
                   class="kf-history-tab {{ $activeTab === 'reviewed' ? 'active' : '' }}"
                   role="tab"
                   aria-selected="{{ $activeTab === 'reviewed' ? 'true' : 'false' }}"
                   id="tab-reviewed"
                   aria-controls="panel-reviewed">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    Degerlendirdiklerim
                </a>
            </li>
            @endif
        </ul>
    </div>

    {{-- PANEL: Olusturduklarim --}}
    @if($activeTab === 'created')
    <div id="panel-created" role="tabpanel" aria-labelledby="tab-created">
        {{-- Filter --}}
        <div class="kf-list-filter-panel mb-4">
            <form action="{{ route('history.index') }}" method="GET">
                <input type="hidden" name="tab" value="created">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="q-created" class="form-label">Arama</label>
                        <input type="text" name="q" id="q-created" class="kf-form-control"
                               value="{{ $filters['q'] ?? '' }}" placeholder="Kaizen kodu veya baslik...">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="status-created" class="form-label">Durum</label>
                        <select name="status" id="status-created" class="kf-form-control">
                            <option value="">Tum durumlar</option>
                            @foreach(\App\Enums\KaizenStatus::cases() as $st)
                                <option value="{{ $st->value }}" {{ ($filters['status'] ?? '') === $st->value ? 'selected' : '' }}>
                                    {{ $st->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="category-created" class="form-label">Kategori</label>
                        <select name="category_id" id="category-created" class="kf-form-control">
                            <option value="">Tum kategoriler</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="date-from-created" class="form-label">Baslangic</label>
                        <input type="date" name="date_from" id="date-from-created" class="kf-form-control"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="date-to-created" class="form-label">Bitis</label>
                        <input type="date" name="date_to" id="date-to-created" class="kf-form-control"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-2">
                        <button type="submit" class="kf-btn kf-btn-primary w-100">Filtrele</button>
                    </div>
                </div>
                @if(!empty(array_filter([$filters['q'] ?? null, $filters['status'] ?? null, $filters['category_id'] ?? null, $filters['date_from'] ?? null, $filters['date_to'] ?? null])))
                    <div class="mt-2">
                        <a href="{{ route('history.index', ['tab' => 'created']) }}" class="text-decoration-none text-muted" style="font-size: 0.9rem; font-weight: 500;">
                            Filtreleri Temizle
                        </a>
                    </div>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="kf-list-surface">
            @if($createdKaizens->isEmpty())
                <div class="kf-list-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" style="opacity:0.4; margin-bottom: 1rem;">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    <h3 class="kf-list-empty-title">Henuz olusturdugunuz bir Kaizen bulunmuyor.</h3>
                    <p class="kf-list-empty-desc">Yeni bir iyilestirme fikri olusturarak baslayabilirsiniz.</p>
                    <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">Yeni Kaizen Olustur</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table kf-list-table" aria-label="Olusturugum Kaizenler">
                        <thead>
                            <tr>
                                <th scope="col">Kaizen</th>
                                <th scope="col">Kategori / Departman</th>
                                <th scope="col">Durum</th>
                                <th scope="col" class="kf-hide-mobile">Mevcut / Son Asama</th>
                                <th scope="col" class="kf-hide-mobile">Gonderim Tarihi</th>
                                <th scope="col" class="kf-hide-mobile">Son Islem</th>
                                <th scope="col" class="text-end">Incele</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($createdKaizens as $kaizen)
                                <tr>
                                    <td>
                                        <div class="fw-bold" style="color: var(--kf-primary-dark); font-family: monospace; font-size: 0.9rem;">{{ $kaizen->code }}</div>
                                        <div class="text-truncate" style="max-width: 220px;" title="{{ $kaizen->title }}">{{ $kaizen->title }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark">{{ $kaizen->category->name ?? '-' }}</div>
                                        <div class="text-secondary" style="font-size: 0.85rem;">{{ $kaizen->department->name ?? '-' }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $statusVariant = match($kaizen->status) {
                                                \App\Enums\KaizenStatus::APPROVED => 'success',
                                                \App\Enums\KaizenStatus::REJECTED => 'danger',
                                                \App\Enums\KaizenStatus::REVISION_REQUESTED => 'warning',
                                                \App\Enums\KaizenStatus::SUBMITTED => 'primary',
                                                \App\Enums\KaizenStatus::IN_PROGRESS, \App\Enums\KaizenStatus::COMPLETED => 'info',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="kf-badge kf-badge-{{ $statusVariant }}" aria-label="Durum: {{ $kaizen->status->label() }}">
                                            {{ $kaizen->status->label() }}
                                        </span>
                                    </td>
                                    <td class="kf-hide-mobile text-secondary" style="font-size: 0.9rem;">
                                        {{ $kaizen->workflowInstance?->currentStage?->name ?? '-' }}
                                    </td>
                                    <td class="kf-hide-mobile text-secondary" style="font-size: 0.9rem;">
                                        {{ $kaizen->submitted_at?->format('d.m.Y H:i') ?? '-' }}
                                    </td>
                                    <td class="kf-hide-mobile text-secondary" style="font-size: 0.9rem;">
                                        {{ $kaizen->updated_at?->format('d.m.Y H:i') ?? '-' }}
                                    </td>
                                    <td class="text-end">
                                        @can('view', $kaizen)
                                            <a href="{{ route('kaizens.show', $kaizen) }}"
                                               class="kf-btn kf-btn-secondary"
                                               style="padding: 0.35rem 0.75rem; font-size: 0.85rem;"
                                               aria-label="{{ $kaizen->code }} detaylarini goruntule">
                                                Incele
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

                @if($createdKaizens->hasPages())
                    <div class="kf-list-pagination">
                        <div class="kf-list-summary">
                            {{ $createdKaizens->firstItem() }}&ndash;{{ $createdKaizens->lastItem() }} / {{ $createdKaizens->total() }} kayit
                        </div>
                        <div>{{ $createdKaizens->links() }}</div>
                    </div>
                @else
                    <div class="kf-list-pagination" style="justify-content: center;">
                        <span class="kf-list-summary">Toplam {{ $createdKaizens->total() }} kayit gosteriliyor.</span>
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endif

    {{-- PANEL: Degerlendirdiklerim --}}
    @if($activeTab === 'reviewed')
    <div id="panel-reviewed" role="tabpanel" aria-labelledby="tab-reviewed">
        {{-- Filter --}}
        <div class="kf-list-filter-panel mb-4">
            <form action="{{ route('history.index') }}" method="GET">
                <input type="hidden" name="tab" value="reviewed">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="q-reviewed" class="form-label">Arama</label>
                        <input type="text" name="q" id="q-reviewed" class="kf-form-control"
                               value="{{ $filters['q'] ?? '' }}" placeholder="Kaizen kodu veya baslik...">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="action-reviewed" class="form-label">Islem Turu</label>
                        <select name="action" id="action-reviewed" class="kf-form-control">
                            <option value="">Tum islemler</option>
                            @foreach(\App\Enums\WorkflowAction::reviewActions() as $act)
                                <option value="{{ $act->value }}" {{ ($filters['action'] ?? '') === $act->value ? 'selected' : '' }}>
                                    {{ $act->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="date-from-reviewed" class="form-label">Baslangic</label>
                        <input type="date" name="date_from" id="date-from-reviewed" class="kf-form-control"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="date-to-reviewed" class="form-label">Bitis</label>
                        <input type="date" name="date_to" id="date-to-reviewed" class="kf-form-control"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="kf-btn kf-btn-primary w-100">Filtrele</button>
                    </div>
                </div>
                @if(!empty(array_filter([$filters['q'] ?? null, $filters['action'] ?? null, $filters['date_from'] ?? null, $filters['date_to'] ?? null])))
                    <div class="mt-2">
                        <a href="{{ route('history.index', ['tab' => 'reviewed']) }}" class="text-decoration-none text-muted" style="font-size: 0.9rem; font-weight: 500;">
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
                    <h3 class="kf-list-empty-title">Henuz tamamladiginiz bir degerlendirme bulunmuyor.</h3>
                    <p class="kf-list-empty-desc">Onay sureclerinde degerlendirme yaptiginizda gecmisiniz burada gorunecek.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table kf-list-table" aria-label="Degerlendirme Gecmisim">
                        <thead>
                            <tr>
                                <th scope="col">Kaizen</th>
                                <th scope="col" class="kf-hide-mobile">Asama</th>
                                <th scope="col">Yaptigim Islem</th>
                                <th scope="col" class="kf-hide-mobile">Islem Tarihi</th>
                                <th scope="col" class="kf-hide-mobile">Son Kaizen Durumu</th>
                                <th scope="col" class="text-end">Incele</th>
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
                                        <span class="kf-badge kf-badge-{{ $badgeVariant }}" aria-label="Islem: {{ $transition->action->label() }}">
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
                                               aria-label="{{ $kaizen->code }} detaylarini goruntule">
                                                Incele
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
                            {{ $reviewedTransitions->firstItem() }}&ndash;{{ $reviewedTransitions->lastItem() }} / {{ $reviewedTransitions->total() }} kayit
                        </div>
                        <div>{{ $reviewedTransitions->links() }}</div>
                    </div>
                @else
                    <div class="kf-list-pagination" style="justify-content: center;">
                        <span class="kf-list-summary">Toplam {{ $reviewedTransitions->total() }} kayit gosteriliyor.</span>
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endif

</div>

@push('styles')
<style>
.kf-history-tabs {
    border-bottom: 2px solid var(--kf-border-light);
    gap: 0;
    margin-bottom: 0;
}
.kf-history-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--kf-text-muted, #6c757d);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color 0.15s ease, border-color 0.15s ease;
    white-space: nowrap;
}
.kf-history-tab:hover {
    color: var(--kf-primary);
    border-bottom-color: var(--kf-primary-subtle, rgba(99,102,241,0.3));
}
.kf-history-tab.active {
    color: var(--kf-primary);
    border-bottom-color: var(--kf-primary);
}
</style>
@endpush
@endsection
