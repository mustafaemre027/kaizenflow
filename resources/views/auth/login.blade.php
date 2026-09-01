@extends('layouts.app')

@section('title', 'Giriş Yap - KaizenFlow')

@section('content')
<!-- Auth Shell -->
<div style="min-height: 100dvh; display: flex; align-items: center; justify-content: center; background-color: var(--kf-app-bg); padding: 1.5rem;">
    
    <div style="width: 100%; max-width: 1120px; background-color: var(--kf-surface); border-radius: 20px; box-shadow: var(--kf-shadow-lg); border: 1px solid var(--kf-border-light); overflow: hidden; display: flex; flex-direction: column;">
        <div class="row g-0">
            <!-- Left Branding Panel (Hidden on mobile) -->
            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between text-white p-5" style="background-color: var(--kf-primary); position: relative; overflow: hidden;">
                <!-- Decorative background elements -->
                <div style="position: absolute; top: -10%; right: -10%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%); border-radius: 50%;"></div>
                
                <div style="position: relative; z-index: 2;">
                    <div class="fw-bold d-flex align-items-center gap-2 mb-5" style="font-size: 1.5rem; letter-spacing: -0.02em;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        KaizenFlow
                    </div>

                    <div style="margin-top: 4rem;">
                        <span class="d-inline-block fw-bold text-uppercase mb-3" style="font-size: 12px; letter-spacing: 0.1em; color: rgba(255,255,255,0.8);">Sistem Erişimi</span>
                        <h1 class="fw-bold mb-4" style="font-size: 2.5rem; line-height: 1.1; letter-spacing: -0.02em;">
                            Operasyonel mükemmelliğe giriş yapın.
                        </h1>
                        <p style="font-size: 1.125rem; color: rgba(255,255,255,0.9); line-height: 1.6; max-width: 400px;">
                            Kurumunuzun tüm kaizen süreçlerini, değerlendirme akışlarını ve raporlarını tek bir güvenli noktadan yönetin.
                        </p>
                    </div>
                </div>

                <!-- Benefits List -->
                <div style="position: relative; z-index: 2; margin-top: 4rem;">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 24px; height: 24px; background-color: rgba(255,255,255,0.2);">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            <span class="fw-medium" style="font-size: 15px;">Standartlaştırılmış Fikir Havuzu</span>
                        </li>
                        <li class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 24px; height: 24px; background-color: rgba(255,255,255,0.2);">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            <span class="fw-medium" style="font-size: 15px;">Otomatik Yönlendirme ve Onaylar</span>
                        </li>
                        <li class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 24px; height: 24px; background-color: rgba(255,255,255,0.2);">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            <span class="fw-medium" style="font-size: 15px;">Gelişmiş Performans İzleme</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Login Form Panel -->
            <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-sm-5 bg-white">
                <div style="width: 100%; max-width: 400px;">
                    <!-- Mobile Brand Visible Only Below LG -->
                    <div class="d-flex d-lg-none align-items-center gap-2 mb-4 text-dark fw-bold" style="font-size: 1.5rem; letter-spacing: -0.02em;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--kf-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        KaizenFlow
                    </div>

                    <div class="mb-4">
                        <h2 class="fw-bold text-dark mb-2" style="font-size: 1.75rem; letter-spacing: -0.02em;">Hoş geldiniz</h2>
                        <p class="text-muted" style="font-size: 15px;">KaizenFlow hesabınıza erişmek için giriş yapın.</p>
                    </div>

                    <x-flash-messages />

                    <form method="POST" action="{{ route('login.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="kf-form-label">E-posta Adresi</label>
                            <input id="email" type="email" class="form-control kf-form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus style="height: 44px;">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="kf-form-label mb-0">Parola</label>
                                <a href="{{ route('password.request') }}" class="text-decoration-none fw-medium" style="font-size: 13px;">Şifremi unuttum</a>
                            </div>
                            <input id="password" type="password" class="form-control kf-form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" style="height: 44px;">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button type="submit" class="kf-btn kf-btn-primary w-100 fw-semibold" style="height: 44px; font-size: 15px;">
                            Giriş Yap
                        </button>
                    </form>

                    <div class="mt-4 text-center d-flex align-items-center justify-content-center gap-2 text-muted" style="font-size: 13px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Oturumunuz güvenli bir şekilde şifrelenmektedir
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
