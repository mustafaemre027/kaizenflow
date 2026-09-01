@extends('layouts.app')

@section('title', 'KaizenFlow - Sürekli İyileştirme Yönetimi')

@section('content')

@guest
<!-- PUBLIC LANDING -->
<div style="background-color: var(--kf-app-bg); min-height: 100vh; display: flex; flex-direction: column;">
    <!-- Simple Header -->
    <header style="height: 72px; padding: 0 5%; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--kf-border-light); background-color: var(--kf-surface);">
        <div class="fw-bold d-flex align-items-center gap-2" style="font-size: 1.25rem; color: var(--kf-text); letter-spacing: -0.02em;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--kf-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            KaizenFlow
        </div>
        <a href="{{ route('login') }}" class="kf-btn kf-btn-primary">Giriş Yap</a>
    </header>

    <main style="flex: 1; padding: 48px 5%;">
        <!-- Hero Section -->
        <div style="max-width: 1200px; margin: 0 auto;">
            <div class="row align-items-center mb-5 g-5">
                <div class="col-lg-6">
                    <span class="d-inline-block fw-bold text-uppercase mb-3" style="font-size: 12px; letter-spacing: 0.1em; color: var(--kf-primary);">Kurumsal Verimlilik</span>
                    <h1 class="fw-bold mb-4" style="font-size: clamp(2rem, 4vw, 3rem); line-height: 1.1; letter-spacing: -0.02em;">
                        Sürekli iyileştirmeyi <br class="d-none d-lg-block"> tek bir akışta yönetin.
                    </h1>
                    <p class="mb-5" style="font-size: 1.125rem; color: var(--kf-text-secondary); max-width: 500px; line-height: 1.6;">
                        Kaizen fikirlerinin oluşturulması, değerlendirilmesi ve uygulama sürecini karmaşadan uzak, tek merkezden izleyin.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="{{ route('login') }}" class="kf-btn kf-btn-primary kf-btn-lg">Hemen Başlayın</a>
                    </div>
                </div>
                
                <div class="col-lg-6 d-none d-lg-block">
                    <!-- Workflow Visual Built with HTML/CSS -->
                    <div class="p-4" style="background-color: var(--kf-surface); border: 1px solid var(--kf-border-light); border-radius: 16px; box-shadow: var(--kf-shadow-lg);">
                        <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background-color: var(--kf-danger);"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background-color: var(--kf-warning);"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background-color: var(--kf-success);"></div>
                            <div class="fw-medium ms-2" style="font-size: 13px; color: var(--kf-text-muted);">Kaizen Akışı</div>
                        </div>
                        
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center gap-3 p-3 rounded" style="background-color: var(--kf-surface-subtle);">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: white; border: 1px solid var(--kf-border-light); display: flex; align-items: center; justify-content: center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--kf-text-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="font-size: 14px;">Yeni Fikir Kaydı</div>
                                    <div style="font-size: 12px; color: var(--kf-text-muted);">Üretim hattı düzenlemesi</div>
                                </div>
                                <span class="kf-badge kf-badge-draft">Taslak</span>
                            </div>

                            <div class="d-flex align-items-center gap-3 p-3 rounded border" style="background-color: white; border-color: var(--kf-border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: var(--kf-warning-soft); display: flex; align-items: center; justify-content: center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--kf-warning-text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="font-size: 14px;">Değerlendirme</div>
                                    <div style="font-size: 12px; color: var(--kf-text-muted);">Mühendislik Onayı Bekliyor</div>
                                </div>
                                <span class="kf-badge kf-badge-warning">Bekliyor</span>
                            </div>

                            <div class="d-flex align-items-center gap-3 p-3 rounded" style="background-color: var(--kf-surface-subtle);">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: var(--kf-success-soft); display: flex; align-items: center; justify-content: center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--kf-success-text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="font-size: 14px;">Tamamlandı</div>
                                    <div style="font-size: 12px; color: var(--kf-text-muted);">Ambalaj firesi %15 azaltıldı</div>
                                </div>
                                <span class="kf-badge kf-badge-success">Onaylandı</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Capabilities -->
            <div class="row g-4 mt-5">
                <div class="col-md-4">
                    <div class="p-4 rounded h-100" style="background-color: var(--kf-surface); border: 1px solid var(--kf-border-light);">
                        <div class="mb-3" style="color: var(--kf-primary);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <h3 class="fw-semibold mb-2" style="font-size: 16px;">Fikirleri Toplayın</h3>
                        <p class="mb-0" style="font-size: 14px; color: var(--kf-text-secondary);">Tüm çalışanların iyileştirme önerilerini standart bir form yapısında toplayın ve kaybolmasını engelleyin.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded h-100" style="background-color: var(--kf-surface); border: 1px solid var(--kf-border-light);">
                        <div class="mb-3" style="color: var(--kf-primary);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <h3 class="fw-semibold mb-2" style="font-size: 16px;">Değerlendirme Akışı</h3>
                        <p class="mb-0" style="font-size: 14px; color: var(--kf-text-secondary);">Departman ve kategori bazlı otomatik yönlendirme sayesinde onay mekanizmalarını hızlandırın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded h-100" style="background-color: var(--kf-surface); border: 1px solid var(--kf-border-light);">
                        <div class="mb-3" style="color: var(--kf-primary);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        </div>
                        <h3 class="fw-semibold mb-2" style="font-size: 16px;">Sonuçları İzleyin</h3>
                        <p class="mb-0" style="font-size: 14px; color: var(--kf-text-secondary);">Kazanımları, uygulanan kaizenleri ve operasyonel verimliliği ölçülebilir şekilde izleyin.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer style="padding: 24px; text-align: center; font-size: 13px; color: var(--kf-text-muted); border-top: 1px solid var(--kf-border-light); background-color: var(--kf-surface);">
        &copy; {{ date('Y') }} KaizenFlow. Tüm hakları saklıdır.
    </footer>
