@extends('layouts.app')

@section('title', 'Doğrulama Kodu - KaizenFlow')

@section('content')
<div class="kf-auth-shell">

    <div class="kf-auth-brand">
        <div class="kf-auth-wordmark">
            KaizenFlow
        </div>

        <div class="kf-auth-brand-content">
            <h1 class="kf-auth-headline">
                E-posta adresinizi <br> doğrulayın.
            </h1>
            <p class="kf-auth-subhead">
                Devam etmeden önce e-posta adresinize gönderdiğimiz doğrulama kodunu girmelisiniz.
            </p>
        </div>
    </div>

    <div class="kf-auth-content">
        <div class="kf-auth-form-wrapper">
            <div class="kf-auth-form-container">
                <div class="kf-auth-header-group">
                    <span class="kf-auth-eyebrow">GÜVENLİK ADIMI</span>
                    <h2 class="kf-auth-title">E-posta Doğrulama</h2>
                    <p class="kf-auth-desc">E-posta adresinize gönderilen kodu aşağıya girin.</p>
                    <p class="mt-2 text-sm font-medium text-gray-800">
                        {{ str(auth()->user()->email)->mask('*', 2, -4) }}
                    </p>
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success mb-4 text-sm text-green-600" role="status">
                        Yeni bir doğrulama kodu gönderildi.
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.verify') }}" novalidate>
                    @csrf
                    
                    <div class="mb-4">
                        <label for="code" class="kf-auth-label">Doğrulama Kodu</label>
                        <input id="code" type="text" class="form-control kf-auth-field @error('code') is-invalid @enderror" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
                        
                        @error('code')
                            <div class="invalid-feedback" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="kf-auth-actions">
                        <button type="submit" class="kf-auth-submit">
                            Kodu Doğrula
                        </button>
                    </div>
                </form>

                <div class="mt-4 d-flex justify-content-between align-items-center border-top pt-4">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline text-decoration-none">
                            Kodu Yeniden Gönder
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline text-decoration-none text-muted">
                            Çıkış Yap
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="kf-auth-footer">
            &copy; {{ date('Y') }} KaizenFlow. Tüm hakları saklıdır.
        </div>
    </div>

</div>
@endsection
