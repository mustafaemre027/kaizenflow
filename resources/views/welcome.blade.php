@extends('layouts.app')

@section('title', 'KaizenFlow')

@section('content')
<div class="d-flex flex-column justify-content-center align-items-center text-center" style="min-height: 60vh; padding: 2rem 1rem;">
    <h1 class="fw-bold mb-3 text-dark" style="font-size: 3rem; letter-spacing: -0.03em;">KaizenFlow</h1>
    <p class="mb-5" style="font-size: 1.25rem; color: var(--kf-text-secondary); max-width: 600px; line-height: 1.6;">
        Sürekli iyileştirme süreçlerini tek merkezden yönetin. Kaizen fikirlerini oluşturun, değerlendirin ve iyileştirme sürecini izlenebilir hale getirin.
    </p>

    @guest
        <a href="{{ route('login') }}" class="kf-btn kf-btn-primary" style="padding: 0.85rem 2.5rem; font-size: 1.1rem; border-radius: 8px;">
            Giriş Yap
        </a>
    @else
        <a href="{{ route('kaizens.create') }}" class="kf-btn kf-btn-primary" style="padding: 0.85rem 2.5rem; font-size: 1.1rem; border-radius: 8px;">
            Yeni Kaizen Oluştur
        </a>
    @endguest
</div>
@endsection
