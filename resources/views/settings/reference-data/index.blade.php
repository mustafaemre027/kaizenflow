@extends('layouts.app')

@section('title', 'Referans Verileri Yönetimi')

@section('content')
<div class="kf-page-header">
    <span class="kf-page-eyebrow">Yapılandırma</span>
    <h1 class="kf-page-title">Referans Verileri</h1>
    <p class="kf-page-desc">KaizenFlow'un kategori ve departman yapılandırmasını yönetin.</p>
</div>

@if (session('error'))
    <div class="kf-alert kf-alert-danger" role="alert" style="background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
        {{ session('error') }}
    </div>
@endif

<div class="row g-4">
    <div class="col-xl-6">
        <div class="kf-panel h-100">
            <div class="kf-panel-header d-flex justify-content-between align-items-center">
                <h2 class="kf-panel-title">Kategoriler</h2>
                <span class="badge bg-secondary rounded-pill">{{ $categories->count() }}</span>
            </div>
            <div class="kf-panel-body p-4">
                <form action="{{ route('settings.categories.store') }}" method="POST" class="mb-4 bg-light p-3 rounded border">
                    @csrf
                    <h5 class="mb-3 fs-6 text-muted">Yeni Kategori Ekle</h5>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Kategori Adı</label>
                            <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Kod (Örn: KALITE)</label>
                            <input type="text" name="code" class="form-control form-control-sm @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Açıklama</label>
                            <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Ekle</button>
                        </div>
                    </div>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ad</th>
                                <th>Kod</th>
                                <th>Durum</th>
                                <th>Kaizen</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                            <tr>
                                <td class="fw-medium">{{ $category->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $category->code }}</span></td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Pasif</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-secondary">{{ $category->kaizens_count }}</span></td>
                                <td class="text-end">
                                    <form action="{{ route('settings.categories.status', $category) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($category->is_active)
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Pasif Yap">Pasif</button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Aktif Yap">Aktif</button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @if($categories->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Henüz kategori eklenmemiş.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="kf-panel h-100">
            <div class="kf-panel-header d-flex justify-content-between align-items-center">
                <h2 class="kf-panel-title">Departmanlar</h2>
                <span class="badge bg-secondary rounded-pill">{{ $departments->count() }}</span>
            </div>
            <div class="kf-panel-body p-4">
                <form action="{{ route('settings.departments.store') }}" method="POST" class="mb-4 bg-light p-3 rounded border">
                    @csrf
                    <h5 class="mb-3 fs-6 text-muted">Yeni Departman Ekle</h5>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Departman Adı</label>
                            <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Kod (Örn: IT)</label>
                            <input type="text" name="code" class="form-control form-control-sm @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Açıklama</label>
                            <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Ekle</button>
                        </div>
                    </div>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ad</th>
                                <th>Kod</th>
                                <th>Durum</th>
                                <th>Kullanıcı (Aktif/Top.)</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departments as $department)
                            <tr>
                                <td class="fw-medium">{{ $department->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $department->code }}</span></td>
                                <td>
                                    @if($department->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Pasif</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $department->active_users_count > 0 ? 'bg-primary' : 'bg-light text-secondary' }}">{{ $department->active_users_count }} / {{ $department->users_count }}</span>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('settings.departments.status', $department) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($department->is_active)
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Pasif Yap" {{ $department->active_users_count > 0 ? 'disabled' : '' }}>Pasif</button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Aktif Yap">Aktif</button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @if($departments->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Henüz departman eklenmemiş.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
