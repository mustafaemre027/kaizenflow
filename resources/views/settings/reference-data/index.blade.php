@extends('layouts.app')

@section('title', 'Referans Verileri Yönetimi')

@section('content')
<div class="kf-page-header">
    <span class="kf-page-eyebrow">YAPILANDIRMA</span>
    <h1 class="kf-page-title">Yönetim Merkezi</h1>
    <p class="kf-page-desc text-muted">
        KaizenFlow'un kurumunuza göre değişebilen referans verilerini yönetin.<br>
        <span class="small opacity-75">Burada yapılan değişiklikler yeni Kaizen formlarına ve ilgili uygulama alanlarına otomatik olarak yansır.</span>
    </p>
</div>

@if (session('error'))
    <div class="kf-alert kf-alert-danger mb-4" role="alert">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="me-2">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
        </svg>
        {{ session('error') }}
    </div>
@endif

<!-- KATEGORİLER BÖLÜMÜ -->
@can('viewAny', App\Models\Category::class)
<div class="kf-panel mb-5">
    <div class="kf-panel-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h2 class="kf-panel-title fs-4 mb-1">Kategoriler</h2>
            <p class="text-muted small mb-0">Kaizen fikirlerini sınıflandırmak için kullanılan kategorileri yönetin.</p>
        </div>
        <div class="d-flex gap-3 text-sm">
            <div class="text-center px-3 border-end">
                <div class="text-muted small fw-medium">Toplam</div>
                <div class="fs-5 fw-bold">{{ $categories->total() }}</div>
            </div>
            <div class="text-center px-3 border-end">
                <div class="text-muted small fw-medium">Aktif</div>
                <div class="fs-5 fw-bold text-success">{{ App\Models\Category::where('is_active', true)->count() }}</div>
            </div>
            <div class="text-center px-3">
                <div class="text-muted small fw-medium">Pasif</div>
                <div class="fs-5 fw-bold text-secondary">{{ App\Models\Category::where('is_active', false)->count() }}</div>
            </div>
        </div>
    </div>
    
    <div class="kf-panel-body p-0">
        <!-- CREATE KATEGORİ -->
        <div class="bg-light border-bottom p-4">
            <form action="{{ route('settings.categories.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-medium text-dark">Kategori Adı</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium text-dark">Kod</label>
                        <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                        <div class="form-text mt-1" style="font-size: 0.75rem;">Sistemde benzersiz teknik tanımlayıcı olarak kullanılır.</div>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium text-dark">Açıklama <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary px-4">Kategori Ekle</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- SEARCH & FİLTRE KATEGORİ -->
        <div class="p-4 border-bottom">
            <form action="{{ route('settings.reference-data.index') }}" method="GET" class="row g-2 align-items-center">
                <!-- Keep department query state if present -->
                @if(request()->filled('department_q')) <input type="hidden" name="department_q" value="{{ request('department_q') }}"> @endif
                @if(request()->filled('department_status')) <input type="hidden" name="department_status" value="{{ request('department_status') }}"> @endif
                @if(request()->filled('department_page')) <input type="hidden" name="department_page" value="{{ request('department_page') }}"> @endif

                <div class="col-md-5">
                    <input type="text" name="category_q" class="form-control" placeholder="Kategori ara (ad, kod)..." value="{{ request('category_q') }}">
                </div>
                <div class="col-md-3">
                    <select name="category_status" class="form-select">
                        <option value="">Tümü</option>
                        <option value="active" {{ request('category_status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('category_status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filtrele</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('settings.reference-data.index', request()->except(['category_q', 'category_status', 'category_page'])) }}" class="btn btn-light w-100 border">Temizle</a>
                </div>
            </form>
        </div>

        <!-- LİSTE KATEGORİ -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 850px;">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Ad</th>
                        <th class="py-3">Kod</th>
                        <th class="py-3">Durum</th>
                        <th class="py-3 text-center">Kaizen Kullanımı</th>
                        <th class="py-3">Son Güncelleme</th>
                        <th class="px-4 py-3 text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td class="px-4 py-3 fw-medium text-dark">
                            {{ $category->name }}
                            @if($category->description)
                                <div class="text-muted small fw-normal text-truncate" style="max-width: 200px;" title="{{ $category->description }}">{{ $category->description }}</div>
                            @endif
                        </td>
                        <td class="py-3"><span class="badge bg-light text-dark border font-monospace">{{ $category->code }}</span></td>
                        <td class="py-3">
                            @if($category->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" aria-label="Kategori durumu: Aktif">Aktif</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" aria-label="Kategori durumu: Pasif">Pasif</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-light text-secondary border px-2 py-1">{{ $category->kaizens_count }}</span>
                        </td>
                        <td class="py-3 text-muted small">{{ $category->updated_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('settings.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">Düzenle</a>
                                <form action="{{ route('settings.categories.status', $category) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($category->is_active)
                                        <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bu kategoriyi pasife almak istediğinize emin misiniz? Mevcut Kaizen kayıtlarında görünmeye devam eder.')">Pasife Al</button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success">Aktifleştir</button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            @if(request('category_q') || request('category_status'))
                                Aramanıza uygun kategori bulunamadı.
                            @else
                                Henüz kategori tanımlanmadı.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($categories->hasPages())
        <div class="p-4 border-top">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>
@endcan


<!-- DEPARTMANLAR BÖLÜMÜ -->
@can('viewAny', App\Models\Department::class)
<div class="kf-panel mb-5">
    <div class="kf-panel-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h2 class="kf-panel-title fs-4 mb-1">Departmanlar</h2>
            <p class="text-muted small mb-0">Kullanıcıların ve Kaizen kayıtlarının bağlı olduğu organizasyon birimlerini yönetin.</p>
        </div>
        <div class="d-flex gap-3 text-sm">
            <div class="text-center px-3 border-end">
                <div class="text-muted small fw-medium">Toplam</div>
                <div class="fs-5 fw-bold">{{ $departments->total() }}</div>
            </div>
            <div class="text-center px-3 border-end">
                <div class="text-muted small fw-medium">Aktif</div>
                <div class="fs-5 fw-bold text-success">{{ App\Models\Department::where('is_active', true)->count() }}</div>
            </div>
            <div class="text-center px-3">
                <div class="text-muted small fw-medium">Pasif</div>
                <div class="fs-5 fw-bold text-secondary">{{ App\Models\Department::where('is_active', false)->count() }}</div>
            </div>
        </div>
    </div>
    
    <div class="kf-panel-body p-0">
        <!-- CREATE DEPARTMAN -->
        <div class="bg-light border-bottom p-4">
            <form action="{{ route('settings.departments.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-medium text-dark">Departman Adı</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium text-dark">Kod</label>
                        <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                        <div class="form-text mt-1" style="font-size: 0.75rem;">Sistemde benzersiz teknik tanımlayıcı olarak kullanılır.</div>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium text-dark">Açıklama <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary px-4">Departman Ekle</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- SEARCH & FİLTRE DEPARTMAN -->
        <div class="p-4 border-bottom">
            <form action="{{ route('settings.reference-data.index') }}" method="GET" class="row g-2 align-items-center">
                <!-- Keep category query state if present -->
                @if(request()->filled('category_q')) <input type="hidden" name="category_q" value="{{ request('category_q') }}"> @endif
                @if(request()->filled('category_status')) <input type="hidden" name="category_status" value="{{ request('category_status') }}"> @endif
                @if(request()->filled('category_page')) <input type="hidden" name="category_page" value="{{ request('category_page') }}"> @endif

                <div class="col-md-5">
                    <input type="text" name="department_q" class="form-control" placeholder="Departman ara (ad, kod)..." value="{{ request('department_q') }}">
                </div>
                <div class="col-md-3">
                    <select name="department_status" class="form-select">
                        <option value="">Tümü</option>
                        <option value="active" {{ request('department_status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('department_status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filtrele</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('settings.reference-data.index', request()->except(['department_q', 'department_status', 'department_page'])) }}" class="btn btn-light w-100 border">Temizle</a>
                </div>
            </form>
        </div>

        <!-- LİSTE DEPARTMAN -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Ad</th>
                        <th class="py-3">Kod</th>
                        <th class="py-3">Durum</th>
                        <th class="py-3 text-center">Kullanıcı (Aktif / Toplam)</th>
                        <th class="py-3 text-center">Kaizen Kullanımı</th>
                        <th class="py-3">Son Güncelleme</th>
                        <th class="px-4 py-3 text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                    <tr>
                        <td class="px-4 py-3 fw-medium text-dark">
                            {{ $department->name }}
                            @if($department->description)
                                <div class="text-muted small fw-normal text-truncate" style="max-width: 200px;" title="{{ $department->description }}">{{ $department->description }}</div>
                            @endif
                        </td>
                        <td class="py-3"><span class="badge bg-light text-dark border font-monospace">{{ $department->code }}</span></td>
                        <td class="py-3">
                            @if($department->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" aria-label="Departman durumu: Aktif">Aktif</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" aria-label="Departman durumu: Pasif">Pasif</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            @if($department->active_users_count > 0)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    {{ $department->active_users_count }} aktif / {{ $department->users_count }}
                                </span>
                            @else
                                <span class="badge bg-light text-secondary border px-2 py-1">{{ $department->active_users_count }} / {{ $department->users_count }}</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-light text-secondary border px-2 py-1">{{ $department->kaizens_count }}</span>
                        </td>
                        <td class="py-3 text-muted small">{{ $department->updated_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('settings.departments.edit', $department) }}" class="btn btn-sm btn-outline-secondary">Düzenle</a>
                                <form action="{{ route('settings.departments.status', $department) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($department->is_active)
                                        @if($department->active_users_count > 0)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled aria-disabled="true" title="Aktif kullanıcısı bulunan departman pasife alınamaz. ({{ $department->active_users_count }} aktif kullanıcı var)">Pasife Al</button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bu departmanı pasife almak istediğinize emin misiniz?')">Pasife Al</button>
                                        @endif
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success">Aktifleştir</button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            @if(request('department_q') || request('department_status'))
                                Aramanıza uygun departman bulunamadı.
                            @else
                                Henüz departman tanımlanmadı.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($departments->hasPages())
        <div class="p-4 border-top">
            {{ $departments->links() }}
        </div>
        @endif
    </div>
</div>
@endcan

<!-- FAYDA TÜRLERİ BÖLÜMÜ -->
@can('viewAny', App\Models\BenefitType::class)
<div class="kf-panel mb-5">
    <div class="kf-panel-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h2 class="kf-panel-title fs-4 mb-1">Fayda Türleri</h2>
            <p class="text-muted small mb-0">Kaizen gerçekleşme raporlarında seçilebilecek fayda metriklerini yönetin.</p>
        </div>
        <div class="d-flex gap-3 text-sm">
            <div class="text-center px-3 border-end">
                <div class="text-muted small fw-medium">Toplam</div>
                <div class="fs-5 fw-bold">{{ $benefitTypes->total() }}</div>
            </div>
            <div class="text-center px-3 border-end">
                <div class="text-muted small fw-medium">Aktif</div>
                <div class="fs-5 fw-bold text-success">{{ App\Models\BenefitType::where('is_active', true)->count() }}</div>
            </div>
            <div class="text-center px-3">
                <div class="text-muted small fw-medium">Pasif</div>
                <div class="fs-5 fw-bold text-secondary">{{ App\Models\BenefitType::where('is_active', false)->count() }}</div>
            </div>
        </div>
    </div>
    
    <div class="kf-panel-body p-0">
        <!-- CREATE FAYDA TÜRÜ -->
        @can('create', App\Models\BenefitType::class)
        <div class="bg-light border-bottom p-4">
            <form action="{{ route('settings.benefit-types.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-medium text-dark">Fayda Türü Adı</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium text-dark">Birim <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                        <input type="text" name="unit_label" class="form-control @error('unit_label') is-invalid @enderror" value="{{ old('unit_label') }}">
                        <div class="form-text mt-1" style="font-size: 0.75rem;">Örn. saat, TL, adet, %, kWh.</div>
                        @error('unit_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary px-4">Fayda Türü Ekle</button>
                    </div>
                </div>
            </form>
        </div>
        @endcan

        <!-- SEARCH & FİLTRE FAYDA TÜRÜ -->
        <div class="p-4 border-bottom">
            <form action="{{ route('settings.reference-data.index') }}" method="GET" class="row g-2 align-items-center">
                @if(request()->filled('category_q')) <input type="hidden" name="category_q" value="{{ request('category_q') }}"> @endif
                @if(request()->filled('category_status')) <input type="hidden" name="category_status" value="{{ request('category_status') }}"> @endif
                @if(request()->filled('category_page')) <input type="hidden" name="category_page" value="{{ request('category_page') }}"> @endif
                
                @if(request()->filled('department_q')) <input type="hidden" name="department_q" value="{{ request('department_q') }}"> @endif
                @if(request()->filled('department_status')) <input type="hidden" name="department_status" value="{{ request('department_status') }}"> @endif
                @if(request()->filled('department_page')) <input type="hidden" name="department_page" value="{{ request('department_page') }}"> @endif

                <div class="col-md-5">
                    <input type="text" name="benefit_type_q" class="form-control" placeholder="Fayda türü ara..." value="{{ request('benefit_type_q') }}">
                </div>
                <div class="col-md-3">
                    <select name="benefit_type_status" class="form-select">
                        <option value="">Tümü</option>
                        <option value="active" {{ request('benefit_type_status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('benefit_type_status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filtrele</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('settings.reference-data.index', request()->except(['benefit_type_q', 'benefit_type_status', 'benefit_type_page'])) }}" class="btn btn-light w-100 border">Temizle</a>
                </div>
            </form>
        </div>

        <!-- LİSTE FAYDA TÜRÜ -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 850px;">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Ad</th>
                        <th class="py-3">Birim</th>
                        <th class="py-3">Durum</th>
                        <th class="py-3 text-center">Kaizen Kullanımı</th>
                        <th class="py-3">Son Güncelleme</th>
                        <th class="px-4 py-3 text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($benefitTypes as $benefitType)
                    <tr>
                        <td class="px-4 py-3 fw-medium text-dark">
                            {{ $benefitType->name }}
                        </td>
                        <td class="py-3">
                            @if($benefitType->unit_label)
                                <span class="badge bg-light text-dark border">{{ $benefitType->unit_label }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($benefitType->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" aria-label="Durum: Aktif">Aktif</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" aria-label="Durum: Pasif">Pasif</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-light text-secondary border px-2 py-1">{{ $benefitType->kaizen_benefits_count }}</span>
                        </td>
                        <td class="py-3 text-muted small">{{ $benefitType->updated_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @can('update', $benefitType)
                                    <a href="{{ route('settings.benefit-types.edit', $benefitType) }}" class="btn btn-sm btn-outline-secondary">Düzenle</a>
                                    <form action="{{ route('settings.benefit-types.status', $benefitType) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($benefitType->is_active)
                                            <input type="hidden" name="is_active" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bu fayda türünü pasife almak istediğinize emin misiniz? Mevcut Kaizen kayıtlarında görünmeye devam eder, ancak yenilerde seçilemez.')">Pasife Al</button>
                                        @else
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Aktifleştir</button>
                                        @endif
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            @if(request('benefit_type_q') || request('benefit_type_status'))
                                Aramanıza uygun fayda türü bulunamadı.
                            @else
                                Henüz fayda türü tanımlanmadı.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($benefitTypes->hasPages())
        <div class="p-4 border-top">
            {{ $benefitTypes->links() }}
        </div>
        @endif
    </div>
</div>
@endcan

@endsection
