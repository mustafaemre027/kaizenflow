@extends('layouts.app')

@section('title', 'Kaizen DetayÄ±: ' . $kaizen->code)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 kf-page-header">
    <div>
        <span class="kf-page-eyebrow">Kaizen DetayÄ±</span>
        <h1 class="kf-page-title mb-2">{{ $kaizen->title }}</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-muted" style="font-family: monospace; font-size: 0.95rem;">{{ $kaizen->code }}</span>
            <span class="text-muted">â€¢</span>
            <span class="kf-badge kf-badge-neutral">{{ $kaizen->status->label() }}</span>
            @if($kaizen->priority)
                <span class="kf-badge kf-badge-priority">{{ $kaizen->priority->label() }}</span>
            @endif
        </div>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        @can('submit', $kaizen)
            @php
                $btnText = $kaizen->status === \App\Enums\KaizenStatus::REVISION_REQUESTED ? 'Yeniden GÃ¶nder' : 'Onaya GÃ¶nder';
            @endphp

            <form action="{{ route('kaizens.submit', $kaizen) }}" method="POST" class="d-inline m-0 p-0">
                @csrf
                <button type="submit" class="kf-btn kf-btn-primary" onclick="return confirm('Bu Kaizen\'i onaya gÃ¶ndermek istediÄŸinize emin misiniz?');">
                    {{ $btnText }}
                </button>
            </form>
            @can('update', $kaizen)
                <a href="{{ route('kaizens.edit', $kaizen) }}" class="kf-btn kf-btn-secondary">
                    DÃ¼zenle
                </a>
            @endcan
        @else
            @can('update', $kaizen)
                <a href="{{ route('kaizens.edit', $kaizen) }}" class="kf-btn kf-btn-primary">
                    DÃ¼zenle
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
        <h3 class="text-uppercase fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.08em; color: var(--kf-primary);">Onay SÃ¼reci</h3>
        @if($workflowTimeline->isAvailable && $workflowTimeline->workflowName)
            <div class="fs-6 fw-medium text-dark">{{ $workflowTimeline->workflowName }}</div>
        @endif
        @if($workflowTimeline->isDraft)
            <div class="p-4 text-center text-muted fst-italic bg-light rounded border">
                SÃ¼reÃ§ henÃ¼z baÅŸlatÄ±lmadÄ±. Kaizen gÃ¶nderildiÄŸinde onay akÄ±ÅŸÄ± oluÅŸturulacaktÄ±r.
            </div>
        @elseif(!$workflowTimeline->isAvailable)
            <div class="p-4 text-center text-muted fst-italic bg-light rounded border">
                Bu kayÄ±t iÃ§in dinamik onay akÄ±ÅŸÄ± bulunmuyor.
            </div>
        @else
            <div class="kf-workflow-track {{ count($workflowTimeline->stages) > 5 ? 'overflow-auto pb-3' : '' }}">
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
                                $stateLabel = 'Mevcut AÅŸama';
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
                            <div class="d-flex align-items-center justify-content-center bg-white mb-2" style="width: 48px; height: 48px; border-radius: 50%; border: 3px solid {{ $borderColor }}; box-shadow: var(--kf-shadow-sm);">
                                @if($iconColor !== 'transparent')
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="{{ $iconColor }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                @else
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
                            <h4 class="kf-gallery-title mb-0 fs-6 fw-medium text-dark">FotoÄŸraflar</h4>
                            <span class="ms-2 badge bg-light text-secondary border fw-normal">{{ $currentSituationAttachments->count() }} fotoÄŸraf</span>
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
                                   data-alt="Mevcut durum fotoÄŸrafÄ± {{ $index + 1 }}"
                                   aria-label="Mevcut durum fotoÄŸrafÄ± {{ $index + 1 }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                         alt="Mevcut durum fotoÄŸrafÄ± {{ $index + 1 }}"
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
                    <h3 class="kf-content-title">Ã–nerilen Durum</h3>
                </div>
                <p class="kf-detail-text">{{ $kaizen->proposed_situation }}</p>

                @if($proposedSituationAttachments->isNotEmpty())
                    <div class="kf-gallery-container mt-3">
                        <div class="d-flex align-items-center mb-2">
                            <h4 class="kf-gallery-title mb-0 fs-6 fw-medium text-dark">FotoÄŸraflar</h4>
                            <span class="ms-2 badge bg-light text-secondary border fw-normal">{{ $proposedSituationAttachments->count() }} fotoÄŸraf</span>
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
                                   data-alt="Ã–nerilen durum fotoÄŸrafÄ± {{ $index + 1 }}"
                                   aria-label="Ã–nerilen durum fotoÄŸrafÄ± {{ $index + 1 }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}"
                                         alt="Ã–nerilen durum fotoÄŸrafÄ± {{ $index + 1 }}"
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
                    <h3 class="kf-content-title">Beklenen Faydalar</h3>
                </div>
                @if($kaizen->benefits->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0" aria-label="Beklenen faydalar tablosu">
                            <thead class="text-muted small">
                                <tr>
                                    <th scope="col">Fayda TÃ¼rÃ¼</th>
                                    <th scope="col">Beklenen DeÄŸer</th>
                                    <th scope="col">Not</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kaizen->benefits as $benefit)
                                    <tr>
                                        <td>
                                            {{ $benefit->benefitType?->name ?? '-' }}
                                            @if($benefit->benefitType && !$benefit->benefitType->is_active)
                                                <span class="badge bg-secondary ms-1" title="Bu fayda tÃ¼rÃ¼ pasif edilmiÅŸtir">Pasif</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($benefit->expected_value !== null)
                                                {{ $benefit->expected_value }}
                                                @if($benefit->benefitType?->unit_label)
                                                    <span class="text-muted small">{{ $benefit->benefitType->unit_label }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted fst-italic">-</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $benefit->expected_note ?? '' ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(trim($kaizen->expected_benefit ?? '') !== '')
                    {{-- Legacy compatibility: show old free-text benefit if no structured record exists --}}
                    <div class="kf-detail-text text-muted fst-italic small mb-1">Eski kayÄ±t (yapÄ±landÄ±rÄ±lmamÄ±ÅŸ)</div>
                    <p class="kf-detail-text">{{ $kaizen->expected_benefit }}</p>
                @else
                    <p class="kf-detail-text text-muted fst-italic">Beklenen fayda bilgisi girilmemiÅŸ.</p>
                @endif
            </div>

            <div class="kf-content-block">
                <div class="kf-content-block-header">
                    <span class="kf-content-num">04</span>
                    <h3 class="kf-content-title">GerÃ§ekleÅŸen SonuÃ§</h3>
                </div>
                @if($kaizen->actual_result)
                    <p class="kf-detail-text">{{ $kaizen->actual_result }}</p>

                    @if($kaizen->benefits->whereNotNull('realized_value')->count() > 0 || $kaizen->benefits->whereNotNull('realized_note')->count() > 0)
                        <h4 class="mt-4 fs-6 fw-bold text-dark mb-3">GerÃ§ekleÅŸen Faydalar</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <thead class="text-muted small">
                                    <tr>
                                        <th scope="col">Fayda TÃ¼rÃ¼</th>
                                        <th scope="col">GerÃ§ekleÅŸen DeÄŸer</th>
                                        <th scope="col">Not</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kaizen->benefits as $benefit)
                                        @if($benefit->realized_value !== null || $benefit->realized_note !== null)
                                            <tr>
                                                <td>
                                                    {{ $benefit->benefitType?->name ?? '-' }}
                                                    @if($benefit->benefitType && !$benefit->benefitType->is_active)
                                                        <span class="badge bg-secondary ms-1">Pasif</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($benefit->realized_value !== null)
                                                        {{ $benefit->realized_value }}
                                                        @if($benefit->benefitType?->unit_label)
                                                            <span class="text-muted small">{{ $benefit->benefitType->unit_label }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted fst-italic">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-muted small">{{ $benefit->realized_note ?? '-' }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif($kaizen->realized_benefit)
                        <h4 class="mt-4 fs-6 fw-bold text-dark mb-3">GerÃ§ekleÅŸen Fayda</h4>
                        <div class="kf-detail-text text-muted fst-italic small mb-1">Eski kayÄ±t (yapÄ±landÄ±rÄ±lmamÄ±ÅŸ)</div>
                        <p class="kf-detail-text">{{ $kaizen->realized_benefit }}</p>
                    @endif
                @else
                    @can('completeImplementation', $kaizen)
                        @if($kaizen->status === \App\Enums\KaizenStatus::IN_PROGRESS)
                            <form action="{{ route('kaizens.implementation.complete', $kaizen) }}" method="POST" class="mt-3">
                                @csrf
                                <div class="kf-form-group mb-4">
                                    <label for="actual_result" class="kf-form-label visually-hidden">GerÃ§ekleÅŸen SonuÃ§ AÃ§Ä±klamasÄ±</label>
                                    <textarea class="kf-form-control @error('actual_result') is-invalid @enderror" id="actual_result" name="actual_result" rows="5" maxlength="5000" placeholder="Kaizen uygulamasÄ± sonucunda elde edilen durum ve kazanÄ±mlarÄ± detaylÄ±ca aÃ§Ä±klayÄ±nÄ±z..." required aria-describedby="actualResultHelp">{{ old('actual_result') }}</textarea>
                                    <div id="actualResultHelp" class="form-text">Maksimum 5000 karakter. Bu bilgi onay sonrasÄ±nda raporlarda kullanÄ±lacaktÄ±r.</div>
                                    @error('actual_result')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <h4 class="kf-content-title fs-6 mb-3">GerÃ§ekleÅŸen Faydalar (Opsiyonel)</h4>

                                    @error('benefits')
                                        <div class="alert alert-danger py-2">{{ $message }}</div>
                                    @enderror

                                    <div class="table-responsive">
                                        <table class="table table-sm kf-table border mb-0" id="realizedBenefitsTable">
                                            <thead class="bg-light text-muted small">
                                                <tr>
                                                    <th scope="col" style="width: 25%;">Fayda TÃ¼rÃ¼</th>
                                                    <th scope="col" style="width: 20%;">Beklenen</th>
                                                    <th scope="col" style="width: 20%;">GerÃ§ekleÅŸen DeÄŸer</th>
                                                    <th scope="col" style="width: 30%;">GerÃ§ekleÅŸen Not</th>
                                                    <th scope="col" style="width: 5%;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="realizedBenefitsBody">
                                                @foreach($kaizen->benefits as $index => $benefit)
                                                    <tr class="existing-benefit-row">
                                                        <td class="align-middle">
                                                            <input type="hidden" name="benefits[{{ $index }}][benefit_type_id]" value="{{ $benefit->benefit_type_id }}">
                                                            {{ $benefit->benefitType?->name ?? '-' }}
                                                            @if($benefit->benefitType && !$benefit->benefitType->is_active)
                                                                <span class="badge bg-secondary ms-1">Pasif</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted small align-middle">
                                                            @if($benefit->expected_value !== null)
                                                                {{ $benefit->expected_value }} {{ $benefit->benefitType?->unit_label }}
                                                            @else
                                                                -
                                                            @endif
                                                            @if($benefit->expected_note)
                                                                <i class="bi bi-info-circle ms-1" title="{{ $benefit->expected_note }}" data-bs-toggle="tooltip"></i>
                                                            @endif
                                                        </td>
                                                        <td class="align-middle">
                                                            <div class="input-group input-group-sm">
                                                                <input type="number" step="0.0001" class="form-control" name="benefits[{{ $index }}][realized_value]" value="{{ old('benefits.'.$index.'.realized_value', $benefit->realized_value) }}">
                                                                @if($benefit->benefitType?->unit_label)
                                                                    <span class="input-group-text">{{ $benefit->benefitType->unit_label }}</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="align-middle">
                                                            <input type="text" class="form-control form-control-sm" name="benefits[{{ $index }}][realized_note]" value="{{ old('benefits.'.$index.'.realized_note', $benefit->realized_note) }}" maxlength="5000">
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-2 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addRealizedBenefitBtn">
                                            <i class="bi bi-plus-lg"></i> Yeni Fayda Ekle
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="kf-btn kf-btn-primary" onclick="if(this.form.checkValidity()){this.disabled=true;this.form.submit();}">
                                    UygulamayÄ± Tamamla
                                </button>
                            </form>
                        @else
                            <p class="kf-detail-text text-muted fst-italic">HenÃ¼z sonuÃ§ girilmedi.</p>
                        @endif
                    @else
                        <p class="kf-detail-text text-muted fst-italic">HenÃ¼z sonuÃ§ girilmedi.</p>
                    @endcan
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
                    <h3 class="kf-meta-group-title">SÄ±nÄ±flandÄ±rma</h3>
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
                            <span class="kf-meta-label">OluÅŸturan</span>
                            <span class="kf-meta-value">{{ $kaizen->creator->name }}</span>
                        </li>
                        <li class="kf-meta-item">
                            <span class="kf-meta-label">Atanan KiÅŸi</span>
                            @if($kaizen->assignedUser)
                                <span class="kf-meta-value">{{ $kaizen->assignedUser->name }}</span>
                            @else
                                <span class="kf-meta-value text-muted fst-italic">AtanmadÄ±</span>
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
                            <span class="kf-meta-label">GÃ¶nderim Tarihi</span>
                            @if($kaizen->submitted_at)
                                <span class="kf-meta-value">{{ $kaizen->submitted_at->format('d.m.Y H:i') }}</span>
                            @else
                                <span class="kf-meta-value text-muted fst-italic">HenÃ¼z gÃ¶nderilmedi</span>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="kf-panel mt-4">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Uygulama YÃ¶netimi</h2>
            </div>
            <div class="kf-panel-body p-4">
                <div class="kf-meta-group mb-0">
                    <ul class="kf-meta-list">
                        <li class="kf-meta-item">
                            <span class="kf-meta-label">BaÅŸlangÄ±Ã§ Tarihi</span>
                            @if($kaizen->started_at)
                                <span class="kf-meta-value">{{ \Carbon\Carbon::parse($kaizen->started_at)->format('d.m.Y H:i') }}</span>
                            @else
                                <span class="kf-meta-value text-muted fst-italic">HenÃ¼z baÅŸlatÄ±lmadÄ±</span>
                            @endif
                        </li>
                        <li class="kf-meta-item">
                            <span class="kf-meta-label">Tamamlanma Tarihi</span>
                            @if($kaizen->completed_at)
                                <span class="kf-meta-value">{{ \Carbon\Carbon::parse($kaizen->completed_at)->format('d.m.Y H:i') }}</span>
                            @else
                                <span class="kf-meta-value text-muted fst-italic">HenÃ¼z tamamlanmadÄ±</span>
                            @endif
                        </li>
                    </ul>
                </div>

                @can('assignImplementation', $kaizen)
                    @if($kaizen->status === \App\Enums\KaizenStatus::APPROVED && !$kaizen->assigned_user_id)
                        <div class="mt-4 pt-4 border-top">
                            <h3 class="kf-meta-group-title">Sorumlu Ata</h3>
                            <form action="{{ route('kaizens.implementation.assign', $kaizen) }}" method="POST">
                                @csrf
                                <div class="kf-form-group mb-3">
                                    <label for="assigned_user_id" class="kf-form-label">Uygulama Sorumlusu <span class="text-danger">*</span></label>
                                    @if(empty($implementationCandidates) || $implementationCandidates->isEmpty())
                                        <div class="alert alert-warning py-2 mb-0" style="font-size: 0.85rem;">Bu departmanda atanabilecek aktif kullanÄ±cÄ± bulunmuyor.</div>
                                    @else
                                        <select class="kf-form-control @error('assigned_user_id') is-invalid @enderror" id="assigned_user_id" name="assigned_user_id" required>
                                            <option value="">SeÃ§iniz...</option>
                                            @foreach($implementationCandidates as $candidate)
                                                <option value="{{ $candidate->id }}" {{ old('assigned_user_id') == $candidate->id ? 'selected' : '' }}>
                                                    {{ $candidate->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('assigned_user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>

                                <div class="kf-form-group mb-3">
                                    <label for="target_date" class="kf-form-label">Hedef Tarih <span class="text-danger">*</span></label>
                                    <input type="date" class="kf-form-control @error('target_date') is-invalid @enderror" id="target_date" name="target_date" value="{{ old('target_date') }}" min="{{ date('Y-m-d') }}" required>
                                    @error('target_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="kf-btn kf-btn-primary w-100" {{ empty($implementationCandidates) || $implementationCandidates->isEmpty() ? 'disabled' : '' }}>Kaydet</button>
                            </form>
                        </div>
                    @endif
                @endcan

                @can('startImplementation', $kaizen)
                    @if($kaizen->status === \App\Enums\KaizenStatus::APPROVED && $kaizen->assigned_user_id && $kaizen->target_date)
                        <div class="mt-4 pt-4 border-top">
                            <form action="{{ route('kaizens.implementation.start', $kaizen) }}" method="POST">
                                @csrf
                                <p class="text-muted mb-3" style="font-size: 0.85rem;">Bu iÅŸlem uygulamayÄ± baÅŸlatacak ve durumu IN PROGRESS olarak gÃ¼ncelleyecektir. Bu iÅŸlem geri alÄ±namaz.</p>
                                <button type="submit" class="kf-btn kf-btn-primary w-100" onclick="return confirm('UygulamayÄ± baÅŸlatmak istediÄŸinize emin misiniz?');">
                                    UygulamayÄ± BaÅŸlat
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>

    </div>
</div>

@can('reviewOnWorkflow', $kaizen)
<div class="kf-panel mt-4 mb-4 border-primary border-opacity-25" style="background-color: var(--kf-primary-subtle);">
    <div class="kf-panel-header bg-transparent border-bottom-0 pb-0 pt-4">
        <h2 class="kf-panel-title text-primary d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-shield-check" viewBox="0 0 16 16">
              <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.323.136.14-.051.323-.136a7.2 7.2 0 0 0 .893-.533 10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7.2 7.2 0 0 1-1.084.665c-.426.228-.846.333-1.113.333s-.687-.105-1.113-.333a7.2 7.2 0 0 1-1.084-.665 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
              <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
            </svg>
            DeÄŸerlendirme
        </h2>
    </div>
    <div class="kf-panel-body p-4 pt-3">
        <p class="mb-4 text-dark fw-medium">
            Mevcut aÅŸama: <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1">{{ $kaizen->workflowInstance->currentStage->name }}</span>
        </p>
        <div class="d-flex flex-wrap gap-3">
            <button type="button" class="kf-btn kf-btn-primary px-4" data-bs-toggle="modal" data-bs-target="#approveModal">
                Onayla
            </button>
            <button type="button" class="kf-btn kf-btn-warning px-4" data-bs-toggle="modal" data-bs-target="#revisionModal">
                Revizyon Ä°ste
            </button>
            <button type="button" class="kf-btn kf-btn-danger px-4 ms-md-auto" data-bs-toggle="modal" data-bs-target="#rejectModal">
                Reddet
            </button>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--kf-radius-md);">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="approveModalLabel" style="color: var(--kf-text);">Kaizen'i Onayla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form action="{{ route('kaizens.workflow.approve', $kaizen) }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-3">
                    <p class="text-muted mb-4" style="font-size: 0.95rem;">
                        @if($kaizen->workflowInstance->currentStage->is_final)
                            Bu son onay aÅŸamasÄ±dÄ±r. OnayladÄ±ÄŸÄ±nÄ±zda Kaizen onay sÃ¼reci tamamlanacaktÄ±r.
                        @else
                            Bu deÄŸerlendirmeyi onayladÄ±ÄŸÄ±nÄ±zda Kaizen bir sonraki onay aÅŸamasÄ±na ilerleyecektir.
                        @endif
                    </p>
                    <div class="kf-form-group mb-0">
                        <label for="approveComment" class="kf-form-label">AÃ§Ä±klama (Opsiyonel)</label>
                        <textarea class="kf-form-control" id="approveComment" name="comment" rows="3" maxlength="2000"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="kf-btn kf-btn-secondary" data-bs-dismiss="modal">VazgeÃ§</button>
                    <button type="submit" class="kf-btn kf-btn-primary" onclick="this.disabled=true;this.form.submit();">Onayla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Revision Modal -->
<div class="modal fade" id="revisionModal" tabindex="-1" aria-labelledby="revisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--kf-radius-md);">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="revisionModalLabel" style="color: var(--kf-text);">Revizyon Ä°ste</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form action="{{ route('kaizens.workflow.request-revision', $kaizen) }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-3">
                    <p class="text-muted mb-4" style="font-size: 0.95rem;">
                        Kaizen sahibinin dÃ¼zenleme yapabilmesi iÃ§in gerekli deÄŸiÅŸiklikleri aÃ§Ä±klayÄ±n.
                    </p>
                    <div class="kf-form-group mb-0">
                        <label for="revisionComment" class="kf-form-label">AÃ§Ä±klama <span class="text-danger">*</span></label>
                        <textarea class="kf-form-control" id="revisionComment" name="comment" rows="4" maxlength="2000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="kf-btn kf-btn-secondary" data-bs-dismiss="modal">VazgeÃ§</button>
                    <button type="submit" class="kf-btn kf-btn-warning" onclick="if(this.form.checkValidity()){this.disabled=true;this.form.submit();}">Revizyon Ä°ste</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--kf-radius-md);">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="rejectModalLabel" style="color: var(--kf-text);">Kaizen'i Reddet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form action="{{ route('kaizens.workflow.reject', $kaizen) }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-3">
                    <p class="text-muted mb-4" style="font-size: 0.95rem;">
                        Reddetme nedenini belirtin. Bu iÅŸlem mevcut onay sÃ¼recini sonlandÄ±racaktÄ±r.
                    </p>
                    <div class="kf-form-group mb-0">
                        <label for="rejectComment" class="kf-form-label">Reddetme Nedeni <span class="text-danger">*</span></label>
                        <textarea class="kf-form-control" id="rejectComment" name="comment" rows="4" maxlength="2000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="kf-btn kf-btn-secondary" data-bs-dismiss="modal">VazgeÃ§</button>
                    <button type="submit" class="kf-btn kf-btn-danger" onclick="if(this.form.checkValidity()){this.disabled=true;this.form.submit();}">Reddet</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@if($workflowTimeline->history->isNotEmpty())
<div class="kf-panel mt-4 mb-4">
    <div class="kf-panel-header">
        <h2 class="kf-panel-title">Ä°ÅŸlem GeÃ§miÅŸi</h2>
    </div>
    <div class="kf-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                <thead class="bg-light text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <tr>
                        <th scope="col" class="px-4 py-3 border-bottom-0 fw-bold">Ä°ÅŸlem</th>
                        <th scope="col" class="px-4 py-3 border-bottom-0 fw-bold">AÅŸama</th>
                        <th scope="col" class="px-4 py-3 border-bottom-0 fw-bold">KullanÄ±cÄ±</th>
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
                                @if($historyItem->comment && $historyItem->comment !== 'Ä°ÅŸ akÄ±ÅŸÄ± baÅŸlatÄ±ldÄ±.')
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
