@extends('layouts.app')

@section('title', 'Uygulama İşlerim')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Uygulama İşlerim</h1>
    </div>

    @if($kaizens->isEmpty())
        <div class="alert alert-info" role="alert">
            Şu anda üzerinize atanmış aktif bir uygulama görevi bulunmuyor.
        </div>
    @else
        <div class="row d-block d-md-none">
            @foreach($kaizens as $kaizen)
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-truncate">
                                <a href="{{ route('kaizens.show', $kaizen->id) }}" class="text-decoration-none">
                                    {{ $kaizen->code }} - {{ $kaizen->title }}
                                </a>
                            </h5>
                            <p class="card-text mb-1">
                                <span class="badge bg-secondary">{{ $kaizen->category->name ?? 'Kategori Yok' }}</span>
                                <span class="badge bg-info text-dark">{{ $kaizen->department->name ?? 'Departman Yok' }}</span>
                            </p>
                            <p class="card-text mb-1"><small class="text-muted">Durum: {{ $kaizen->status->label() }}</small></p>
                            <p class="card-text">
                                <small>
                                    Hedef Tarih: 
                                    @if(is_null($kaizen->target_date))
                                        <span class="text-muted">Hedef tarih belirtilmedi</span>
                                    @else
                                        <time datetime="{{ $kaizen->target_date->format('Y-m-d') }}">
                                            @if($kaizen->is_overdue)
                                                <span class="badge bg-danger">Gecikmiş</span>
                                                <span class="text-danger">{{ $kaizen->target_date->format('d.m.Y') }}</span>
                                            @elseif($kaizen->target_date->isToday())
                                                <span class="text-warning fw-bold">Bugün</span>
                                            @else
                                                {{ $kaizen->target_date->format('d.m.Y') }}
                                            @endif
                                        </time>
                                    @endif
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive d-none d-md-block shadow-sm rounded bg-white">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Kaizen Kodu</th>
                        <th scope="col">Başlık</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Departman</th>
                        <th scope="col">Durum</th>
                        <th scope="col">Hedef Tarih</th>
                        <th scope="col">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kaizens as $kaizen)
                        <tr>
                            <td><strong>{{ $kaizen->code }}</strong></td>
                            <td class="text-truncate" style="max-width: 250px;">{{ $kaizen->title }}</td>
                            <td>{{ $kaizen->category->name ?? 'Kategori Yok' }}</td>
                            <td>{{ $kaizen->department->name ?? 'Departman Yok' }}</td>
                            <td>{{ $kaizen->status->label() }}</td>
                            <td>
                                @if(is_null($kaizen->target_date))
                                    <span class="text-muted">Hedef tarih belirtilmedi</span>
                                @else
                                    <time datetime="{{ $kaizen->target_date->format('Y-m-d') }}">
                                        @if($kaizen->is_overdue)
                                            <span class="badge bg-danger">Gecikmiş</span>
                                            <span class="text-danger ms-1">{{ $kaizen->target_date->format('d.m.Y') }}</span>
                                        @elseif($kaizen->target_date->isToday())
                                            <span class="text-warning fw-bold">Bugün</span>
                                        @else
                                            {{ $kaizen->target_date->format('d.m.Y') }}
                                        @endif
                                    </time>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('kaizens.show', $kaizen->id) }}" class="btn btn-sm btn-outline-primary">
                                    Detay
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $kaizens->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
