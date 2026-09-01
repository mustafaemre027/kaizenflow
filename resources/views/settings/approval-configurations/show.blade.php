@extends('layouts.app')

@section('title', $workflow->name . ' - Onay Yapılandırması')

@section('content')
<x-page-header 
    title="{{ $workflow->name }}" 
    subtitle="{{ $workflow->code }} (v{{ $workflow->version }})"
>
    <x-slot:eyebrow>YÖNETİM &gt; ONAY YAPILANDIRMALARI</x-slot:eyebrow>
    <x-slot:actions>
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
    </x-slot:actions>
</x-page-header>

<div class="kf-detail-grid">
    <div class="kf-detail-main" style="min-width: 0;">
        <x-section-card class="mb-4" title="Açıklama">
            @if($workflow->description)
                <div class="kf-detail-text" style="word-break: break-word;">{{ $workflow->description }}</div>
            @else
                <span class="text-muted">Açıklama bulunmuyor.</span>
            @endif
        </x-section-card>

        <x-section-card title="Onay Aşamaları" :no-padding="true">
            <div class="table-responsive d-none d-md-block border-0">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="px-4" style="width: 60px;">#</th>
                            <th scope="col">Kod</th>
                            <th scope="col">Ad</th>
                            <th scope="col" class="px-4 text-end">Özellikler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workflow->stages->sortBy('sequence') as $stage)
                        <tr>
                            <td class="fw-bold text-muted px-4">{{ $stage->sequence }}</td>
                            <td class="font-monospace text-muted small fw-semibold" style="word-break: break-word;">{{ $stage->code }}</td>
                            <td style="word-break: break-word;">
                                <div class="fw-semibold text-dark">{{ $stage->name }}</div>
                                @if($stage->description)
                                    <small class="d-block text-muted mt-1">{{ $stage->description }}</small>
                                @endif
                            </td>
                            <td class="px-4 text-end">
                                @if($stage->is_final)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Final Aşaması</span>
                                @endif
                                @if(!$stage->is_active)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">Pasif</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none p-3">
                <ul class="list-group list-group-flush">
                    @foreach($workflow->stages->sortBy('sequence') as $stage)
                    <li class="list-group-item px-0 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-secondary border rounded-circle" style="width:24px;height:24px;line-height:16px;">{{ $stage->sequence }}</span>
                                <span class="fw-bold text-dark font-monospace small">{{ $stage->code }}</span>
                            </div>
                            <div>
                                @if($stage->is_final)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1" style="font-size: 0.7rem;">Final</span>
                                @endif
                                @if(!$stage->is_active)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1" style="font-size: 0.7rem;">Pasif</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-1 text-dark fw-medium">{{ $stage->name }}</div>
                        @if($stage->description)
                            <div class="text-muted small">{{ $stage->description }}</div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </x-section-card>

        @if($workflow->approver_resolution_mode === \App\Enums\ApproverResolutionMode::CAPABILITY_RULE)
        <x-section-card class="mt-4" title="Onaylayıcı Kuralları" :no-padding="true">
            <div class="p-4 bg-light border-bottom">
                <p class="text-muted small mb-0">Bu iş akışında onaylayıcılar her bir aşama için belirlenmiş dinamik yetki kurallarına göre çözümlenir.</p>
            </div>
            <div class="p-4">
                @foreach($workflow->stages->sortBy('sequence')->where('is_active', true) as $stage)
                    <div class="card mb-3 border border-light-subtle shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-dark fs-6 mb-3">Aşama {{ $stage->sequence }}: {{ $stage->name }}</h5>
                            
                            @if($workflow->published_at === null && Gate::check('update', \App\Models\ApprovalWorkflow::class))
                                <form action="{{ route('settings.approval-configurations.stages.approver-rule', [$workflow->id, $stage->id]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="mb-3">
                                        <label for="capability_{{ $stage->id }}" class="kf-form-label mb-1">Onaylayıcı Yetkisi</label>
                                        <select name="capability" id="capability_{{ $stage->id }}" class="form-select kf-form-control @error('capability') is-invalid @enderror" aria-invalid="{{ $errors->has('capability') ? 'true' : 'false' }}">
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
                                        <label class="form-check-label fw-medium text-dark" for="is_active_{{ $stage->id }}">Kural Aktif</label>
                                    </div>
                                    <button type="submit" class="kf-btn kf-btn-primary btn-sm px-4">Kaydet</button>
                                </form>
                            @else
                                <div class="d-flex flex-column gap-2 text-sm">
                                    <div class="d-flex align-items-center">
                                        <strong class="text-muted" style="width: 80px;">Yetki:</strong> 
                                        <span class="text-dark fw-medium">
                                        @if(optional($stage->approverRule)->capability?->value === 'kaizen.opex_review') OPEX Ön Değerlendirmesi
                                        @elseif(optional($stage->approverRule)->capability?->value === 'kaizen.department_approve') Departman Yöneticisi Onayı
                                        @elseif(optional($stage->approverRule)->capability?->value === 'kaizen.board_approve') Kaizen Kurulu Onayı
                                        @else <span class="text-muted font-italic">Belirlenmedi</span> @endif
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <strong class="text-muted" style="width: 80px;">Kapsam:</strong>
                                        <span class="text-dark fw-medium">{{ optional($stage->approverRule)->scope_source ? optional($stage->approverRule)->scope_source->name : '-' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <strong class="text-muted" style="width: 80px;">Durum:</strong> 
                                        @if(optional($stage->approverRule)->is_active)
                                            <span class="text-success fw-medium d-flex align-items-center gap-1">
                                                <span class="d-inline-block rounded-circle bg-success" style="width: 6px; height: 6px;"></span> Aktif
                                            </span>
                                        @else
                                            <span class="text-secondary fw-medium d-flex align-items-center gap-1">
                                                <span class="d-inline-block rounded-circle bg-secondary" style="width: 6px; height: 6px;"></span> Pasif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-section-card>
        @endif
    </div>

    <div class="kf-detail-sidebar">
        <x-section-card title="Durum Bilgileri" :no-padding="true">
            <ul class="list-group list-group-flush rounded-bottom">
                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1">
                    <span class="small text-muted fw-semibold text-uppercase">Çözümleme Modu</span>
                    <span class="fw-medium text-dark">
                        @if($workflow->approver_resolution_mode === \App\Enums\ApproverResolutionMode::LEGACY_GROUP)
                            Eski Grup Sistemi
                        @else
                            Dinamik Kural
                        @endif
                    </span>
                </li>
                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1 bg-light">
                    <span class="small text-muted fw-semibold text-uppercase">Aktiflik</span>
                    <span class="fw-medium {{ $workflow->is_active ? 'text-success' : 'text-secondary' }}">{{ $workflow->is_active ? 'Aktif' : 'Pasif' }}</span>
                </li>
                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1">
                    <span class="small text-muted fw-semibold text-uppercase">Varsayılan</span>
                    <span class="fw-medium text-dark">{{ $workflow->is_default ? 'Evet' : 'Hayır' }}</span>
                </li>
                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1 bg-light">
                    <span class="small text-muted fw-semibold text-uppercase">Yayınlanma</span>
                    <span class="fw-medium text-dark">{{ $workflow->published_at ? \Carbon\Carbon::parse($workflow->published_at)->format('d.m.Y H:i') : 'Taslak' }}</span>
                </li>
                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1">
                    <span class="small text-muted fw-semibold text-uppercase">Oluşturulma</span>
                    <span class="fw-medium text-dark">{{ $workflow->created_at ? $workflow->created_at->format('d.m.Y H:i') : '-' }}</span>
                </li>
            </ul>
        </x-section-card>
    </div>
</div>
@endsection
