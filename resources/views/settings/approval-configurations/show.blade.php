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

        @if($workflow->approver_resolution_mode === \App\Enums\ApproverResolutionMode::CAPABILITY_RULE)
        <div class="kf-panel mt-4">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Onaylayıcı Kuralları</h2>
            </div>
            <div class="kf-panel-body">
                @foreach($workflow->stages->sortBy('sequence')->where('is_active', true) as $stage)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Aşama {{ $stage->sequence }}: {{ $stage->name }}</h5>
                            
                            @if($workflow->published_at === null && Gate::check('update', \App\Models\ApprovalWorkflow::class))
                                <form action="{{ route('settings.approval-configurations.stages.approver-rule', [$workflow->id, $stage->id]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="mb-3">
                                        <label for="capability_{{ $stage->id }}" class="form-label">Onaylayıcı Yetkisi</label>
                                        <select name="capability" id="capability_{{ $stage->id }}" class="form-select @error('capability') is-invalid @enderror" aria-invalid="{{ $errors->has('capability') ? 'true' : 'false' }}">
                                            <option value="">Seçiniz</option>
                                            <option value="kaizen.opex_review" {{ optional($stage->approverRule)->capability?->value === 'kaizen.opex_review' ? 'selected' : '' }}>OPEX Ön Değerlendirmesi (kaizen.opex_review)</option>
                                            <option value="kaizen.department_approve" {{ optional($stage->approverRule)->capability?->value === 'kaizen.department_approve' ? 'selected' : '' }}>Departman Yöneticisi Onayı (kaizen.department_approve)</option>
                                            <option value="kaizen.board_approve" {{ optional($stage->approverRule)->capability?->value === 'kaizen.board_approve' ? 'selected' : '' }}>Kaizen Kurulu Onayı (kaizen.board_approve)</option>
                                        </select>
                                        @error('capability')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" name="is_active" id="is_active_{{ $stage->id }}" class="form-check-input" value="1" {{ optional($stage->approverRule)->is_active !== false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active_{{ $stage->id }}">Kural Aktif</label>
                                    </div>
                                    <button type="submit" class="kf-btn kf-btn-primary">Kaydet</button>
                                </form>
                            @else
                                <div class="mb-2">
                                    <strong>Yetki:</strong> 
                                    @if(optional($stage->approverRule)->capability?->value === 'kaizen.opex_review') OPEX Ön Değerlendirmesi
                                    @elseif(optional($stage->approverRule)->capability?->value === 'kaizen.department_approve') Departman Yöneticisi Onayı
                                    @elseif(optional($stage->approverRule)->capability?->value === 'kaizen.board_approve') Kaizen Kurulu Onayı
                                    @else Belirlenmedi @endif
                                </div>
                                <div class="mb-2">
                                    <strong>Kapsam:</strong>
                                    {{ optional($stage->approverRule)->scope_source ? optional($stage->approverRule)->scope_source->name : '-' }}
                                </div>
                                <div>
                                    <strong>Durum:</strong> {{ optional($stage->approverRule)->is_active ? 'Aktif' : 'Pasif' }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="kf-detail-sidebar">
        <div class="kf-panel">
            <div class="kf-panel-header">
                <h2 class="kf-panel-title">Durum Bilgileri</h2>
            </div>
            <div class="kf-panel-body">
                <ul class="kf-meta-list">
                    <li class="kf-meta-item">
                        <span class="kf-meta-label">Çözümleme Modu</span>
                        <span class="kf-meta-value">
                            @if($workflow->approver_resolution_mode === \App\Enums\ApproverResolutionMode::LEGACY_GROUP)
                                Eski Grup Sistemi
                            @else
                                Dinamik Kural
                            @endif
                        </span>
                    </li>
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
