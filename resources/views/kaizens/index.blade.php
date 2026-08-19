@extends('layouts.app')

@section('title', 'Kaizenler')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Kaizenler</h1>
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">Yeni Kaizen</a>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('kaizens.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="q" class="form-label">Arama</label>
                    <input type="text" name="q" id="q" class="form-control" value="{{ request('q') }}" placeholder="Kod veya başlık...">
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Durum</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Tümü</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ request('status') == $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Tümü</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="department_id" class="form-label">Departman</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="">Tümü</option>
                        @foreach($departments as $dep)
                            <option value="{{ $dep->id }}" {{ request('department_id') == $dep->id ? 'selected' : '' }}>
                                {{ $dep->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="sort" class="form-label">Sıralama</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Oluşturulma</option>
                        <option value="code" {{ request('sort') == 'code' ? 'selected' : '' }}>Kod</option>
                        <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Başlık</option>
                        <option value="target_date" {{ request('sort') == 'target_date' ? 'selected' : '' }}>Hedef Tarih</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <select name="direction" class="form-select" aria-label="Sıralama yönü">
                        <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Azalan</option>
                        <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>Artan</option>
                    </select>
                </div>

                <div class="col-12 mt-3 d-flex gap-2">
                    <button type="submit" class="kf-btn kf-btn-primary">Filtrele</button>
                    <a href="{{ route('kaizens.index') }}" class="kf-btn btn-light border">Temizle</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if($kaizens->isEmpty())
                <div class="text-center py-5 text-muted">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="mb-3 opacity-50"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    <p>Gösterilecek Kaizen bulunamadı.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kod</th>
                                <th>Başlık</th>
                                <th>Durum</th>
                                <th>Kategori</th>
                                <th>Departman</th>
                                <th>Oluşturan</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kaizens as $kaizen)
                                <tr>
                                    <td>
                                        <a href="{{ route('kaizens.show', $kaizen) }}" class="fw-semibold text-decoration-none">
                                            {{ $kaizen->code }}
                                        </a>
                                    </td>
                                    <td>{{ $kaizen->title }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $kaizen->status->label() }}</span>
                                    </td>
                                    <td>{{ $kaizen->category->name }}</td>
                                    <td>{{ $kaizen->department->name }}</td>
                                    <td>{{ $kaizen->creator->name }}</td>
                                    <td>{{ $kaizen->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if($kaizens->hasPages())
            <div class="card-footer bg-white border-top-0 pt-3">
                {{ $kaizens->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
