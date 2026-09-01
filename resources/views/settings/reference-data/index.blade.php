@extends('layouts.app')

@section('title', 'Referans Verileri Yönetimi')

@section('content')
<x-page-header 
    title="Yönetim Merkezi" 
    subtitle="KaizenFlow'un kurumunuza göre değişebilen referans verilerini yönetin."
/>

@if (session('error'))
    <x-flash-messages />
@endif

<!-- KATEGORİLER BÖLÜMÜ -->
@can('viewAny', App\Models\Category::class)
<x-section-card class="mb-5" :no-padding="true">
    <x-slot:title>Kategoriler</x-slot:title>
    <x-slot:description>Kaizen fikirlerini sınıflandırmak için kullanılan kategorileri yönetin.</x-slot:description>
    
    <!-- İstatistikler -->
    <div class="d-flex gap-4 p-4 bg-light border-bottom">
        <div>
            <div class="text-muted small fw-medium text-uppercase mb-1">Toplam</div>
            <div class="fs-4 fw-bold text-dark">{{ $categories->total() }}</div>
        </div>
        <div class="border-start ps-4">
            <div class="text-muted small fw-medium text-uppercase mb-1">Aktif</div>
            <div class="fs-4 fw-bold text-success">{{ App\Models\Category::where('is_active', true)->count() }}</div>
        </div>
        <div class="border-start ps-4">
            <div class="text-muted small fw-medium text-uppercase mb-1">Pasif</div>
            <div class="fs-4 fw-bold text-secondary">{{ App\Models\Category::where('is_active', false)->count() }}</div>
        </div>
    </div>
    
    <!-- CREATE KATEGORİ -->
    <div class="p-4 border-bottom" style="background-color: var(--kf-surface);">
        <form action="{{ route('settings.categories.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="kf-form-label mb-1">Kategori Adı</label>
                    <input type="text" name="name" class="form-control kf-form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="kf-form-label mb-1">Kod</label>
                    <input type="text" name="code" class="form-control kf-form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="kf-form-label mb-1">Açıklama <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                    <input type="text" name="description" class="form-control kf-form-control" value="{{ old('description') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="kf-btn kf-btn-primary w-100">Ekle</button>
                </div>
            </div>
        </form>
    </div>

    <!-- SEARCH & FİLTRE KATEGORİ -->
    <div class="p-3 border-bottom bg-light">
        <form action="{{ route('settings.reference-data.index') }}" method="GET" class="row g-2 align-items-center m-0">
            <!-- Keep department query state if present -->
            @if(request()->filled('department_q')) <input type="hidden" name="department_q" value="{{ request('department_q') }}"> @endif
            @if(request()->filled('department_status')) <input type="hidden" name="department_status" value="{{ request('department_status') }}"> @endif
            @if(request()->filled('department_page')) <input type="hidden" name="department_page" value="{{ request('department_page') }}"> @endif

            <div class="col-md-5">
                <input type="text" name="category_q" class="form-control kf-form-control form-control-sm" placeholder="Kategori ara (ad, kod)..." value="{{ request('category_q') }}">
            </div>
            <div class="col-md-3">
                <select name="category_status" class="form-select kf-form-control form-select-sm">
                    <option value="">Tümü (Durum)</option>
                    <option value="active" {{ request('category_status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('category_status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="kf-btn kf-btn-secondary btn-sm w-100">Filtrele</button>
            </div>
            <div class="col-md-2">
                @if(request()->anyFilled(['category_q', 'category_status']))
                    <a href="{{ route('settings.reference-data.index', request()->except(['category_q', 'category_status', 'category_page'])) }}" class="kf-btn btn-sm btn-light border w-100 text-center text-decoration-none">Temizle</a>
                @endif
            </div>
        </form>
    </div>

    <!-- LİSTE KATEGORİ -->
    <div class="kf-table-shell border-0 rounded-0">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Kategori</th>
                        <th scope="col">Kod</th>
                        <th scope="col">Durum</th>
                        <th scope="col" class="text-center d-none d-md-table-cell">Kaizen Kullanımı</th>
                        <th scope="col" class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $category->name }}</div>
                            @if($category->description)
                                <div class="text-muted small fw-normal text-truncate" style="max-width: 200px;" title="{{ $category->description }}">{{ $category->description }}</div>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $category->code }}</span></td>
                        <td>
                            @if($category->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Pasif</span>
                            @endif
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="badge bg-light text-secondary border px-2 py-1">{{ $category->kaizens_count }}</span>
                        </td>
                        <td class="text-end">
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
                        <td colspan="5" class="text-center text-muted py-4">
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
        <div class="p-3 border-top bg-light">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</x-section-card>
@endcan


<!-- DEPARTMANLAR BÖLÜMÜ -->
@can('viewAny', App\Models\Department::class)
<x-section-card class="mb-5" :no-padding="true">
    <x-slot:title>Departmanlar</x-slot:title>
    <x-slot:description>Kullanıcıların ve Kaizen kayıtlarının bağlı olduğu organizasyon birimlerini yönetin.</x-slot:description>

    <!-- İstatistikler -->
    <div class="d-flex gap-4 p-4 bg-light border-bottom">
        <div>
            <div class="text-muted small fw-medium text-uppercase mb-1">Toplam</div>
            <div class="fs-4 fw-bold text-dark">{{ $departments->total() }}</div>
        </div>
        <div class="border-start ps-4">
            <div class="text-muted small fw-medium text-uppercase mb-1">Aktif</div>
            <div class="fs-4 fw-bold text-success">{{ App\Models\Department::where('is_active', true)->count() }}</div>
        </div>
        <div class="border-start ps-4">
            <div class="text-muted small fw-medium text-uppercase mb-1">Pasif</div>
            <div class="fs-4 fw-bold text-secondary">{{ App\Models\Department::where('is_active', false)->count() }}</div>
        </div>
    </div>
    
    <!-- CREATE DEPARTMAN -->
    <div class="p-4 border-bottom" style="background-color: var(--kf-surface);">
        <form action="{{ route('settings.departments.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="kf-form-label mb-1">Departman Adı</label>
                    <input type="text" name="name" class="form-control kf-form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="kf-form-label mb-1">Kod</label>
                    <input type="text" name="code" class="form-control kf-form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="kf-form-label mb-1">Açıklama <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                    <input type="text" name="description" class="form-control kf-form-control" value="{{ old('description') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="kf-btn kf-btn-primary w-100">Ekle</button>
                </div>
            </div>
        </form>
    </div>

    <!-- SEARCH & FİLTRE DEPARTMAN -->
    <div class="p-3 border-bottom bg-light">
        <form action="{{ route('settings.reference-data.index') }}" method="GET" class="row g-2 align-items-center m-0">
            <!-- Keep category query state if present -->
            @if(request()->filled('category_q')) <input type="hidden" name="category_q" value="{{ request('category_q') }}"> @endif
            @if(request()->filled('category_status')) <input type="hidden" name="category_status" value="{{ request('category_status') }}"> @endif
            @if(request()->filled('category_page')) <input type="hidden" name="category_page" value="{{ request('category_page') }}"> @endif

            <div class="col-md-5">
                <input type="text" name="department_q" class="form-control kf-form-control form-control-sm" placeholder="Departman ara (ad, kod)..." value="{{ request('department_q') }}">
            </div>
            <div class="col-md-3">
                <select name="department_status" class="form-select kf-form-control form-select-sm">
                    <option value="">Tümü (Durum)</option>
                    <option value="active" {{ request('department_status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('department_status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="kf-btn kf-btn-secondary btn-sm w-100">Filtrele</button>
            </div>
            <div class="col-md-2">
                @if(request()->anyFilled(['department_q', 'department_status']))
                    <a href="{{ route('settings.reference-data.index', request()->except(['department_q', 'department_status', 'department_page'])) }}" class="kf-btn btn-sm btn-light border w-100 text-center text-decoration-none">Temizle</a>
                @endif
            </div>
        </form>
    </div>

    <!-- LİSTE DEPARTMAN -->
    <div class="kf-table-shell border-0 rounded-0">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Departman</th>
                        <th scope="col">Kod</th>
                        <th scope="col">Durum</th>
                        <th scope="col" class="text-center d-none d-lg-table-cell">Kullanıcı (Aktif/Top.)</th>
                        <th scope="col" class="text-center d-none d-md-table-cell">Kaizen Kullanımı</th>
                        <th scope="col" class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $department->name }}</div>
                            @if($department->description)
                                <div class="text-muted small fw-normal text-truncate" style="max-width: 200px;" title="{{ $department->description }}">{{ $department->description }}</div>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $department->code }}</span></td>
                        <td>
                            @if($department->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Pasif</span>
                            @endif
                        </td>
                        <td class="text-center d-none d-lg-table-cell">
                            @if($department->active_users_count > 0)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    {{ $department->active_users_count }} / {{ $department->users_count }}
                                </span>
                            @else
                                <span class="badge bg-light text-secondary border px-2 py-1">{{ $department->active_users_count }} / {{ $department->users_count }}</span>
                            @endif
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="badge bg-light text-secondary border px-2 py-1">{{ $department->kaizens_count }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('settings.departments.edit', $department) }}" class="btn btn-sm btn-outline-secondary">Düzenle</a>
                                <form action="{{ route('settings.departments.status', $department) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($department->is_active)
                                        @if($department->active_users_count > 0)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Aktif kullanıcısı bulunan departman pasife alınamaz. ({{ $department->active_users_count }} aktif kullanıcı var)">Pasife Al</button>
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
                        <td colspan="6" class="text-center text-muted py-4">
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
        <div class="p-3 border-top bg-light">
            {{ $departments->links() }}
        </div>
        @endif
    </div>
</x-section-card>
@endcan

<!-- FAYDA TÜRLERİ BÖLÜMÜ -->
@can('viewAny', App\Models\BenefitType::class)
<x-section-card class="mb-5" :no-padding="true">
    <x-slot:title>Fayda Türleri</x-slot:title>
    <x-slot:description>Kaizen gerçekleşme raporlarında seçilebilecek fayda metriklerini yönetin.</x-slot:description>

    <!-- İstatistikler -->
    <div class="d-flex gap-4 p-4 bg-light border-bottom">
        <div>
            <div class="text-muted small fw-medium text-uppercase mb-1">Toplam</div>
            <div class="fs-4 fw-bold text-dark">{{ $benefitTypes->total() }}</div>
        </div>
        <div class="border-start ps-4">
            <div class="text-muted small fw-medium text-uppercase mb-1">Aktif</div>
            <div class="fs-4 fw-bold text-success">{{ App\Models\BenefitType::where('is_active', true)->count() }}</div>
        </div>
        <div class="border-start ps-4">
            <div class="text-muted small fw-medium text-uppercase mb-1">Pasif</div>
            <div class="fs-4 fw-bold text-secondary">{{ App\Models\BenefitType::where('is_active', false)->count() }}</div>
        </div>
    </div>
    
    <!-- CREATE FAYDA TÜRÜ -->
    @can('create', App\Models\BenefitType::class)
    <div class="p-4 border-bottom" style="background-color: var(--kf-surface);">
        <form action="{{ route('settings.benefit-types.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="kf-form-label mb-1">Fayda Türü Adı</label>
                    <input type="text" name="name" class="form-control kf-form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="kf-form-label mb-1">Birim <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                    <input type="text" name="unit_label" class="form-control kf-form-control @error('unit_label') is-invalid @enderror" value="{{ old('unit_label') }}" placeholder="Örn. saat, TL, vb.">
                    @error('unit_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="kf-btn kf-btn-primary w-100">Ekle</button>
                </div>
            </div>
        </form>
    </div>
    @endcan

    <!-- SEARCH & FİLTRE FAYDA TÜRÜ -->
    <div class="p-3 border-bottom bg-light">
        <form action="{{ route('settings.reference-data.index') }}" method="GET" class="row g-2 align-items-center m-0">
            @if(request()->filled('category_q')) <input type="hidden" name="category_q" value="{{ request('category_q') }}"> @endif
            @if(request()->filled('category_status')) <input type="hidden" name="category_status" value="{{ request('category_status') }}"> @endif
            @if(request()->filled('category_page')) <input type="hidden" name="category_page" value="{{ request('category_page') }}"> @endif
            
            @if(request()->filled('department_q')) <input type="hidden" name="department_q" value="{{ request('department_q') }}"> @endif
            @if(request()->filled('department_status')) <input type="hidden" name="department_status" value="{{ request('department_status') }}"> @endif
            @if(request()->filled('department_page')) <input type="hidden" name="department_page" value="{{ request('department_page') }}"> @endif

            <div class="col-md-5">
                <input type="text" name="benefit_type_q" class="form-control kf-form-control form-control-sm" placeholder="Fayda türü ara..." value="{{ request('benefit_type_q') }}">
            </div>
            <div class="col-md-3">
                <select name="benefit_type_status" class="form-select kf-form-control form-select-sm">
                    <option value="">Tümü (Durum)</option>
                    <option value="active" {{ request('benefit_type_status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('benefit_type_status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="kf-btn kf-btn-secondary btn-sm w-100">Filtrele</button>
            </div>
            <div class="col-md-2">
                @if(request()->anyFilled(['benefit_type_q', 'benefit_type_status']))
                    <a href="{{ route('settings.reference-data.index', request()->except(['benefit_type_q', 'benefit_type_status', 'benefit_type_page'])) }}" class="kf-btn btn-sm btn-light border w-100 text-center text-decoration-none">Temizle</a>
                @endif
            </div>
        </form>
    </div>

    <!-- LİSTE FAYDA TÜRÜ -->
    <div class="kf-table-shell border-0 rounded-0">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Fayda Türü</th>
                        <th scope="col">Birim</th>
                        <th scope="col">Durum</th>
                        <th scope="col" class="text-center d-none d-md-table-cell">Kaizen Kullanımı</th>
                        <th scope="col" class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($benefitTypes as $benefitType)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $benefitType->name }}</div>
                        </td>
                        <td>
                            @if($benefitType->unit_label)
                                <span class="badge bg-light text-dark border">{{ $benefitType->unit_label }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            @if($benefitType->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Pasif</span>
                            @endif
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="badge bg-light text-secondary border px-2 py-1">{{ $benefitType->kaizen_benefits_count }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @can('update', $benefitType)
                                    <a href="{{ route('settings.benefit-types.edit', $benefitType) }}" class="btn btn-sm btn-outline-secondary">Düzenle</a>
                                    <form action="{{ route('settings.benefit-types.status', $benefitType) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($benefitType->is_active)
                                            <input type="hidden" name="is_active" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bu fayda türünü pasife almak istediğinize emin misiniz?')">Pasife Al</button>
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
                        <td colspan="5" class="text-center text-muted py-4">
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
        <div class="p-3 border-top bg-light">
            {{ $benefitTypes->links() }}
        </div>
        @endif
    </div>
</x-section-card>
@endcan

@endsection
