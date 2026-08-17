@extends('layouts.app')

@section('title', 'KaizenFlow - Hoş Geldiniz')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 text-center">
        <h1 class="display-4 fw-bold mb-3">KaizenFlow</h1>
        <p class="lead text-muted mb-5">Dijital Kaizen ve Sürekli İyileştirme Yönetim Sistemi</p>

        <div class="card shadow-sm mb-4 text-start">
            <div class="card-header bg-white">
                <h5 class="mb-0">Sistem Durumu</h5>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex align-items-center">
                    <span class="badge bg-success me-3">Hazır</span>
                    Laravel 13 altyapısı kuruldu ve çalışıyor.
                </li>
                <li class="list-group-item d-flex align-items-center">
                    <span class="badge bg-success me-3">Hazır</span>
                    Blade görünüm yapısı oluşturuldu.
                </li>
                <li class="list-group-item d-flex align-items-center">
                    <span class="badge bg-success me-3">Hazır</span>
                    Bootstrap varlıkları Vite üzerinden derleniyor.
                </li>
                <li class="list-group-item d-flex align-items-center text-muted">
                    <span class="badge bg-secondary me-3">Beklemede</span>
                    MySQL veritabanı yapılandırması bir sonraki adımda yapılacaktır.
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
