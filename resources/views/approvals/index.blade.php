@extends('layouts.app')

@section('content')
<x-page-header 
    title="Bekleyen Onaylar" 
    subtitle="İşlem yapmanız gereken Kaizen değerlendirmelerini görüntüleyin."
/>

<x-section-card class="mb-4">
    <form action="{{ route('approvals.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
            <label for="q" class="kf-form-label mb-1">Arama</label>
            <input type="text" class="form-control kf-form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Kaizen Kodu veya Başlık...">
        </div>
        <div class="col-12 col-md-3">
            <label for="department_id" class="kf-form-label mb-1">Departman</label>
            <select class="form-select kf-form-control" id="department_id" name="department_id">
                <option value="">Tümü</option>
                @foreach(\App\Models\Department::active()->get() as $department)
                    <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4 d-flex gap-2">
            <button type="submit" class="kf-btn kf-btn-primary w-100">Filtrele</button>
            @if(request()->anyFilled(['q', 'department_id']))
                <a href="{{ route('approvals.index') }}" class="kf-btn kf-btn-secondary w-100">Temizle</a>
            @endif
        </div>
    </form>
</x-section-card>

@if($approvals->isEmpty())
    <x-empty-state 
        title="Bekleyen onay bulunmuyor." 
        description="Şu an için işlem yapmanız gereken bir Kaizen kaydı yok."
    >
        <x-slot:icon>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
            </svg>
        </x-slot:icon>
    </x-empty-state>
@else
    <div class="kf-table-shell">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Kaizen</th>
                        <th scope="col" class="d-none d-md-table-cell">Kategori / Departman</th>
                        <th scope="col" class="d-none d-lg-table-cell">Oluşturan</th>
                        <th scope="col">Mevcut Aşama</th>
                        <th scope="col" class="d-none d-sm-table-cell text-end">Gönderim Tarihi</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($approvals as $kaizen)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="{{ $kaizen->title }}">{{ $kaizen->title }}</div>
                                <div class="font-monospace text-muted small">{{ $kaizen->code }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="fw-medium text-dark small">{{ $kaizen->category->name }}</div>
                                <div class="text-secondary small">{{ $kaizen->department->name }}</div>
                            </td>
                            <td class="d-none d-lg-table-cell text-secondary small">{{ $kaizen->creator->name }}</td>
                            <td>
                                <span class="badge" style="background-color: var(--kf-primary-soft); color: var(--kf-primary); font-weight: 600;">
                                    {{ $kaizen->workflowInstance->currentStage->name }}
                                </span>
                            </td>
                            <td class="d-none d-sm-table-cell text-end text-secondary small">
                                {{ $kaizen->submitted_at ? $kaizen->submitted_at->format('d.m.Y H:i') : '-' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('kaizens.show', $kaizen) }}" class="kf-btn kf-btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                                    İncele
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($approvals->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $approvals->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
