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
        @can('submit', $kaizen)
            @php
                $isCompletable = trim($kaizen->expected_benefit ?? '') !== '';
                $btnText = $kaizen->status === \App\Enums\KaizenStatus::REVISION_REQUESTED ? 'Yeniden Gönder' : 'Onaya Gönder';
            @endphp
            
            @if($isCompletable)
                <form action="{{ route('kaizens.submit', $kaizen) }}" method="POST" class="d-inline m-0 p-0">
                    @csrf
                    <button type="submit" class="kf-btn kf-btn-primary" onclick="return confirm('Bu Kaizen\'i onaya göndermek istediğinize emin misiniz?');">
                        {{ $btnText }}
                    </button>
                </form>
                @can('update', $kaizen)
                    <a href="{{ route('kaizens.edit', $kaizen) }}" class="kf-btn kf-btn-secondary">
                        Düzenle
                    </a>
                @endcan
            @else
                <div class="d-inline-flex align-items-center gap-2 bg-light px-3 py-1 rounded border">
                    <span class="text-muted small fw-medium">Onay için Beklenen Fayda eksik.</span>
                    <a href="{{ route('kaizens.edit', $kaizen) }}" class="kf-btn kf-btn-primary btn-sm">
                        Eksikleri Tamamla
                    </a>
                </div>
            @endif
        @else
            @can('update', $kaizen)
                <a href="{{ route('kaizens.edit', $kaizen) }}" class="kf-btn kf-btn-primary">
                    Düzenle
                </a>
            @endcan
        @endcan
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-secondary">
            Yeni Kaizen
        </a>
    </div>
</div>

<!-- Workflow Visual -->
<div class="kf-workflow-panel mb-4">
    <div class="mb-4 text-center">
        <h3 class="text-uppercase fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.08em; color: var(--kf-primary);">Onay Süreci</h3>
        @if($workflowTimeline->isAvailable && $workflowTimeline->workflowName)
            <div class="fs-6 fw-medium text-dark">{{ $workflowTimeline->workflowName }}</div>
        @endif
    </div>

    <div class="kf-workflow-track {{ count($workflowTimeline->stages) > 5 ? 'overflow-auto pb-3' : '' }}">
        @if($workflowTimeline->isDraft)
            <div class="p-4 text-center text-muted fst-italic bg-light rounded border">
                Süreç henüz başlatılmadı. Kaizen gönderildiğinde onay akışı oluşturulacaktır.
            </div>
        @elseif(!$workflowTimeline->isAvailable)
            <div class="p-4 text-center text-muted fst-italic bg-light rounded border">
                Bu kayıt için dinamik onay akışı bulunmuyor.
            </div>
        @else
            <div class="position-relative d-flex justify-content-between align-items-start px-md-4 py-2" style="min-width: {{ count($workflowTimeline->stages) > 5 ? count($workflowTimeline->stages) * 160 : 100 }}%;">
                <!-- Connector Line -->
                <div class="position-absolute" style="top: 24px; left: 10%; right: 10%; height: 2px; background-color: var(--kf-border-light); z-index: 1;"></div>

                @foreach($workflowTimeline->stages as $stage)
                    @php
                        $borderColor = 'var(--kf-border-strong)';
                        $fillColor = 'transparent';
                        $iconColor = 'transparent';
                        $stateLabel = '';
                        $stateTextColor = 'var(--kf-primary)';

                        if ($stage->presentation_state === 'completed') {
                            $borderColor = 'var(--kf-primary)';
                            $iconColor = 'var(--kf-primary)';
                        } elseif ($stage->presentation_state === 'current') {
                            $borderColor = 'var(--kf-primary)';
                            $fillColor = 'var(--kf-primary)';
                            $stateLabel = 'Mevcut Aşama';
                        } elseif ($stage->presentation_state === 'rejected') {
                            $borderColor = 'var(--kf-danger)';
                            $fillColor = 'var(--kf-danger)';
                            $stateLabel = 'Reddedildi';
                            $stateTextColor = 'var(--kf-danger)';
                        } elseif ($stage->presentation_state === 'revision') {
                            $borderColor = 'var(--kf-warning)';
                            $fillColor = 'var(--kf-warning)';
                            $stateLabel = 'Revizyon Bekleniyor';
                            $stateTextColor = 'var(--kf-warning)';
                        }
                    @endphp

                    <div class="kf-workflow-item flex-fill d-flex flex-column align-items-center position-relative" style="z-index: 2; max-width: 200px;">
                        <div class="kf-workflow-marker d-flex align-items-center justify-content-center bg-white border border-2 rounded-circle mb-2" style="width: 32px; height: 32px; border-color: {{ $borderColor }} !important;" title="{{ $stage->name }}">
                            @if($stage->presentation_state === 'completed')
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $iconColor }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            @elseif(in_array($stage->presentation_state, ['current', 'revision', 'rejected']))
                                <div class="rounded-circle" style="width: 12px; height: 12px; background-color: {{ $fillColor }};"></div>
                            @endif
                        </div>
                        <span class="text-center px-2 fw-medium text-wrap text-break lh-sm" style="font-size: 0.85rem; color: var(--kf-text); max-width: 150px;">{{ $stage->name }}</span>
                        @if($stateLabel)
                            <span class="badge rounded-pill mt-2" style="background-color: var(--kf-surface); color: {{ $stateTextColor }}; font-weight: 600; font-size: 0.7rem; border: 1px solid {{ $borderColor }}; box-shadow: var(--kf-shadow-sm);">{{ $stateLabel }}</span>
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

    </div>
</div>

@if($workflowTimeline->history->isNotEmpty())
<div class="kf-panel mt-4 mb-4">
    <div class="kf-panel-header">
        <h2 class="kf-panel-title">İşlem Geçmişi</h2>
    </div>
    <div class="kf-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                <thead class="bg-light text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <tr>
                        <th scope="col" class="px-4 py-3 border-bottom-0 fw-bold">İşlem</th>
                        <th scope="col" class="px-4 py-3 border-bottom-0 fw-bold">Aşama</th>
                        <th scope="col" class="px-4 py-3 border-bottom-0 fw-bold">Kullanıcı</th>
                        <th scope="col" class="px-4 py-3 border-bottom-0 fw-bold text-end">Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workflowTimeline->history as $historyItem)
                        <tr>
                            <td class="px-4 py-3 fw-medium text-dark">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle" style="width: 8px; height: 8px; background-color: var(--kf-primary);"></div>
                                    {{ $historyItem->actionLabel }}
                                </div>
                                @if($historyItem->comment && $historyItem->comment !== 'İş akışı başlatıldı.')
                                    <div class="mt-2 p-2 bg-light rounded text-muted fw-normal" style="font-size: 0.85rem; border-left: 3px solid var(--kf-primary-subtle);">
                                        "{{ $historyItem->comment }}"
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-secondary">{{ $historyItem->stageContext }}</td>
                            <td class="px-4 py-3 text-dark">{{ $historyItem->actorName }}</td>
                            <td class="px-4 py-3 text-end text-muted" style="font-size: 0.85rem;">{{ $historyItem->timestamp->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