</div>
@endguest

@auth
<div class="kf-page-header">
    <div>
        <h1 class="kf-page-title">Çalışma Alanım</h1>
        <p class="kf-page-subtitle">Hoş geldiniz, {{ auth()->user()->name }}. Bugünün genel görünümü.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Yeni Kaizen
        </a>
    </div>
</div>

@if(isset($workQueueSummary))
<div class="row g-4 mb-4">
    <!-- Aktif Görev -->
    <div class="col-12 col-md-4">
        <div class="kf-surface h-100 p-4" style="border-left: 4px solid var(--kf-primary);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="fw-semibold text-uppercase tracking-wide" style="font-size: 12px; color: var(--kf-text-secondary); letter-spacing: 0.05em;">Aktif Görev</div>
                <div style="color: var(--kf-primary);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
            </div>
            <div class="fw-bold" style="font-size: 36px; line-height: 1; color: var(--kf-text);">
                {{ $workQueueSummary['active_count'] }}
            </div>
        </div>
    </div>
    
    <!-- Bugün -->
    <div class="col-12 col-md-4">
        <div class="kf-surface h-100 p-4" style="border-left: 4px solid var(--kf-warning);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="fw-semibold text-uppercase tracking-wide" style="font-size: 12px; color: var(--kf-text-secondary); letter-spacing: 0.05em;">Bugün</div>
                <div style="color: var(--kf-warning);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
            </div>
            <div class="fw-bold" style="font-size: 36px; line-height: 1; color: var(--kf-text);">
                {{ $workQueueSummary['today_count'] }}
            </div>
        </div>
    </div>
    
    <!-- Gecikmiş -->
    <div class="col-12 col-md-4">
        <div class="kf-surface h-100 p-4" style="border-left: 4px solid var(--kf-danger);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="fw-semibold text-uppercase tracking-wide" style="font-size: 12px; color: var(--kf-text-secondary); letter-spacing: 0.05em;">Gecikmiş</div>
                <div style="color: var(--kf-danger);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
            </div>
            <div class="fw-bold" style="font-size: 36px; line-height: 1; color: var(--kf-text);">
                {{ $workQueueSummary['overdue_count'] }}
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-4">
    <!-- Left: Uygulama İşlerim -->
    <div class="col-12 col-lg-8">
        <div class="kf-surface h-100 d-flex flex-column">
            <div class="kf-surface-header d-flex justify-content-between align-items-center">
                <h2 class="kf-surface-title">Uygulama İşlerim</h2>
                <a href="{{ route('implementation.work-queue.index') }}" class="text-decoration-none" style="font-size: 13px; font-weight: 500;">Tümünü Gör</a>
            </div>
            <div class="kf-surface-body flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                @if(isset($workQueueSummary) && $workQueueSummary['active_count'] > 0)
                    <!-- Placeholder representation for active tasks since detailed tasks are queried in the controller for index -->
                    <div class="w-100">
                        <div class="p-3 mb-2 rounded border" style="background-color: var(--kf-app-bg); border-color: var(--kf-border-light) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="fw-semibold" style="font-size: 14px;">Bekleyen Uygulama Görevleri</div>
                                <span class="kf-badge kf-badge-warning">{{ $workQueueSummary['active_count'] }} Adet</span>
                            </div>
                            <div style="font-size: 13px; color: var(--kf-text-muted);">Görevlerinizi detaylı incelemek için Uygulama İşlerim sayfasına gidin.</div>
                            <div class="mt-3">
                                <a href="{{ route('implementation.work-queue.index') }}" class="kf-btn kf-btn-secondary kf-btn-sm" style="font-size: 13px; padding: 4px 12px;">Görevlere Git</a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--kf-text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <div class="fw-medium text-muted">Şu an için bekleyen uygulamanız bulunmuyor.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right: Hızlı İşlemler -->
    <div class="col-12 col-lg-4">
        <div class="kf-surface h-100">
            <div class="kf-surface-header">
                <h2 class="kf-surface-title">Hızlı İşlemler</h2>
            </div>
            <div class="kf-surface-body p-3">
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('kaizens.index') }}" class="d-flex align-items-center p-3 rounded text-decoration-none" style="background-color: var(--kf-app-bg); border: 1px solid var(--kf-border-light); color: var(--kf-text); transition: all 0.2s;">
                        <div class="d-flex align-items-center justify-content-center rounded bg-white me-3" style="width: 32px; height: 32px; border: 1px solid var(--kf-border-light);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <span class="fw-medium flex-grow-1" style="font-size: 14px;">Tüm Kaizenlerim</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--kf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                    
                    @if($navContext['canViewDashboard'] ?? false)
                    <a href="{{ route('dashboard.index') }}" class="d-flex align-items-center p-3 rounded text-decoration-none" style="background-color: var(--kf-app-bg); border: 1px solid var(--kf-border-light); color: var(--kf-text); transition: all 0.2s;">
                        <div class="d-flex align-items-center justify-content-center rounded bg-white me-3" style="width: 32px; height: 32px; border: 1px solid var(--kf-border-light);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        </div>
                        <span class="fw-medium flex-grow-1" style="font-size: 14px;">Yönetim Dashboardu</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--kf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                    @endif
                    
                    @if($navContext['canViewApprovals'] ?? false)
                    <a href="{{ route('approvals.index') }}" class="d-flex align-items-center p-3 rounded text-decoration-none" style="background-color: var(--kf-primary-soft); border: 1px solid var(--kf-primary-soft); color: var(--kf-primary-text); transition: all 0.2s;">
                        <div class="d-flex align-items-center justify-content-center rounded bg-white me-3" style="width: 32px; height: 32px; border: 1px solid white;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <span class="fw-medium flex-grow-1" style="font-size: 14px;">Onay Bekleyenler</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.5;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endauth

@endsection
