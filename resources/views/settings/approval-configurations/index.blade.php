@extends('layouts.app')

@section('title', 'Onay Yapılandırmaları - KaizenFlow')

@section('content')
<div class="kf-page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <span class="kf-page-eyebrow">YÖNETİM</span>
        <h1 class="kf-page-title">Onay Yapılandırmaları</h1>
        <p class="kf-page-desc">Sistem genelinde kullanılacak onay akışlarını yönetin.</p>
    </div>
    @can('create', \App\Models\ApprovalWorkflow::class)
    <div>
        <a href="{{ route('settings.approval-configurations.create') }}" class="kf-btn kf-btn-primary">Yeni Yapılandırma</a>
    </div>
    @endcan
</div>

<div class="kf-panel">
    @if($workflows->isEmpty())
        <div class="kf-panel-body text-center py-5">
            <p class="text-muted">Kayıtlı onay yapılandırması bulunmuyor.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th scope="col" class="text-uppercase text-muted" style="font-size: 0.8rem;">Kod</th>
                        <th scope="col" class="text-uppercase text-muted" style="font-size: 0.8rem;">Ad</th>
                        <th scope="col" class="text-uppercase text-muted" style="font-size: 0.8rem;">Versiyon</th>
                        <th scope="col" class="text-uppercase text-muted" style="font-size: 0.8rem;">Durum</th>
                        <th scope="col" class="text-uppercase text-muted text-end" style="font-size: 0.8rem;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workflows as $workflow)
                        <tr>
                            <td class="fw-medium">{{ $workflow->code }}</td>
                            <td>{{ $workflow->name }}</td>
                            <td>v{{ $workflow->version }}</td>
                            <td>
                                @if($workflow->is_default)
                                    <span class="kf-badge kf-badge-priority">Varsayılan</span>
                                @endif
                                @if($workflow->is_active)
                                    <span class="kf-badge kf-badge-neutral" style="border-color: #38a169; color: #38a169; background: #e6f4ea;">Aktif</span>
                                @else
                                    <span class="kf-badge kf-badge-neutral">Pasif</span>
                                @endif
                                @if($workflow->published_at === null)
                                    <span class="kf-badge kf-badge-neutral" style="border-color: #d69e2e; color: #d69e2e; background: #fefcbf;">Taslak</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('settings.approval-configurations.show', $workflow->id) }}" class="kf-btn kf-btn-secondary kf-btn-sm" aria-label="{{ $workflow->name }} detaylarını görüntüle">Detay</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-4">
    {{ $workflows->links() }}
</div>
@endsection
