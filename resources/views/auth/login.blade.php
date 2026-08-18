@extends('layouts.app')

@section('title', 'Giriş Yap - KaizenFlow')

@section('content')
<div class="kf-auth-shell">

    <!-- Left Brand Panel -->
    <div class="kf-auth-brand">
        <div class="kf-auth-wordmark">
            KaizenFlow
        </div>

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

    <!-- Right Form Side -->
    <div class="kf-auth-content">
        <div class="kf-auth-form-container">
            <h2 class="kf-auth-title">Tekrar hoş geldiniz</h2>
            <p class="kf-auth-desc">Hesabınızla devam edin.</p>

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

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
                    <label for="password" class="kf-auth-label">Parola</label>
                    <input id="password" type="password" class="form-control kf-auth-field @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="kf-auth-submit">
                    Giriş Yap
                </button>

                <p class="kf-auth-trust">
                    Oturumunuz güvenli şekilde korunur.
                </p>
            </form>

            <div class="kf-auth-footer">
                &copy; {{ date('Y') }} KaizenFlow. Tüm hakları saklıdır.
            </div>
        </div>
    </div>

</div>
@endsection
