@extends('layouts.app')

@section('title', 'Kaizenler')

@section('content')
<div class="kf-list-page">
    <!-- Page Header -->
    <div class="kf-list-header">
        <div>
            <span class="kf-list-context">KAIZEN YÖNETİMİ</span>
            <h1 class="kf-list-title">Kaizenler</h1>
            <p class="kf-list-desc">Erişiminiz olan Kaizen kayıtlarını görüntüleyin, arayın ve filtreleyin.</p>
        </div>
        <div>
            <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">Yeni Kaizen Oluştur</a>
        </div>
    </div>

    <!-- Filter Surface -->
    <div class="kf-list-filter-panel">
        <form action="{{ route('kaizens.index') }}" method="GET">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-3">
                    <label for="q" class="form-label">Ara</label>
                    <input type="text" name="q" id="q" class="kf-form-control" value="{{ request('q') }}" placeholder="Kod, başlık veya açıklama ara">
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="status" class="form-label">Durum</label>
                    <select name="status" id="status" class="kf-form-control">
                        <option value="">Tüm durumlar</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ request('status') == $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select name="category_id" id="category_id" class="kf-form-control">
                        <option value="">Tüm kategoriler</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name ?? 'Bilinmeyen Kategori' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="department_id" class="form-label">Departman</label>
                    <select name="department_id" id="department_id" class="kf-form-control">
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
                    <label for="sort" class="form-label">Sıralama</label>
                    <select name="sort" id="sort" class="kf-form-control">
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Oluşturulma Tarihi</option>
                        <option value="updated_at" {{ request('sort') == 'updated_at' ? 'selected' : '' }}>Güncellenme Tarihi</option>
                        <option value="code" {{ request('sort') == 'code' ? 'selected' : '' }}>Kaizen Kodu</option>
                        <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Başlık</option>
                        <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>Durum</option>
                        <option value="target_date" {{ request('sort') == 'target_date' ? 'selected' : '' }}>Hedef Tarih</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="direction" class="form-label">Yön</label>
                    <select name="direction" id="direction" class="kf-form-control">
                        <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Yeniden eskiye</option>
                        <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>Eskiden yeniye</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-6 d-flex align-items-center gap-3">
                    <button type="submit" class="kf-btn kf-btn-primary">Filtrele</button>
                    @if(request()->anyFilled(['q', 'status', 'category_id', 'department_id', 'sort', 'direction']))
                        <a href="{{ route('kaizens.index') }}" class="text-decoration-none text-muted" style="font-size: 0.9rem; font-weight: 500;">
                            Filtreleri Temizle
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filter Summary -->
    @if(request()->anyFilled(['q', 'status', 'category_id', 'department_id']))
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <span class="kf-list-summary fw-medium">Filtrelenmiş sonuçlar gösteriliyor.</span>
            @if($kaizens->total() > 0)
                <span class="kf-list-summary">{{ $kaizens->total() }} Kaizen</span>
            @endif
        </div>
    @elseif($kaizens->total() > 0)
        <div class="mb-3 text-end">
            <span class="kf-list-summary">{{ $kaizens->total() }} Kaizen</span>
        </div>
    @endif

    <!-- Data Surface -->
    <div class="kf-list-surface">
        @if($kaizens->isEmpty())
            <div class="kf-list-empty">
                @if(request()->anyFilled(['q', 'status', 'category_id', 'department_id']))
                    <h3 class="kf-list-empty-title">Filtrelere uygun Kaizen bulunamadı</h3>
                    <p class="kf-list-empty-desc">Filtreleri değiştirerek tekrar deneyin.</p>
                    <a href="{{ route('kaizens.index') }}" class="kf-btn kf-btn-secondary">Filtreleri Temizle</a>
                @else
                    <h3 class="kf-list-empty-title">Henüz görüntülenecek Kaizen yok</h3>
                    <p class="kf-list-empty-desc">Yeni bir iyileştirme fikri oluşturarak başlayabilirsiniz.</p>
                    <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">Yeni Kaizen Oluştur</a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table kf-list-table">
                    <thead>
                        <tr>
                            <th scope="col">Kaizen</th>
                            <th scope="col">Durum</th>
                            <th scope="col" class="kf-hide-mobile">Kategori</th>
                            <th scope="col" class="kf-hide-mobile">Departman</th>
                            <th scope="col" class="kf-hide-mobile">Oluşturan</th>
                            <th scope="col" class="kf-hide-mobile">Tarih</th>
                            <th scope="col" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kaizens as $kaizen)
                            <tr>
                                <td>
                                    <a href="{{ route('kaizens.show', $kaizen) }}" class="kf-list-title-link">
                                        {{ $kaizen->title }}
                                    </a>
                                    <span class="kf-list-code">{{ $kaizen->code }}</span>
                                </td>
                                <td>
                                    <span class="kf-list-status">
                                        {{ $kaizen->status->label() }}
                                    </span>
                                </td>
                                <td class="kf-hide-mobile">{{ $kaizen->category->name ?? '-' }}</td>
                                <td class="kf-hide-mobile">{{ $kaizen->department->name ?? '-' }}</td>
                                <td class="kf-hide-mobile">{{ $kaizen->creator->name ?? '-' }}</td>
                                <td class="kf-hide-mobile">{{ $kaizen->created_at->format('d.m.Y') }}</td>
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
                <div class="kf-list-pagination">
                    <div class="kf-list-summary">
                        {{ $kaizens->firstItem() }}–{{ $kaizens->lastItem() }} / {{ $kaizens->total() }} kayıt
                    </div>
                    <div>
                        {{ $kaizens->links() }}
                    </div>
                </div>
            @else
                <div class="kf-list-pagination" style="justify-content: center;">
                    <span class="kf-list-summary">
                        Toplam {{ $kaizens->total() }} kayıt gösteriliyor.
                    </span>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
