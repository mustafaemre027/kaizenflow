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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="kf-workflow-title mb-0">
            Onay Süreci
            @if($workflowTimeline->isAvailable && $workflowTimeline->workflowName)
                <span class="fs-6 fw-normal text-muted ms-2 d-none d-md-inline">({{ $workflowTimeline->workflowName }})</span>
            @endif
        </h3>
    </div>

    <div class="kf-workflow-track {{ count($workflowTimeline->stages) > 5 ? 'overflow-auto pb-2' : '' }}">
        @if($workflowTimeline->isDraft)
            <div class="p-3 text-muted fst-italic">
                Süreç henüz başlatılmadı. Kaizen gönderildiğinde onay akışı oluşturulacaktır.
            </div>
        @elseif(!$workflowTimeline->isAvailable)
            <div class="p-3 text-muted fst-italic">
                Bu kayıt için dinamik onay akışı bulunmuyor.
            </div>
        @else
            <div class="d-flex min-vw-50">
                @foreach($workflowTimeline->stages as $stage)
                    @php
                        $stateClass = '';
                        $stateLabel = '';
                        if ($stage->presentation_state === 'completed') {
                            $stateClass = 'completed';
                        } elseif ($stage->presentation_state === 'current') {
                            $stateClass = 'current';
                            $stateLabel = ' (Mevcut Aşama)';
                        } elseif ($stage->presentation_state === 'rejected') {
                            $stateClass = 'current rejected';
                            $stateLabel = ' (Reddedildi)';
                        } elseif ($stage->presentation_state === 'revision') {
                            $stateClass = 'current revision';
                            $stateLabel = ' (Revizyon Bekleniyor)';
                        }
                    @endphp

                    <div class="kf-workflow-item {{ $stateClass }} flex-shrink-0" style="min-width: 140px; flex: 1;">
                        <div class="kf-workflow-marker" title="{{ $stage->name }}{{ $stateLabel }}">
                            @if($stage->presentation_state === 'completed')
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            @endif
                        </div>
                        <span class="kf-workflow-label text-center px-2">{{ $stage->name }}</span>
                        @if($stateLabel)
                            <span class="d-block text-center mt-1" style="font-size: 0.75rem; color: var(--kf-primary-dark);">{{ trim($stateLabel, ' ()') }}</span>
                        @endif
                    </div>
                @endforeach
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
                                <button type="button"
                                   class="kf-gallery-item border-0 p-0 text-start w-100"
                                   data-lightbox-trigger
                                   data-context="current_situation"
                                   data-index="{{ $index }}"
                                   data-view-url="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                   data-download-url="{{ route('kaizens.attachments.download', [$kaizen, $attachment]) }}"
                                   data-alt="Mevcut durum fotoğrafı {{ $index + 1 }}"
                                   aria-label="Mevcut durum fotoğrafı {{ $index + 1 }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                         alt="Mevcut durum fotoğrafı {{ $index + 1 }}"
                                         loading="lazy">
                                </button>
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
                                <button type="button"
                                   class="kf-gallery-item border-0 p-0 text-start w-100"
                                   data-lightbox-trigger
                                   data-context="proposed_situation"
                                   data-index="{{ $index }}"
                                   data-view-url="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                   data-download-url="{{ route('kaizens.attachments.download', [$kaizen, $attachment]) }}"
                                   data-alt="Önerilen durum fotoğrafı {{ $index + 1 }}"
                                   aria-label="Önerilen durum fotoğrafı {{ $index + 1 }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                         alt="Önerilen durum fotoğrafı {{ $index + 1 }}"
                                         loading="lazy">
                                </button>
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

        @if($workflowTimeline->history->isNotEmpty())
        <div class="kf-panel mt-4">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">İşlem Geçmişi</h2>
            </div>
            <div class="kf-panel-body p-4">
                <div class="kf-timeline-vertical position-relative">
                    @foreach($workflowTimeline->history as $historyItem)
                        <div class="mb-4 position-relative ps-4 border-start border-2" style="border-color: var(--kf-gray-200) !important;">
                            <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-white border border-2" style="width: 14px; height: 14px; border-color: var(--kf-primary) !important;"></div>

                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold" style="color: var(--kf-primary-dark);">{{ $historyItem->actionLabel }}</span>
                                <span class="text-muted" style="font-size: 0.8rem;">{{ $historyItem->timestamp->format('d.m.Y H:i') }}</span>
                            </div>

                            <p class="text-secondary mb-1" style="font-size: 0.9rem;">
                                {{ $historyItem->stageContext }}
                            </p>

                            @if($historyItem->comment)
                                <div class="bg-light p-2 rounded mt-2 text-dark" style="font-size: 0.85rem;">
                                    "{{ $historyItem->comment }}"
                                </div>
                            @endif

                            <div class="mt-2 text-muted" style="font-size: 0.8rem;">
                                İşlem Yapan: <span class="fw-medium">{{ $historyItem->actorName }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
