@extends('layouts.app')

@section('title', 'Yeni Fayda Türü Ekle')

@section('content')
<div class="kf-page-header">
    <div class="d-flex align-items-center gap-2 mb-2">
        <a href="{{ route('settings.reference-data.index', ['tab' => 'benefit-types']) }}" class="text-muted text-decoration-none kf-hover-primary">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
            </svg>
        </a>
        <span class="kf-page-eyebrow mb-0">YAPILANDIRMA / FAYDA TÜRLERİ</span>
    </div>
    <h1 class="kf-page-title">Yeni Fayda Türü</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="kf-panel">
            <div class="kf-panel-body p-4 p-md-5">
                <form action="{{ route('settings.benefit-types.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label fw-medium text-dark">Fayda Türü Adı <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium text-dark">Birim <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                        <input type="text" name="unit_label" class="form-control form-control-lg @error('unit_label') is-invalid @enderror" value="{{ old('unit_label') }}">
                        <div class="form-text mt-2">Birim isteğe bağlıdır. Örn. saat, TL, adet, %, kWh.</div>
                        @error('unit_label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-5">
                    
                    <div class="d-flex justify-content-end gap-3">
                        <a href="{{ route('settings.reference-data.index', ['tab' => 'benefit-types']) }}" class="btn btn-light px-4">İptal</a>
                        <button type="submit" class="btn btn-primary px-5">Fayda Türü Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
