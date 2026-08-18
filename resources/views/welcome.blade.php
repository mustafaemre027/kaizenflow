@extends('layouts.app')

@section('title', 'KaizenFlow')

@section('content')
<div class="kf-hero-container">
    <div class="kf-hero-content">
        <h1 class="kf-display mb-4">
            Sürekli iyileştirmeyi<br>
            <span style="color: var(--kf-primary);">görünür bir sürece</span><br>
            dönüştürün.
        </h1>
        
        <p class="kf-lead mb-5" style="max-width: 500px;">
            Kaizen fikirlerini oluşturun, değerlendirme sürecine hazırlayın ve iyileştirmeyi izlenebilir hale getirin.
        </p>

        <div class="d-flex align-items-center gap-3">
            @guest
                <a href="{{ route('login') }}" class="kf-btn kf-btn-primary px-4 py-3" style="font-size: 1.05rem;">
                    Giriş Yap
                </a>
            @else
                <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary px-4 py-3" style="font-size: 1.05rem;">
                    Yeni Kaizen Oluştur
                </a>
            @endguest
        </div>

        <div class="kf-capability-strip">
            <div class="kf-capability-item">
                <h4>Güvenli Erişim</h4>
                <p>Session tabanlı güvenli kullanıcı girişi ile verilerinizi koruyun.</p>
            </div>
            <div class="kf-capability-item">
                <h4>Kaizen Taslağı</h4>
                <p>İyileştirme fikrini yapılandırılmış bir form ile kolayca kaydedin.</p>
            </div>
            <div class="kf-capability-item">
                <h4>Detaylı Takip</h4>
                <p>Kaizen içeriğini ve durumunu tek ekranda görüntüleyin.</p>
            </div>
        </div>
    </div>

    <div class="kf-hero-visual">
        <div class="kf-process-canvas">
            <div class="kf-process-canvas-title">Süreç Mimarisi</div>
            <div class="kf-process-track">
                <div class="kf-process-node active">
                    <div class="kf-process-node-marker"></div>
                    <span class="kf-process-node-label">Fikir</span>
                </div>
                <div class="kf-process-node active">
                    <div class="kf-process-node-marker"></div>
                    <span class="kf-process-node-label">Taslak</span>
                </div>
                <div class="kf-process-node">
                    <div class="kf-process-node-marker"></div>
                    <span class="kf-process-node-label">Değerlendirme</span>
                </div>
                <div class="kf-process-node">
                    <div class="kf-process-node-marker"></div>
                    <span class="kf-process-node-label">Uygulama</span>
                </div>
                <div class="kf-process-node">
                    <div class="kf-process-node-marker"></div>
                    <span class="kf-process-node-label">Sonuç</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
