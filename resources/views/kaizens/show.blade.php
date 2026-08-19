@extends('layouts.app')

@section('title', 'Kaizen Detayı: ' . $kaizen->code)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 kf-page-header">
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
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        @can('update', $kaizen)
            <a href="{{ route('kaizens.edit', $kaizen) }}" class="kf-btn kf-btn-primary">
                Düzenle
            </a>
        @endcan
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-secondary">
            Yeni Kaizen
        </a>
    </div>
</div>

<!-- Workflow Visual -->
<div class="kf-workflow-panel">
    <h3 class="kf-workflow-title">Süreç Durumu</h3>
    <div class="kf-workflow-track">
        @foreach($workflowStatuses as $status)
            <div class="kf-workflow-item {{ $kaizen->status === $status ? 'current' : '' }}">
                <div class="kf-workflow-marker"></div>
                <span class="kf-workflow-label">{{ $status->label() }}</span>
            </div>
        @endforeach

        @if($specialWorkflowStatus)
            <div class="kf-workflow-item current {{ $specialWorkflowStatus === \App\Enums\KaizenStatus::REJECTED ? 'rejected' : 'revision' }}">
                <div class="kf-workflow-marker"></div>
                <span class="kf-workflow-label">{{ $specialWorkflowStatus->label() }}</span>
            </div>
        @endif
    </div>
</div>

<div class="kf-detail-grid">
    <!-- Main Content -->
    <div class="kf-panel align-self-start">
        <div class="kf-panel-body p-4 p-md-5">
            <div class="kf-content-block">
                <div class="kf-content-block-header">
                    <span class="kf-content-num">01</span>
                    <h3 class="kf-content-title">Mevcut Durum</h3>
                </div>
                <p class="kf-detail-text">{{ $kaizen->current_situation }}</p>

                @if($currentSituationAttachments->isNotEmpty())
                    <div class="kf-gallery-container mt-3">
                        <div class="d-flex align-items-center mb-2">
                            <h4 class="kf-gallery-title mb-0 fs-6 fw-medium text-dark">Fotoğraflar</h4>
                            <span class="ms-2 badge bg-light text-secondary border fw-normal">{{ $currentSituationAttachments->count() }} fotoğraf</span>
                        </div>
                        <div class="kf-gallery-grid">
                            @foreach($currentSituationAttachments as $index => $attachment)
                                <a href="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="kf-gallery-item"
                                   aria-label="Mevcut durum fotoğrafı {{ $index + 1 }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                         alt="Mevcut durum fotoğrafı {{ $index + 1 }}"
                                         loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="kf-content-block">
                <div class="kf-content-block-header">
                    <span class="kf-content-num">02</span>
                    <h3 class="kf-content-title">Önerilen Durum</h3>
                </div>
                <p class="kf-detail-text">{{ $kaizen->proposed_situation }}</p>

                @if($proposedSituationAttachments->isNotEmpty())
                    <div class="kf-gallery-container mt-3">
                        <div class="d-flex align-items-center mb-2">
                            <h4 class="kf-gallery-title mb-0 fs-6 fw-medium text-dark">Fotoğraflar</h4>
                            <span class="ms-2 badge bg-light text-secondary border fw-normal">{{ $proposedSituationAttachments->count() }} fotoğraf</span>
                        </div>
                        <div class="kf-gallery-grid">
                            @foreach($proposedSituationAttachments as $index => $attachment)
                                <a href="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="kf-gallery-item"
                                   aria-label="Önerilen durum fotoğrafı {{ $index + 1 }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                         alt="Önerilen durum fotoğrafı {{ $index + 1 }}"
                                         loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="kf-content-block">
                <div class="kf-content-block-header">
                    <span class="kf-content-num">03</span>
                    <h3 class="kf-content-title">Beklenen Fayda</h3>
                </div>
                <p class="kf-detail-text">{{ $kaizen->expected_benefit }}</p>
            </div>

            <div class="kf-content-block">
                <div class="kf-content-block-header">
                    <span class="kf-content-num">04</span>
                    <h3 class="kf-content-title">Gerçekleşen Sonuç</h3>
                </div>
                @if($kaizen->actual_result)
                    <p class="kf-detail-text">{{ $kaizen->actual_result }}</p>
                @else
                    <p class="kf-detail-text text-muted fst-italic">Henüz sonuç girilmedi.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Metadata Panel -->
    <div class="align-self-start">
        <div class="kf-panel">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Kaizen Bilgileri</h2>
            </div>
            <div class="kf-panel-body p-4">
                <div class="kf-meta-group">
                    <h3 class="kf-meta-group-title">Sınıflandırma</h3>
                    <ul class="kf-meta-list">
                        <li class="kf-meta-item">
                            <span class="kf-meta-label">Kategori</span>
                            <span class="kf-meta-value">{{ $kaizen->category->name }}</span>
                        </li>
                        <li class="kf-meta-item">
                            <span class="kf-meta-label">Departman</span>
                            <span class="kf-meta-value">{{ $kaizen->department->name }}</span>
                        </li>
                    </ul>
                </div>

                <div class="kf-meta-group">
                    <h3 class="kf-meta-group-title">Sorumluluk</h3>
                    <ul class="kf-meta-list">
                        <li class="kf-meta-item">
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
                    </ul>
                </div>

                <div class="kf-meta-group">
                    <h3 class="kf-meta-group-title">Zaman</h3>
                    <ul class="kf-meta-list">
                        <li class="kf-meta-item">
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
</div>
@endsection
