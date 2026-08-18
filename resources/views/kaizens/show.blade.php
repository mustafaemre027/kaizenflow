@extends('layouts.app')

@section('title', 'Kaizen Detayı: ' . $kaizen->code)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold">{{ $kaizen->code }}</span>
                    <div>
                        <span class="badge bg-secondary">{{ $kaizen->status->label() }}</span>
                        @if($kaizen->priority)
                            <span class="badge bg-info text-dark">{{ $kaizen->priority->label() }}</span>
                        @endif
                    </div>
                </div>
                <h2 class="h4 mb-0 text-primary">{{ $kaizen->title }}</h2>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2">Mevcut Durum</h5>
                    <p class="mb-0 text-muted" style="white-space: pre-line;">{{ $kaizen->current_situation }}</p>
                </div>
                <div class="mb-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2">Önerilen Durum</h5>
                    <p class="mb-0 text-muted" style="white-space: pre-line;">{{ $kaizen->proposed_situation }}</p>
                </div>
                <div class="mb-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2">Beklenen Fayda</h5>
                    <p class="mb-0 text-muted" style="white-space: pre-line;">{{ $kaizen->expected_benefit }}</p>
                </div>
                @if($kaizen->status->isTerminal() || $kaizen->actual_result)
                <div class="mb-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2">Gerçekleşen Sonuç</h5>
                    @if($kaizen->actual_result)
                        <p class="mb-0 text-muted" style="white-space: pre-line;">{{ $kaizen->actual_result }}</p>
                    @else
                        <p class="mb-0 text-muted fst-italic">Henüz sonuç girilmedi.</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="h6 mb-0 fw-bold">Kaizen Bilgileri</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <span class="d-block text-muted small fw-bold text-uppercase">Kategori</span>
                        <span class="text-dark">{{ $kaizen->category->name }}</span>
                    </li>
                    <li class="mb-3">
                        <span class="d-block text-muted small fw-bold text-uppercase">Departman</span>
                        <span class="text-dark">{{ $kaizen->department->name }}</span>
                    </li>
                    <li class="mb-3">
                        <span class="d-block text-muted small fw-bold text-uppercase">Oluşturan</span>
                        <span class="text-dark">{{ $kaizen->creator->name }}</span>
                    </li>
                    <li class="mb-3">
                        <span class="d-block text-muted small fw-bold text-uppercase">Atanan Kişi</span>
                        <span class="text-dark">{{ $kaizen->assignedUser ? $kaizen->assignedUser->name : 'Atanmadı' }}</span>
                    </li>
                    <li class="mb-3">
                        <span class="d-block text-muted small fw-bold text-uppercase">Hedef Tarih</span>
                        <span class="text-dark">{{ $kaizen->target_date ? $kaizen->target_date->format('d.m.Y') : 'Belirtilmedi' }}</span>
                    </li>
                    <li>
                        <span class="d-block text-muted small fw-bold text-uppercase">Gönderim Tarihi</span>
                        <span class="text-dark">{{ $kaizen->submitted_at ? $kaizen->submitted_at->format('d.m.Y H:i') : 'Henüz gönderilmedi' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
