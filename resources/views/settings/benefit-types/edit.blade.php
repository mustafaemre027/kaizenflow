@extends('layouts.app')

@section('title', 'Fayda Türü Düzenle: ' . $benefitType->name)

@section('content')
<x-page-header 
    title="Fayda Türü Düzenle" 
    subtitle="Yapılandırma / Fayda Türleri"
>
    <x-slot:actions>
        @if($benefitType->is_active)
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">Aktif</span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill">Pasif</span>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <x-section-card>
            <form action="{{ route('settings.benefit-types.update', $benefitType) }}" method="POST">
                @csrf
                @method('PATCH')
                
                <div class="mb-4">
                    <label class="kf-form-label mb-1">Fayda Türü Adı <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control kf-form-control @error('name') is-invalid @enderror" value="{{ old('name', $benefitType->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label class="kf-form-label mb-1">Birim <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                    <input type="text" name="unit_label" class="form-control kf-form-control @error('unit_label') is-invalid @enderror" value="{{ old('unit_label', $benefitType->unit_label) }}">
                    <div class="form-text mt-1 small">Birim isteğe bağlıdır. Örn. saat, TL, adet, %, kWh.</div>
                    @error('unit_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('settings.reference-data.index', ['tab' => 'benefit-types']) }}" class="kf-btn kf-btn-secondary">İptal</a>
                    <button type="submit" class="kf-btn kf-btn-primary">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </x-section-card>
    </div>
    
    <div class="col-12 col-lg-4">
        <div class="d-flex flex-column gap-4 position-sticky" style="top: 2rem;">
            <!-- DURUM DEĞİŞTİRME PANELİ -->
            <x-section-card title="Durum Yönetimi">
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
                        <input type="hidden" name="is_active" value="0">
                        <button type="submit" class="kf-btn kf-btn-secondary w-100 border-warning text-warning" onclick="return confirm('Bu fayda türünü pasife almak istediğinize emin misiniz?')">
                            Pasife Al
                        </button>
                    @else
                        <input type="hidden" name="is_active" value="1">
                        <button type="submit" class="kf-btn kf-btn-secondary w-100 border-success text-success">
                            Aktifleştir
                        </button>
                    @endif
                </form>
            </x-section-card>

            <!-- BİLGİ PANELİ -->
            <x-section-card title="Sistem Bilgileri" :no-padding="true">
                <ul class="list-group list-group-flush rounded-bottom">
                    <li class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <span class="small text-muted fw-semibold text-uppercase">Kullanım Sayısı</span>
                        <span class="fw-medium text-dark">{{ $benefitType->kaizen_benefits_count }} Kaizen</span>
                    </li>
                    <li class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center bg-light">
                        <span class="small text-muted fw-semibold text-uppercase">Oluşturulma</span>
                        <span class="fw-medium text-dark">{{ $benefitType->created_at->format('d.m.Y H:i') }}</span>
                    </li>
                    <li class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <span class="small text-muted fw-semibold text-uppercase">Son Güncelleme</span>
                        <span class="fw-medium text-dark">{{ $benefitType->updated_at->format('d.m.Y H:i') }}</span>
                    </li>
                </ul>
            </x-section-card>
        </div>
    </div>
</div>
@endsection
