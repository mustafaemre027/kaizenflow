@extends('layouts.app')

@section('title', 'Onay Yapılandırmaları - KaizenFlow')

@section('content')
<x-page-header 
    title="Onay Yapılandırmaları" 
    subtitle="Sistem genelinde kullanılacak onay akışlarını yönetin."
>
    <x-slot:actions>
        @can('create', \App\Models\ApprovalWorkflow::class)
            <a href="{{ route('settings.approval-configurations.create') }}" class="kf-btn kf-btn-primary">Yeni Yapılandırma</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if($workflows->isEmpty())
    <x-empty-state 
        title="Onay yapılandırması bulunmuyor" 
        description="Sisteme kayıtlı onay yapılandırması bulunmuyor. Yeni bir yapılandırma oluşturarak başlayabilirsiniz."
    >
        <x-slot:icon>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
        </x-slot:icon>
    </x-empty-state>
@else
    <div class="kf-table-shell">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Kod</th>
                        <th scope="col">Ad</th>
                        <th scope="col">Versiyon</th>
                        <th scope="col">Durum</th>
                        <th scope="col" class="text-end">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workflows as $workflow)
                        <tr>
                            <td class="font-monospace text-muted fw-bold small">{{ $workflow->code }}</td>
                            <td class="fw-semibold text-dark">{{ $workflow->name }}</td>
                            <td class="text-secondary">v{{ $workflow->version }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($workflow->is_default)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Varsayılan</span>
                                    @endif
                                    @if($workflow->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">Pasif</span>
                                    @endif
                                    @if($workflow->published_at === null)
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">Taslak</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('settings.approval-configurations.show', $workflow->id) }}" class="kf-btn kf-btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;" aria-label="{{ $workflow->name }} detaylarını görüntüle">Detay</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($workflows->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $workflows->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
