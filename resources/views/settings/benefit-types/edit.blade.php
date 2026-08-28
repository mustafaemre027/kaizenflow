@extends('layouts.app')

@section('title', 'Fayda Türü Düzenle: ' . $benefitType->name)

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
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="kf-page-title mb-0">Fayda Türü Düzenle</h1>
        @if($benefitType->is_active)
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fs-6">Aktif</span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 fs-6">Pasif</span>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="kf-panel mb-4">
            <div class="kf-panel-body p-4 p-md-5">
                <form action="{{ route('settings.benefit-types.update', $benefitType) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="mb-4">
                        <label class="form-label fw-medium text-dark">Fayda Türü Adı <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $benefitType->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium text-dark">Birim <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                        <input type="text" name="unit_label" class="form-control form-control-lg @error('unit_label') is-invalid @enderror" value="{{ old('unit_label', $benefitType->unit_label) }}">
                        <div class="form-text mt-2">Birim isteğe bağlıdır. Örn. saat, TL, adet, %, kWh.</div>
                        @error('unit_label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-5">
                    
                    <div class="d-flex justify-content-end gap-3">
                        <a href="{{ route('settings.reference-data.index', ['tab' => 'benefit-types']) }}" class="btn btn-light px-4">İptal</a>
                        <button type="submit" class="btn btn-primary px-5">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- DURUM DEĞİŞTİRME PANELİ -->
        <div class="kf-panel mb-4">
            <div class="kf-panel-header bg-light border-bottom">
                <h3 class="fs-6 fw-bold mb-0">Durum Yönetimi</h3>
            </div>
            <div class="kf-panel-body p-4">
                <p class="text-muted small mb-4">
                    @if($benefitType->is_active)
                        Bu fayda türü şu anda <strong>aktif</strong>. Yeni Kaizen gerçekleşmelerinde kullanılabilir durumda.
                        <br><br>
                        Pasife alırsanız mevcut kayıtlarda görünmeye devam eder ancak yeni Kaizen'lerde listelenmez.
                    @else
                        Bu fayda türü şu anda <strong>pasif</strong>. Yeni Kaizen'lerde seçilemez durumda.
                    @endif
                </p>
                
                <form action="{{ route('settings.benefit-types.status', $benefitType) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    @if($benefitType->is_active)
                        <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Bu fayda türünü pasife almak istediğinize emin misiniz?')">
                            Pasife Al
                        </button>
                    @else
                        <button type="submit" class="btn btn-outline-success w-100">
                            Aktifleştir
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <!-- BİLGİ PANELİ -->
        <div class="kf-panel">
            <div class="kf-panel-header bg-light border-bottom">
                <h3 class="fs-6 fw-bold mb-0">Sistem Bilgileri</h3>
            </div>
            <div class="kf-panel-body p-0">
                <ul class="list-group list-group-flush text-sm">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Kullanım Sayısı</span>
                        <span class="fw-medium">{{ $benefitType->kaizen_benefits_count }} Kaizen</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Oluşturulma</span>
                        <span class="fw-medium">{{ $benefitType->created_at->format('d.m.Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Son Güncelleme</span>
                        <span class="fw-medium">{{ $benefitType->updated_at->format('d.m.Y H:i') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
