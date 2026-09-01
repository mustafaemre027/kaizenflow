@extends('layouts.app')

@section('title', 'KaizenFlow - Çalışma Alanım')

@section('content')

@guest
<div class="row justify-content-center pt-5">
    <div class="col-12 col-md-8 col-lg-6 text-center">
        <h1 class="display-5 fw-bold mb-3">Sürekli İyileştirme Yönetimi</h1>
        <p class="lead text-muted mb-5">KaizenFlow ile kurumunuzun iyileştirme fikirlerini toplayın, değerlendirin ve hayata geçirin.</p>
        <a href="{{ route('login') }}" class="kf-btn kf-btn-primary kf-btn-lg px-5 py-3 rounded-pill shadow-sm">Giriş Yap</a>
    </div>
</div>
@endguest

@auth
<x-page-header 
    title="Çalışma Alanım" 
    subtitle="Hoş geldiniz, {{ auth()->user()->name }}. Bugünün genel görünümü."
>
    <x-slot:actions>
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Yeni Kaizen Oluştur
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row g-4 mb-4">
    <!-- Quick Actions -->
    <div class="col-12 col-md-4">
        <x-section-card title="Hızlı İşlemler" class="h-100">
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('kaizens.index') }}" class="kf-btn kf-btn-secondary w-100 justify-content-start">Tüm Kaizenlerim</a>
                
                @if($navContext['canViewDashboard'] ?? false)
                    <a href="{{ route('dashboard.index') }}" class="kf-btn kf-btn-secondary w-100 justify-content-start">Yönetim Dashboardu</a>
                @endif
                
                @if($navContext['canViewApprovals'] ?? false)
                    <a href="{{ route('approvals.index') }}" class="kf-btn kf-btn-secondary w-100 justify-content-start text-primary">Onay Bekleyenler</a>
                @endif
            </div>
        </x-section-card>
    </div>

    <!-- Implementation Work Queue Metrics (if available) -->
    @if(isset($workQueueSummary))
    <div class="col-12 col-md-8">
        <x-section-card title="Uygulama İşlerim" class="h-100">
            <div class="row text-center h-100 align-items-center">
                <div class="col-4">
                    <div class="display-5 fw-bold" style="color: var(--kf-primary);">{{ $workQueueSummary['active_count'] }}</div>
                    <div class="text-muted small fw-medium text-uppercase tracking-wide mt-1">Aktif Görev</div>
                </div>
                <div class="col-4 border-start">
                    <div class="display-5 text-warning fw-bold">{{ $workQueueSummary['today_count'] }}</div>
                    <div class="text-muted small fw-medium text-uppercase tracking-wide mt-1">Bugün</div>
                </div>
                <div class="col-4 border-start">
                    <div class="display-5 text-danger fw-bold">{{ $workQueueSummary['overdue_count'] }}</div>
                    <div class="text-muted small fw-medium text-uppercase tracking-wide mt-1">Gecikmiş</div>
                </div>
            </div>
            
            <x-slot:footer>
                <div class="text-end">
                    <a href="{{ route('implementation.work-queue.index') }}" class="text-decoration-none fw-medium">Detaylara Git &rarr;</a>
                </div>
            </x-slot:footer>
        </x-section-card>
    </div>
    @endif
</div>
@endauth

@endsection
