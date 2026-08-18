@extends('layouts.app')

@section('title', 'Kaizen Detayı: ' . $kaizen->code)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 kf-page-header">
    <div>
        <span class="kf-page-eyebrow">Kaizen Detayı</span>
        <h1 class="kf-page-title mb-2">{{ $kaizen->title }}</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-muted" style="font-family: monospace; font-size: 0.95rem;">{{ $kaizen->code }}</span>
            <span class="text-muted">•</span>
            <span class="kf-badge kf-badge-neutral">{{ $kaizen->status->label() }}</span>
            @if($kaizen->priority)
                <span class="kf-badge kf-badge-priority">{{ $kaizen->priority->label() }}</span>
            @endif
        </div>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-secondary">
            Yeni Kaizen Oluştur
        </a>
    </div>
</div>

<div class="kf-detail-grid">
    <div class="kf-panel">
        <div class="kf-panel-body p-4 p-md-5">
            <div class="kf-detail-section">
                <h3 class="kf-detail-section-title">Mevcut Durum</h3>
                <p class="kf-detail-text">{{ $kaizen->current_situation }}</p>
            </div>

            <div class="kf-detail-section">
                <h3 class="kf-detail-section-title">Önerilen Durum</h3>
                <p class="kf-detail-text">{{ $kaizen->proposed_situation }}</p>
            </div>

            <div class="kf-detail-section">
                <h3 class="kf-detail-section-title">Beklenen Fayda</h3>
                <p class="kf-detail-text">{{ $kaizen->expected_benefit }}</p>
            </div>

            @if($kaizen->status->isTerminal() || $kaizen->actual_result)
            <div class="kf-detail-section pt-3 mt-4 border-top">
                <h3 class="kf-detail-section-title">Gerçekleşen Sonuç</h3>
                @if($kaizen->actual_result)
                    <p class="kf-detail-text">{{ $kaizen->actual_result }}</p>
                @else
                    <p class="kf-detail-text text-muted fst-italic">Henüz sonuç girilmedi.</p>
                @endif
            </div>
            @endif
        </div>
    </div>

    <div>
        <div class="kf-panel sticky-top" style="top: 2rem;">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Kaizen Bilgileri</h2>
            </div>
            <div class="kf-panel-body p-4">
                <ul class="kf-meta-list">
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Kategori</span>
                        <span class="kf-meta-value">{{ $kaizen->category->name }}</span>
                    </li>
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Departman</span>
                        <span class="kf-meta-value">{{ $kaizen->department->name }}</span>
                    </li>
                    <li class="kf-meta-item border-top pt-3 mt-3">
                        <span class="kf-meta-label">Oluşturan</span>
                        <span class="kf-meta-value">{{ $kaizen->creator->name }}</span>
                    </li>
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Atanan Kişi</span>
                        @if($kaizen->assignedUser)
                            <span class="kf-meta-value">{{ $kaizen->assignedUser->name }}</span>
                        @else
                            <span class="kf-meta-value text-muted fst-italic">Atanmadı</span>
                        @endif
                    </li>
                    <li class="kf-meta-item border-top pt-3 mt-3">
                        <span class="kf-meta-label">Hedef Tarih</span>
                        @if($kaizen->target_date)
                            <span class="kf-meta-value">{{ $kaizen->target_date->format('d.m.Y') }}</span>
                        @else
                            <span class="kf-meta-value text-muted fst-italic">Belirtilmedi</span>
                        @endif
                    </li>
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Gönderim Tarihi</span>
                        @if($kaizen->submitted_at)
                            <span class="kf-meta-value">{{ $kaizen->submitted_at->format('d.m.Y H:i') }}</span>
                        @else
                            <span class="kf-meta-value text-muted fst-italic">Henüz gönderilmedi</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
