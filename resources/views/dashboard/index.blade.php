@extends('layouts.app')

@section('title', 'Yönetim Dashboardu')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Yönetim Dashboardu</h1>
            <p class="text-muted mb-0">Yetkiniz dahilindeki Kaizen performansını ve fayda göstergelerini izleyin.</p>
            <small class="text-info">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="me-1">
                    <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                Rakamlar yalnızca erişim yetkiniz bulunan Kaizen kayıtlarını içerir.
            </small>
        </div>
        <div>
            <a href="{{ route('reports.kaizens.csv', request()->only(['date_from', 'date_to', 'department_id', 'category_id', 'status'])) }}" class="btn btn-outline-primary d-inline-flex align-items-center">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="me-2">
                    <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM.354 11.646a.5.5 0 0 0 0 .708l3 3a.5.5 0 0 0 .708-.708L1.707 12.5H8a.5.5 0 0 0 0-1H1.707l2.355-2.146a.5.5 0 1 0-.708-.708l-3 3Z"/>
                </svg>
                CSV Dışa Aktar
            </a>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.index') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Başlangıç Tarihi</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Bitiş Tarihi</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label for="department_id" class="form-label">Departman</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Tümü</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }} {{ !$department->is_active ? '(Pasif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Tümü</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }} {{ !$category->is_active ? '(Pasif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Durum</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tümü</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Uygula</button>
                    <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary">Temizle</a>
                </div>
            </form>
        </div>
    </div>

    @if($metrics['total_kaizens'] === 0)
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="me-2" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 7.58172 7.58172 4 12 4ZM13 16C13 16.5523 12.5523 17 12 17C11.4477 17 11 16.5523 11 16C11 15.4477 11.4477 15 12 15C12.5523 15 13 15.4477 13 16ZM12 7C12.5523 7 13 7.44772 13 8V13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13V8C11 7.44772 11.4477 7 12 7Z" clip-rule="evenodd"/>
            </svg>
            <div>
                Seçilen filtrelerle eşleşen Kaizen kaydı bulunamadı.
            </div>
        </div>
    @endif

    {{-- Top KPI Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 py-2" style="border-left: 4px solid var(--kf-primary, #0d6efd);">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Görüntülenebilir Kaizen</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['total_kaizens'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 py-2" style="border-left: 4px solid #f6c23e;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Süreçte</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['in_process_kaizens'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 py-2" style="border-left: 4px solid #1cc88a;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tamamlanan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['completed_kaizens'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100 py-2" style="border-left: 4px solid #e74a3b;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Geciken Uygulama</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['overdue_kaizens'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Distributions --}}
    <div class="row g-4 mb-4">
        {{-- Status Distribution --}}
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Durum Dağılımı</h6>
                </div>
                <div class="card-body">
                    @if(empty($metrics['status_distribution']) || $metrics['total_kaizens'] === 0)
                        <p class="text-muted text-center my-4">Veri bulunamadı.</p>
                    @else
                        @foreach($metrics['status_distribution'] as $status)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-sm font-weight-bold">{{ $status['label'] }}</span>
                                    <span class="text-sm">{{ $status['count'] }} ({{ $status['percentage'] }}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $status['percentage'] }}%" aria-valuenow="{{ $status['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Department Distribution --}}
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Departman Dağılımı</h6>
                </div>
                <div class="card-body">
                    @if(empty($metrics['department_breakdown']) || $metrics['total_kaizens'] === 0)
                        <p class="text-muted text-center my-4">Veri bulunamadı.</p>
                    @else
                        @php
                            $maxDeptCount = collect($metrics['department_breakdown'])->max('count');
                        @endphp
                        @foreach(array_slice($metrics['department_breakdown'], 0, 8) as $dept)
                            @php
                                $percent = $maxDeptCount > 0 ? ($dept['count'] / $maxDeptCount) * 100 : 0;
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-sm font-weight-bold text-truncate" style="max-width: 80%;">{{ $dept['name'] }}</span>
                                    <span class="text-sm fw-bold">{{ $dept['count'] }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Category Distribution --}}
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Kategori Dağılımı</h6>
                </div>
                <div class="card-body">
                    @if(empty($metrics['category_breakdown']) || $metrics['total_kaizens'] === 0)
                        <p class="text-muted text-center my-4">Veri bulunamadı.</p>
                    @else
                        @php
                            $maxCatCount = collect($metrics['category_breakdown'])->max('count');
                        @endphp
                        @foreach(array_slice($metrics['category_breakdown'], 0, 8) as $cat)
                            @php
                                $percent = $maxCatCount > 0 ? ($cat['count'] / $maxCatCount) * 100 : 0;
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-sm font-weight-bold text-truncate" style="max-width: 80%;">{{ $cat['name'] }}</span>
                                    <span class="text-sm fw-bold">{{ $cat['count'] }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Benefit Table --}}
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Fayda Performansı</h6>
                </div>
                <div class="card-body p-0">
                    @if(empty($metrics['structured_benefits']) || $metrics['total_kaizens'] === 0)
                        <p class="text-muted text-center my-4">Fayda kaydı bulunamadı.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-4 py-3">Fayda Türü</th>
                                        <th scope="col" class="text-end py-3">Beklenen</th>
                                        <th scope="col" class="text-end py-3">Gerçekleşen</th>
                                        <th scope="col" class="px-4 py-3">Birim</th>
                                        <th scope="col" class="text-end px-4 py-3">Ölçülen Kaizen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($metrics['structured_benefits'] as $benefit)
                                        <tr>
                                            <td class="px-4">
                                                <span class="font-weight-bold">{{ $benefit['name'] }}</span>
                                                @if(!$benefit['is_active'])
                                                    <span class="badge bg-secondary ms-1">Pasif</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                {{ $benefit['expected_total'] !== null ? number_format($benefit['expected_total'], 2, ',', '.') : '—' }}
                                            </td>
                                            <td class="text-end">
                                                {{ $benefit['realized_total'] !== null ? number_format($benefit['realized_total'], 2, ',', '.') : '—' }}
                                            </td>
                                            <td class="px-4">
                                                {{ $benefit['unit_label'] }}
                                            </td>
                                            <td class="text-end px-4">
                                                {{ $benefit['kaizen_count'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Monthly Trend --}}
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Aylık Trend (Son 12 Ay)</h6>
                </div>
                <div class="card-body">
                    @if(empty($metrics['monthly_trend']))
                        <p class="text-muted text-center my-4">Veri bulunamadı.</p>
                    @else
                        @php
                            $maxMonthCount = collect($metrics['monthly_trend'])->max('count');
                        @endphp
                        <div class="d-flex flex-column justify-content-end h-100 pb-2">
                            @foreach($metrics['monthly_trend'] as $month)
                                @php
                                    $percent = $maxMonthCount > 0 ? ($month['count'] / $maxMonthCount) * 100 : 0;
                                @endphp
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 80px;" class="text-sm text-muted text-truncate">{{ $month['label'] }}</div>
                                    <div class="flex-grow-1 mx-2">
                                        <div class="progress" style="height: 12px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div style="width: 30px;" class="text-end text-sm fw-bold">{{ $month['count'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
