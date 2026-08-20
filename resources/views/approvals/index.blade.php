@extends('layouts.app')

@section('content')
<div class="kf-page-header">
    <span class="kf-page-eyebrow">ONAY YÖNETİMİ</span>
    <h1 class="kf-page-title">Bekleyen Onaylar</h1>
    <p class="kf-page-desc">İşlem yapmanız gereken Kaizen değerlendirmelerini görüntüleyin.</p>
</div>

<div class="kf-panel mb-4">
    <div class="kf-panel-body">
        <form action="{{ route('approvals.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="q" class="form-label kf-form-label mb-1">Arama</label>
                <input type="text" class="form-control kf-form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Kaizen Kodu veya Başlık...">
            </div>
            <div class="col-md-3">
                <label for="department_id" class="form-label kf-form-label mb-1">Departman</label>
                <select class="form-select kf-form-control" id="department_id" name="department_id">
                    <option value="">Tümü</option>
                    @foreach(\App\Models\Department::active()->get() as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="kf-btn kf-btn-primary w-100">Filtrele</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('approvals.index') }}" class="kf-btn kf-btn-secondary w-100">Temizle</a>
            </div>
        </form>
    </div>
</div>

<div class="kf-panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th scope="col" class="px-4 py-3 border-bottom-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Kaizen</th>
                    <th scope="col" class="px-4 py-3 border-bottom-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Kategori / Departman</th>
                    <th scope="col" class="px-4 py-3 border-bottom-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Oluşturan</th>
                    <th scope="col" class="px-4 py-3 border-bottom-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Mevcut Aşama</th>
                    <th scope="col" class="px-4 py-3 border-bottom-0 text-muted text-end" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Gönderim Tarihi</th>
                    <th scope="col" class="px-4 py-3 border-bottom-0"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvals as $kaizen)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold" style="color: var(--kf-primary-dark);">{{ $kaizen->code }}</div>
                            <div class="text-truncate" style="max-width: 250px;" title="{{ $kaizen->title }}">{{ $kaizen->title }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="fw-medium text-dark">{{ $kaizen->category->name }}</div>
                            <div class="text-secondary" style="font-size: 0.85rem;">{{ $kaizen->department->name }}</div>
                        </td>
                        <td class="px-4 py-3 text-secondary">{{ $kaizen->creator->name }}</td>
                        <td class="px-4 py-3">
                            <span class="badge rounded-pill" style="background-color: var(--kf-primary-subtle); color: var(--kf-primary); font-weight: 600;">
                                {{ $kaizen->workflowInstance->currentStage->name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end text-secondary" style="font-size: 0.9rem;">
                            {{ $kaizen->submitted_at ? $kaizen->submitted_at->format('d.m.Y H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('kaizens.show', $kaizen) }}" class="kf-btn kf-btn-primary" style="padding: 0.4rem 1rem; font-size: 0.85rem;">
                                İncele
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-5 text-center text-muted">
                            <div class="mb-2">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.5;">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                </svg>
                            </div>
                            <div class="fw-medium text-dark" style="font-size: 1.1rem;">Bekleyen onay bulunmuyor.</div>
                            <p class="mb-0" style="font-size: 0.9rem;">Şu an için işlem yapmanız gereken bir Kaizen kaydı yok.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $approvals->links() }}
</div>
@endsection
