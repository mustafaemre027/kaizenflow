@extends('layouts.app')

@section('title', 'Uygulama İşlerim')

@section('content')
<x-page-header 
    title="Uygulama İşlerim" 
    subtitle="Üzerinize atanmış olan ve uygulamasını gerçekleştireceğiniz Kaizenler."
/>

@if($kaizens->isEmpty())
    <x-empty-state 
        title="Uygulama işi bulunmuyor" 
        description="Şu anda üzerinize atanmış aktif bir uygulama görevi bulunmuyor."
    >
        <x-slot:icon>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="M12 6v6l4 2"></path>
            </svg>
        </x-slot:icon>
    </x-empty-state>
@else
    <div class="kf-table-shell">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Kaizen Kodu</th>
                        <th scope="col">Başlık</th>
                        <th scope="col" class="d-none d-md-table-cell">Kategori / Departman</th>
                        <th scope="col">Durum</th>
                        <th scope="col" class="d-none d-sm-table-cell">Hedef Tarih</th>
                        <th scope="col" class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kaizens as $kaizen)
                        <tr>
                            <td class="font-monospace text-muted small fw-bold">{{ $kaizen->code }}</td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="{{ $kaizen->title }}">{{ $kaizen->title }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="fw-medium text-dark small">{{ $kaizen->category->name ?? 'Kategori Yok' }}</div>
                                <div class="text-secondary small">{{ $kaizen->department->name ?? 'Departman Yok' }}</div>
                            </td>
                            <td>
                                <span class="badge" style="background-color: var(--kf-primary-soft); color: var(--kf-primary); font-weight: 600;">
                                    {{ $kaizen->status->label() }}
                                </span>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                @if(is_null($kaizen->target_date))
                                    <span class="text-muted small fst-italic">Hedef tarih belirtilmedi</span>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <time datetime="{{ $kaizen->target_date->toDateString() }}">
                                            @if($kaizen->is_overdue)
                                                <span class="badge bg-danger rounded-pill px-2 py-1">Gecikmiş</span>
                                                <span class="text-danger small fw-medium">{{ $kaizen->target_date->format('d.m.Y') }}</span>
                                            @elseif($kaizen->target_date->isToday())
                                                <span class="badge bg-warning rounded-pill px-2 py-1 text-dark">Bugün</span>
                                                <span class="text-dark small fw-bold">{{ $kaizen->target_date->format('d.m.Y') }}</span>
                                            @else
                                                <span class="text-dark small">{{ $kaizen->target_date->format('d.m.Y') }}</span>
                                            @endif
                                        </time>
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('kaizens.show', $kaizen->id) }}" class="kf-btn kf-btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                                    Detay
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($kaizens->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $kaizens->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
