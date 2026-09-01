@extends('layouts.app')

@section('title', 'Yönetim Dashboardu')

@section('content')
<x-page-header 
    title="Yönetim Dashboardu" 
    subtitle="Yetkiniz dahilindeki Kaizen performansını ve fayda göstergelerini izleyin. Rakamlar yalnızca erişim yetkiniz bulunan Kaizen kayıtlarını içerir."
>
    <x-slot:actions>
        <a href="{{ route('reports.kaizens.csv', request()->only(['date_from', 'date_to', 'department_id', 'category_id', 'status'])) }}" class="kf-btn kf-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="me-2">
                <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM.354 11.646a.5.5 0 0 0 0 .708l3 3a.5.5 0 0 0 .708-.708L1.707 12.5H8a.5.5 0 0 0 0-1H1.707l2.355-2.146a.5.5 0 1 0-.708-.708l-3 3Z"/>
            </svg>
            CSV Dışa Aktar
        </a>
    </x-slot:actions>
</x-page-header>

{{-- Filter Section --}}
<div class="kf-card mb-4">
    <div class="kf-card-body pb-3">
        <form method="GET" action="{{ route('dashboard.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-2">
                <label for="date_from" class="kf-form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control kf-form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="col-12 col-md-2">
                <label for="date_to" class="kf-form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control kf-form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-md-2">
                <label for="department_id" class="kf-form-label">Departman</label>
                <select class="form-select kf-form-control" id="department_id" name="department_id">
                    <option value="">Tümü</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }} {{ !$department->is_active ? '(Pasif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="category_id" class="kf-form-label">Kategori</label>
                <select class="form-select kf-form-control" id="category_id" name="category_id">
                    <option value="">Tümü</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }} {{ !$category->is_active ? '(Pasif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="status" class="kf-form-label">Durum</label>
                <select class="form-select kf-form-control" id="status" name="status">
                    <option value="">Tümü</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex flex-column gap-2 flex-md-row">
                <button type="submit" class="kf-btn kf-btn-primary w-100 px-2" title="Filtrele">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                </button>
                <a href="{{ route('dashboard.index') }}" class="kf-btn kf-btn-secondary w-100 px-2" title="Temizle">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </a>
            </div>
        </form>
    </div>
</div>

@if($metrics['total_kaizens'] === 0)
    <x-empty-state 
        title="Veri Bulunamadı" 
        description="Seçilen filtrelerle eşleşen Kaizen kaydı bulunamadı."
    />
@endif

{{-- Top KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kf-card h-100 mb-0" style="border-top: 3px solid var(--kf-primary);">
            <div class="kf-card-body">
                <div class="text-uppercase text-muted small fw-bold mb-1">Görüntülenebilir Kaizen</div>
                <div class="display-6 fw-bold text-dark">{{ $metrics['total_kaizens'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kf-card h-100 mb-0" style="border-top: 3px solid var(--kf-warning);">
            <div class="kf-card-body">
                <div class="text-uppercase text-muted small fw-bold mb-1">Süreçte</div>
                <div class="display-6 fw-bold text-dark">{{ $metrics['in_process_kaizens'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kf-card h-100 mb-0" style="border-top: 3px solid var(--kf-success);">
            <div class="kf-card-body">
                <div class="text-uppercase text-muted small fw-bold mb-1">Tamamlanan</div>
                <div class="display-6 fw-bold text-dark">{{ $metrics['completed_kaizens'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kf-card h-100 mb-0" style="border-top: 3px solid var(--kf-danger);">
            <div class="kf-card-body">
                <div class="text-uppercase text-muted small fw-bold mb-1">Geciken Uygulama</div>
                <div class="display-6 fw-bold text-dark">{{ $metrics['overdue_kaizens'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Distributions --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
        <x-section-card title="Durum Dağılımı" class="h-100 mb-0">
            @if(empty($metrics['status_distribution']) || $metrics['total_kaizens'] === 0)
                <p class="text-muted text-center my-4 small">Veri bulunamadı.</p>
            @else
                @foreach($metrics['status_distribution'] as $status)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-dark">{{ $status['label'] }}</span>
                            <span class="small text-muted">{{ $status['count'] }} ({{ $status['percentage'] }}%)</span>
                        </div>
                        <div class="progress" style="height: 6px; background-color: var(--kf-surface-muted);">
                            <div class="progress-bar" role="progressbar" style="width: {{ $status['percentage'] }}%; background-color: var(--kf-primary);" aria-valuenow="{{ $status['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @endforeach
            @endif
        </x-section-card>
    </div>

    <div class="col-12 col-lg-4">
        <x-section-card title="Departman Dağılımı" class="h-100 mb-0">
            @if(empty($metrics['department_breakdown']) || $metrics['total_kaizens'] === 0)
                <p class="text-muted text-center my-4 small">Veri bulunamadı.</p>
            @else
                @php $maxDeptCount = collect($metrics['department_breakdown'])->max('count'); @endphp
                @foreach(array_slice($metrics['department_breakdown'], 0, 8) as $dept)
                    @php $percent = $maxDeptCount > 0 ? ($dept['count'] / $maxDeptCount) * 100 : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-dark text-truncate pe-2">{{ $dept['name'] }}</span>
                            <span class="small fw-bold">{{ $dept['count'] }}</span>
                        </div>
                        <div class="progress" style="height: 6px; background-color: var(--kf-surface-muted);">
                            <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%; background-color: var(--kf-info);" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @endforeach
            @endif
        </x-section-card>
    </div>

    <div class="col-12 col-lg-4">
        <x-section-card title="Kategori Dağılımı" class="h-100 mb-0">
            @if(empty($metrics['category_breakdown']) || $metrics['total_kaizens'] === 0)
                <p class="text-muted text-center my-4 small">Veri bulunamadı.</p>
            @else
                @php $maxCatCount = collect($metrics['category_breakdown'])->max('count'); @endphp
                @foreach(array_slice($metrics['category_breakdown'], 0, 8) as $cat)
                    @php $percent = $maxCatCount > 0 ? ($cat['count'] / $maxCatCount) * 100 : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-dark text-truncate pe-2">{{ $cat['name'] }}</span>
                            <span class="small fw-bold">{{ $cat['count'] }}</span>
                        </div>
                        <div class="progress" style="height: 6px; background-color: var(--kf-surface-muted);">
                            <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%; background-color: var(--kf-success);" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @endforeach
            @endif
        </x-section-card>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Benefit Table --}}
    <div class="col-12 col-lg-8">
        <x-section-card title="Fayda Performansı" class="h-100 mb-0" :no-padding="true">
            @if(empty($metrics['structured_benefits']) || $metrics['total_kaizens'] === 0)
                <p class="text-muted text-center my-4 small">Fayda kaydı bulunamadı.</p>
            @else
                <div class="table-responsive">
                    <table class="kf-table">
                        <thead>
                            <tr>
                                <th>Fayda Türü</th>
                                <th class="text-end">Beklenen</th>
                                <th class="text-end">Gerçekleşen</th>
                                <th>Birim</th>
                                <th class="text-end">Ölçülen Kaizen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($metrics['structured_benefits'] as $benefit)
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $benefit['name'] }}</span>
                                        @if(!$benefit['is_active'])
                                            <span class="badge bg-secondary ms-1 small">Pasif</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-medium">
                                        {{ $benefit['expected_total'] !== null ? number_format($benefit['expected_total'], 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="text-end fw-medium">
                                        {{ $benefit['realized_total'] !== null ? number_format($benefit['realized_total'], 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="small">{{ $benefit['unit_label'] }}</td>
                                    <td class="text-end">{{ $benefit['kaizen_count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-section-card>
    </div>

    {{-- Monthly Trend --}}
    <div class="col-12 col-lg-4">
        <x-section-card title="Aylık Trend (Son 12 Ay)" class="h-100 mb-0">
            @if(empty($metrics['monthly_trend']))
                <p class="text-muted text-center my-4 small">Veri bulunamadı.</p>
            @else
                @php $maxMonthCount = collect($metrics['monthly_trend'])->max('count'); @endphp
                <div class="d-flex flex-column justify-content-end h-100">
                    @foreach($metrics['monthly_trend'] as $month)
                        @php $percent = $maxMonthCount > 0 ? ($month['count'] / $maxMonthCount) * 100 : 0; @endphp
                        <div class="d-flex align-items-center mb-2 gap-2">
                            <div style="width: 70px;" class="small text-muted text-truncate">{{ $month['label'] }}</div>
                            <div class="flex-grow-1">
                                <div class="progress" style="height: 8px; background-color: var(--kf-surface-muted);">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%; background-color: var(--kf-primary);" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div style="width: 30px;" class="text-end small fw-bold">{{ $month['count'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-section-card>
    </div>
</div>
@endsection
