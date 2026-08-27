@extends('layouts.app')

@section('title', 'Giriş Yap - KaizenFlow')

@section('content')
<div class="kf-auth-shell">

    <!-- Left Brand Panel -->
    <div class="kf-auth-brand">
        <div class="kf-auth-wordmark">
            KaizenFlow
        </div>

        <div class="kf-auth-brand-content">
            <h1 class="kf-auth-headline">
                Sürekli iyileştirmeyi <br> tek bir akışta yönetin.
            </h1>

            <p class="kf-auth-subhead">
                Kaizen fikirlerinin oluşturulması, değerlendirilmesi ve uygulama sürecini tek merkezden izleyin.
            </p>

            <ul class="kf-auth-features">
                <li>Fikirleri tek merkezde yönetin</li>
                <li>Değerlendirme süreçlerini takip edin</li>
                <li>İyileştirme sonuçlarını görünür hale getirin</li>
            </ul>
        </div>

        <!-- Process Visual -->
        <div class="kf-auth-process d-none d-md-flex">
            <div class="kf-process-step">
                <div class="kf-process-marker"></div>
                <div class="kf-process-label">Fikir</div>
            </div>
            <div class="kf-process-step">
                <div class="kf-process-marker"></div>
                <div class="kf-process-label">Değerlendirme</div>
            </div>
            <div class="kf-process-step">
                <div class="kf-process-marker"></div>
                <div class="kf-process-label">Uygulama</div>
            </div>
            <div class="kf-process-step">
                <div class="kf-process-marker"></div>
                <div class="kf-process-label">Sonuç</div>
            </div>
        </div>
    </div>

    <!-- Right Form Side -->
    <div class="kf-auth-content">
        <div class="kf-auth-form-wrapper">
            <div class="kf-auth-form-container">
                <div class="kf-auth-header-group">
                    <span class="kf-auth-eyebrow">GÜVENLİ ERİŞİM</span>
                    <h2 class="kf-auth-title">Hesabınıza giriş yapın</h2>
                    <p class="kf-auth-desc">KaizenFlow’a devam etmek için bilgilerinizi girin.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf

                    @if (session('status'))
                        <div class="alert alert-success mb-4 text-sm text-green-600" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <label for="email" class="kf-auth-label">E-posta Adresi</label>
                        <input id="email" type="email" class="form-control kf-auth-field @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="kf-auth-label mb-0">Parola</label>
                            <a href="{{ route('password.request') }}" class="text-sm text-decoration-none">Şifremi unuttum</a>
                        </div>
                        <input id="password" type="password" class="form-control kf-auth-field mt-2 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="kf-auth-actions">
                        <button type="submit" class="kf-auth-submit">
                            Giriş Yap
                        </button>
                        <p class="kf-auth-trust">
                            Oturumunuz güvenli şekilde korunur.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div class="kf-auth-footer">
            &copy; {{ date('Y') }} KaizenFlow. Tüm hakları saklıdır.
        </div>
    </div>

</div>
@endsection
