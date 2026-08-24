@extends('layouts.app')

@section('title', $workflow->name . ' - Onay Yapılandırması')

@section('content')
<div class="kf-page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <span class="kf-page-eyebrow">YÖNETİM > ONAY YAPILANDIRMALARI</span>
        <h1 class="kf-page-title">{{ $workflow->name }}</h1>
        <p class="kf-page-desc">{{ $workflow->code }} (v{{ $workflow->version }})</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($workflow->published_at === null)
            @can('update', \App\Models\ApprovalWorkflow::class)
                <a href="{{ route('settings.approval-configurations.edit', $workflow->id) }}" class="kf-btn kf-btn-secondary">Düzenle</a>
            @endcan
            
            @can('publish', \App\Models\ApprovalWorkflow::class)
                <form action="{{ route('settings.approval-configurations.publish', $workflow->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="kf-btn kf-btn-primary">Yayınla</button>
                </form>
            @endcan
        @else
            @if(!$workflow->is_default && $workflow->is_active)
                @can('setDefault', \App\Models\ApprovalWorkflow::class)
                    <form action="{{ route('settings.approval-configurations.set-default', $workflow->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="kf-btn kf-btn-secondary">Varsayılan Yap</button>
                    </form>
                @endcan
            @endif

            @if($workflow->is_active)
                @can('deactivate', \App\Models\ApprovalWorkflow::class)
                    <form action="{{ route('settings.approval-configurations.deactivate', $workflow->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="kf-btn kf-btn-danger">Pasifleştir</button>
                    </form>
                @endcan
            @endif
        @endif
    </div>
</div>

<div class="kf-detail-grid">
    <div class="kf-detail-main" style="min-width: 0;">
        <div class="kf-panel mb-4">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Açıklama</h2>
            </div>
            <div class="kf-panel-body">
                @if($workflow->description)
                    <div class="kf-detail-text" style="word-break: break-word;">{{ $workflow->description }}</div>
                @else
                    <span class="text-muted">Açıklama bulunmuyor.</span>
                @endif
            </div>
        </div>

        <div class="kf-panel">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Onay Aşamaları</h2>
            </div>
            <div class="kf-panel-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 60px;">#</th>
                                <th scope="col">Kod</th>
                                <th scope="col">Ad</th>
                                <th scope="col">Özellikler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workflow->stages->sortBy('sequence') as $stage)
                            <tr>
                                <td class="fw-bold text-muted">{{ $stage->sequence }}</td>
                                <td style="word-break: break-word;">{{ $stage->code }}</td>
                                <td style="word-break: break-word;">
                                    {{ $stage->name }}
                                    @if($stage->description)
                                        <small class="d-block text-muted">{{ $stage->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($stage->is_final)
                                        <span class="kf-badge kf-badge-priority">Final Aşaması</span>
                                    @endif
                                    @if(!$stage->is_active)
                                        <span class="kf-badge kf-badge-neutral">Pasif</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="kf-detail-sidebar">
        <div class="kf-panel">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Durum Bilgileri</h2>
            </div>
            <div class="kf-panel-body">
                <ul class="kf-meta-list">
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Aktiflik</span>
                        <span class="kf-meta-value">{{ $workflow->is_active ? 'Aktif' : 'Pasif' }}</span>
                    </li>
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Varsayılan</span>
                        <span class="kf-meta-value">{{ $workflow->is_default ? 'Evet' : 'Hayır' }}</span>
                    </li>
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Yayınlanma</span>
                        <span class="kf-meta-value">{{ $workflow->published_at ? \Carbon\Carbon::parse($workflow->published_at)->format('d.m.Y H:i') : 'Taslak' }}</span>
                    </li>
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Oluşturulma</span>
                        <span class="kf-meta-value">{{ $workflow->created_at ? $workflow->created_at->format('d.m.Y H:i') : '-' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
