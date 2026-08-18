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
    <div class="mt-3 mt-md-0">
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-secondary">
            Yeni Kaizen Oluştur
        </a>
    </div>
</div>

<!-- Workflow Visual -->
<div class="kf-workflow-panel">
    <h3 class="kf-workflow-title">Süreç Durumu</h3>
    <div class="kf-workflow-track">
        @php
            $statuses = [
                \App\Enums\KaizenStatus::DRAFT,
                \App\Enums\KaizenStatus::SUBMITTED,
                \App\Enums\KaizenStatus::MANAGER_REVIEW,
                \App\Enums\KaizenStatus::APPROVED,
                \App\Enums\KaizenStatus::IN_PROGRESS,
                \App\Enums\KaizenStatus::COMPLETED,
            ];

            $currentIndex = array_search($kaizen->status, $statuses);

            // Handle special states like REVISION_REQUESTED or REJECTED which break the linear flow
            $isRevision = $kaizen->status === \App\Enums\KaizenStatus::REVISION_REQUESTED;
            $isRejected = $kaizen->status === \App\Enums\KaizenStatus::REJECTED;

            // If in a special state, we approximate its position for the linear track visually
            if ($isRevision) $currentIndex = 1; // Generally happens after SUBMITTED
            if ($isRejected) $currentIndex = 2; // Generally happens during MANAGER_REVIEW
        @endphp

        @foreach($statuses as $index => $status)
            @php
                $itemClass = '';
                if ($currentIndex !== false && $index < $currentIndex) {
                    $itemClass = 'past';
                } elseif ($currentIndex !== false && $index === $currentIndex && !$isRevision && !$isRejected) {
                    $itemClass = 'current';
                }
            @endphp

            <div class="kf-workflow-item {{ $itemClass }}">
                <div class="kf-workflow-marker"></div>
                <span class="kf-workflow-label">{{ $status->label() }}</span>
            </div>

            @if($isRevision && $index === 1)
                <div class="kf-workflow-item revision">
                    <div class="kf-workflow-marker"></div>
                    <span class="kf-workflow-label">Revizyon İstendi</span>
                </div>
            @endif

            @if($isRejected && $index === 2)
                <div class="kf-workflow-item rejected">
                    <div class="kf-workflow-marker"></div>
                    <span class="kf-workflow-label">Reddedildi</span>
                </div>
            @endif
        @endforeach
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
            </div>

            <div class="kf-content-block">
                <div class="kf-content-block-header">
                    <span class="kf-content-num">02</span>
                    <h3 class="kf-content-title">Önerilen Durum</h3>
                </div>
                <p class="kf-detail-text">{{ $kaizen->proposed_situation }}</p>
            </div>

            <div class="kf-content-block">
                <div class="kf-content-block-header">
                    <span class="kf-content-num">03</span>
                    <h3 class="kf-content-title">Beklenen Fayda</h3>
                </div>
                <p class="kf-detail-text">{{ $kaizen->expected_benefit }}</p>
            </div>

            @if($kaizen->status->isTerminal() || $kaizen->actual_result)
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
            @endif
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
