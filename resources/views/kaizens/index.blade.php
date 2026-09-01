@extends('layouts.app')

@section('title', 'Kaizenler')

@section('content')
<x-page-header 
    title="Kaizenler" 
    subtitle="Erişiminiz olan Kaizen kayıtlarını görüntüleyin, arayın ve filtreleyin."
>
    <x-slot:actions>
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Yeni Kaizen Oluştur
        </a>
    </x-slot:actions>
</x-page-header>

<!-- Filter Surface -->
<div class="kf-card mb-4">
    <div class="kf-card-body pb-3">
        <form action="{{ route('kaizens.index') }}" method="GET">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-3">
                    <label for="q" class="kf-form-label">Ara</label>
                    <input type="text" name="q" id="q" class="form-control kf-form-control" value="{{ request('q') }}" placeholder="Kod, başlık veya açıklama">
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="status" class="kf-form-label">Durum</label>
                    <select name="status" id="status" class="form-select kf-form-control">
                        <option value="">Tüm durumlar</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ request('status') == $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="category_id" class="kf-form-label">Kategori</label>
                    <select name="category_id" id="category_id" class="form-select kf-form-control">
                        <option value="">Tüm kategoriler</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name ?? 'Bilinmeyen Kategori' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="department_id" class="kf-form-label">Departman</label>
                    <select name="department_id" id="department_id" class="form-select kf-form-control">
                        <option value="">Tüm departmanlar</option>
                        @foreach($departments as $dep)
                            <option value="{{ $dep->id }}" {{ request('department_id') == $dep->id ? 'selected' : '' }}>
                                {{ $dep->name ?? 'Bilinmeyen Departman' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="sort" class="kf-form-label">Sıralama</label>
                    <select name="sort" id="sort" class="form-select kf-form-control">
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Oluşturulma Tarihi</option>
                        <option value="updated_at" {{ request('sort') == 'updated_at' ? 'selected' : '' }}>Güncellenme Tarihi</option>
                        <option value="code" {{ request('sort') == 'code' ? 'selected' : '' }}>Kaizen Kodu</option>
                        <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Başlık</option>
                        <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>Durum</option>
                        <option value="target_date" {{ request('sort') == 'target_date' ? 'selected' : '' }}>Hedef Tarih</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="direction" class="kf-form-label">Yön</label>
                    <select name="direction" id="direction" class="form-select kf-form-control">
                        <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Yeniden eskiye</option>
                        <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>Eskiden yeniye</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-6 d-flex align-items-center gap-3">
                    <button type="submit" class="kf-btn kf-btn-primary px-4">Filtrele</button>
                    @if(request()->anyFilled(['q', 'status', 'category_id', 'department_id', 'sort', 'direction']))
                        <a href="{{ route('kaizens.index') }}" class="kf-btn kf-btn-secondary">Temizle</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Active Filter Summary -->
@if(request()->anyFilled(['q', 'status', 'category_id', 'department_id']))
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <span class="small fw-medium text-muted">Filtrelenmiş sonuçlar gösteriliyor.</span>
        @if($kaizens->total() > 0)
            <span class="small fw-bold text-dark">{{ $kaizens->total() }} Kaizen</span>
        @endif
    </div>
@elseif($kaizens->total() > 0)
    <div class="mb-3 text-end">
        <span class="small fw-bold text-dark">{{ $kaizens->total() }} Kaizen</span>
    </div>
@endif

<!-- Data Surface -->
@if($kaizens->isEmpty())
    @if(request()->anyFilled(['q', 'status', 'category_id', 'department_id']))
        <x-empty-state 
            title="Filtrelere uygun Kaizen bulunamadı" 
            description="Filtreleri değiştirerek tekrar deneyin."
        >
            <x-slot:action>
                <a href="{{ route('kaizens.index') }}" class="kf-btn kf-btn-secondary">Filtreleri Temizle</a>
            </x-slot:action>
        </x-empty-state>
    @else
        <x-empty-state 
            title="Henüz Kaizen Yok" 
            description="Yeni bir iyileştirme fikri oluşturarak başlayabilirsiniz."
        >
            <x-slot:action>
                <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">Yeni Kaizen Oluştur</a>
            </x-slot:action>
        </x-empty-state>
    @endif
@else
    <div class="kf-table-shell">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Kaizen</th>
                        <th scope="col">Durum</th>
                        <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                        <th scope="col" class="d-none d-md-table-cell">Departman</th>
                        <th scope="col" class="d-none d-lg-table-cell">Oluşturan</th>
                        <th scope="col" class="d-none d-sm-table-cell">Tarih</th>
                        <th scope="col" class="text-end">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kaizens as $kaizen)
                        <tr>
                            <td>
                                <a href="{{ route('kaizens.show', $kaizen) }}" class="fw-semibold text-dark text-decoration-none d-block">
                                    {{ $kaizen->title }}
                                </a>
                                <span class="font-monospace text-muted small">{{ $kaizen->code }}</span>
                            </td>
                            <td>
                                <x-status-badge :status="$kaizen->status" />
                            </td>
                            <td class="d-none d-md-table-cell text-truncate" style="max-width: 150px;">{{ $kaizen->category->name ?? '-' }}</td>
                            <td class="d-none d-md-table-cell text-truncate" style="max-width: 150px;">{{ $kaizen->department->name ?? '-' }}</td>
                            <td class="d-none d-lg-table-cell">{{ $kaizen->creator->name ?? '-' }}</td>
                            <td class="d-none d-sm-table-cell">{{ $kaizen->created_at->format('d.m.Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('kaizens.show', $kaizen) }}" class="kf-btn kf-btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;" aria-label="{{ $kaizen->code }} detaylarını görüntüle">
                                    Görüntüle
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Footer -->
        @if($kaizens->hasPages())
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-3 border-top bg-light">
                <div class="small text-muted mb-2 mb-md-0">
                    {{ $kaizens->firstItem() }}–{{ $kaizens->lastItem() }} / {{ $kaizens->total() }} kayıt
                </div>
                <div>
                    {{ $kaizens->links() }}
                </div>
            </div>
        @else
            <div class="p-3 border-top bg-light text-center">
                <span class="small text-muted">
                    Toplam {{ $kaizens->total() }} kayıt gösteriliyor.
                </span>
            </div>
        @endif
    </div>
@endif

@endsection
