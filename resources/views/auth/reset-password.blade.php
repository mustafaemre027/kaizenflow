@extends('layouts.app')

@section('title', 'Yeni Şifre Belirle - KaizenFlow')

@section('content')
<div class="kf-auth-shell">

    <!-- Left Brand Panel -->
    <div class="kf-auth-brand">
        <div class="kf-auth-wordmark">
            KaizenFlow
        </div>

        <div class="kf-auth-brand-content">
            <h1 class="kf-auth-headline">
                Güvenliğinizi yenileyin.
            </h1>
        </div>
    </div>

    <!-- Right Form Side -->
    <div class="kf-auth-content">
        <div class="kf-auth-form-wrapper">
            <div class="kf-auth-form-container">
                <div class="kf-auth-header-group">
                    <span class="kf-auth-eyebrow">ŞİFRE SIFIRLAMA</span>
                    <h2 class="kf-auth-title">Yeni Şifre Belirle</h2>
                    <p class="kf-auth-desc">Lütfen yeni şifrenizi girin.</p>
                </div>

                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-4">
                        <label for="email" class="kf-auth-label">E-posta Adresi</label>
                        <input id="email" type="email" class="form-control kf-auth-field @error('email') is-invalid @enderror" name="email" value="{{ request()->email ?? old('email') }}" required readonly>
                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="kf-auth-label">Yeni Parola</label>
                        <input id="password" type="password" class="form-control kf-auth-field @error('password') is-invalid @enderror" name="password" required autofocus>
                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="kf-auth-label">Yeni Parola (Tekrar)</label>
                        <input id="password_confirmation" type="password" class="form-control kf-auth-field" name="password_confirmation" required>
                    </div>

                    <div class="kf-auth-actions mt-4">
                        <button type="submit" class="kf-auth-submit">
                            Şifremi Sıfırla
                        </button>
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
