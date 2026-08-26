@extends('layouts.app')

@section('title', 'Şifremi Unuttum - KaizenFlow')

@section('content')
<div class="kf-auth-shell">

    <!-- Left Brand Panel -->
    <div class="kf-auth-brand">
        <div class="kf-auth-wordmark">
            KaizenFlow
        </div>

        <div class="kf-auth-brand-content">
            <h1 class="kf-auth-headline">
                Şifrenizi güvenle sıfırlayın.
            </h1>
            <p class="kf-auth-subhead">
                Sürekli iyileştirme yolculuğuna kaldığınız yerden devam edin.
            </p>
        </div>
    </div>

    <!-- Right Form Side -->
    <div class="kf-auth-content">
        <div class="kf-auth-form-wrapper">
            <div class="kf-auth-form-container">
                <div class="kf-auth-header-group">
                    <span class="kf-auth-eyebrow">ŞİFRE SIFIRLAMA</span>
                    <h2 class="kf-auth-title">Şifremi Unuttum</h2>
                    <p class="kf-auth-desc">Kayıtlı e-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success mb-4 text-sm" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="kf-auth-label">E-posta Adresi</label>
                        <input id="email" type="email" class="form-control kf-auth-field @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="kf-auth-actions d-flex flex-column gap-3 mt-4">
                        <button type="submit" class="kf-auth-submit">
                            Sıfırlama Bağlantısı Gönder
                        </button>
                        <a href="{{ route('login') }}" class="text-center text-sm text-decoration-none">
                            Giriş ekranına dön
                        </a>
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
