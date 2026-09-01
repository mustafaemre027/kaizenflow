@extends('layouts.app')

@section('title', 'Yeni Kaizen Oluştur')

@section('content')
<x-page-header 
    title="Yeni Kaizen Oluştur" 
    subtitle="Sürekli iyileştirme fikrinizi yapılandırılmış bir form ile tanımlayın."
/>

<form method="POST" action="{{ route('kaizens.store') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <x-section-card title="1. Temel Bilgiler" description="Fikrinizi sınıflandırın ve net bir başlık verin.">
                <div class="row g-4">
                    <div class="col-12 col-md-4">
                        <label for="category_id" class="kf-form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select kf-form-control @error('category_id') is-invalid @enderror" required>
                            <option value="">-- Seçiniz --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-8">
                        <label for="title" class="kf-form-label">Başlık <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control kf-form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required maxlength="255" placeholder="Kaizen'inizi özetleyen kısa başlık">
                        <div class="form-text small">Örn: Depo alanındaki etiketleme sürecinin iyileştirilmesi</div>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-section-card>

            <x-section-card title="2. Mevcut Durum" description="Mevcut problemi detaylı olarak açıklayın ve varsa fotoğraflarla destekleyin.">
                <div class="mb-3">
                    <label for="current_situation" class="kf-form-label visually-hidden">Mevcut Durum Açıklaması</label>
                    <textarea name="current_situation" id="current_situation" class="form-control kf-form-control @error('current_situation') is-invalid @enderror" rows="4" required maxlength="5000" placeholder="Şu anki süreci ve yaşanan problemi detaylı olarak açıklayın...">{{ old('current_situation') }}</textarea>
                    @error('current_situation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="p-3 border rounded bg-light" data-evidence-picker data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label for="current_situation_images" class="kf-form-label mb-0">Mevcut Durum Fotoğrafları (Opsiyonel)</label>
                        <span class="text-muted small picker-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }}</span>
                    </div>
                    <div class="picker-header mb-3">
                        <div class="position-relative d-inline-block">
                            <input type="file" name="current_situation_images[]" id="current_situation_images" multiple accept="image/jpeg,image/png,image/webp" class="visually-hidden picker-input @error('current_situation_images') is-invalid @enderror @error('current_situation_images.*') is-invalid @enderror">
                            <label for="current_situation_images" class="btn btn-outline-secondary btn-sm mb-0">Fotoğrafları Seç</label>
                        </div>
                        <small class="text-muted d-block mt-2">JPEG, PNG, WEBP &bull; Max {{ round(config('kaizen.attachments.max_image_kb', 8192) / 1024) }} MB</small>
                    </div>
                    <div class="picker-preview-area d-flex flex-wrap gap-2"></div>
                    <div class="picker-error-region text-danger small mt-2 fw-medium" aria-live="polite"></div>
                    @error('current_situation_images')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('current_situation_images.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </x-section-card>

            <x-section-card title="3. Önerilen Durum" description="İyileştirme sonrasında sürecin nasıl işleyeceğini açıklayın.">
                <div class="mb-3">
                    <label for="proposed_situation" class="kf-form-label visually-hidden">Önerilen Durum Açıklaması</label>
                    <textarea name="proposed_situation" id="proposed_situation" class="form-control kf-form-control @error('proposed_situation') is-invalid @enderror" rows="4" required maxlength="5000" placeholder="İyileştirme sonrasında sürecin nasıl işleyeceğini açıklayın...">{{ old('proposed_situation') }}</textarea>
                    @error('proposed_situation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="p-3 border rounded bg-light" data-evidence-picker data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label for="proposed_situation_images" class="kf-form-label mb-0">Önerilen Durum Fotoğrafları (Opsiyonel)</label>
                        <span class="text-muted small picker-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }}</span>
                    </div>
                    <div class="picker-header mb-3">
                        <div class="position-relative d-inline-block">
                            <input type="file" name="proposed_situation_images[]" id="proposed_situation_images" multiple accept="image/jpeg,image/png,image/webp" class="visually-hidden picker-input @error('proposed_situation_images') is-invalid @enderror @error('proposed_situation_images.*') is-invalid @enderror">
                            <label for="proposed_situation_images" class="btn btn-outline-secondary btn-sm mb-0">Fotoğrafları Seç</label>
                        </div>
                        <small class="text-muted d-block mt-2">JPEG, PNG, WEBP &bull; Max {{ round(config('kaizen.attachments.max_image_kb', 8192) / 1024) }} MB</small>
                    </div>
                    <div class="picker-preview-area d-flex flex-wrap gap-2"></div>
                    <div class="picker-error-region text-danger small mt-2 fw-medium" aria-live="polite"></div>
                    @error('proposed_situation_images')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('proposed_situation_images.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </x-section-card>

            <x-section-card title="4. Beklenen Faydalar" description="Opsiyonel — Bu kaizen ile elde etmeyi beklediğiniz ölçülebilir faydaları belirtin.">
                @if($benefitTypes->isEmpty())
                    <div class="alert alert-light border text-muted small py-2 mb-0">Sistemde tanımlı aktif fayda türü bulunmuyor.</div>
                @else
                    <div id="benefits-container">
                        @php $oldBenefits = old('benefits', []); @endphp
                        @foreach($oldBenefits as $idx => $oldBenefit)
                            @php $matchedType = $benefitTypes->firstWhere('id', $oldBenefit['benefit_type_id'] ?? null); @endphp
                            @if($matchedType)
                                <div class="border rounded p-3 mb-3 bg-light position-relative" data-benefit-row>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold text-dark">{{ $matchedType->name }}
                                            @if($matchedType->unit_label) <span class="text-muted small">({{ $matchedType->unit_label }})</span> @endif
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-danger kf-remove-benefit-row">Kaldır</button>
                                    </div>
                                    <input type="hidden" name="benefits[{{ $idx }}][benefit_type_id]" value="{{ $matchedType->id }}">
                                    <div class="row g-2">
                                        <div class="col-12 col-md-4">
                                            <input type="number" step="any" min="0" name="benefits[{{ $idx }}][expected_value]" class="form-control kf-form-control @error('benefits.'.$idx.'.expected_value') is-invalid @enderror" placeholder="Beklenen değer" value="{{ old('benefits.'.$idx.'.expected_value') }}">
                                            @error('benefits.'.$idx.'.expected_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12 col-md-8">
                                            <input type="text" name="benefits[{{ $idx }}][expected_note]" class="form-control kf-form-control @error('benefits.'.$idx.'.expected_note') is-invalid @enderror" placeholder="Not (opsiyonel)" maxlength="2000" value="{{ old('benefits.'.$idx.'.expected_note') }}">
                                            @error('benefits.'.$idx.'.expected_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    @error('benefits')<div class="text-danger small mt-1 mb-2">{{ $message }}</div>@enderror
                    @error('benefits.*')<div class="text-danger small mt-1 mb-2">{{ $message }}</div>@enderror

                    @php
                        $usedTypeIds = collect(old('benefits', []))->pluck('benefit_type_id')->map(fn($v) => (int)$v)->filter()->all();
                    @endphp
                    <div class="d-flex align-items-center gap-2 mt-2" id="benefit-add-area">
                        <select id="benefit-type-picker" class="form-select kf-form-control" style="max-width: 280px;" aria-label="Eklenecek fayda türü seçin">
                            <option value="">-- Fayda türü seçin --</option>
                            @foreach($benefitTypes as $type)
                                <option value="{{ $type->id }}" data-name="{{ $type->name }}" data-unit="{{ $type->unit_label ?? '' }}" {{ in_array($type->id, $usedTypeIds, true) ? 'disabled' : '' }}>
                                    {{ $type->name }}{{ $type->unit_label ? ' ('.$type->unit_label.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" id="benefit-add-btn" class="kf-btn kf-btn-secondary btn-sm flex-shrink-0">+ Ekle</button>
                    </div>
                @endif
            </x-section-card>
            
            <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
                <a href="{{ route('kaizens.index') }}" class="kf-btn kf-btn-secondary">İptal</a>
                <button type="submit" class="kf-btn kf-btn-primary px-4">
                    Taslağı Kaydet
                </button>
            </div>
        </div>
        
        <div class="col-12 col-lg-4 d-none d-lg-block">
            <div class="position-sticky" style="top: 2rem;">
                <x-section-card title="Kayıt Bilgisi">
                    <ul class="list-unstyled mb-0 text-muted small">
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-1 flex-shrink-0"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span>Taslak olarak kaydedilen Kaizenler sadece sizin tarafınızdan görüntülenebilir ve düzenlenebilir.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-1 flex-shrink-0"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span>Değerlendirmeye gönderme (Submit) işlemini kaydettikten sonra yapabilirsiniz.</span>
                        </li>
                    </ul>
                </x-section-card>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    const container  = document.getElementById('benefits-container');
    const picker     = document.getElementById('benefit-type-picker');
    const addBtn     = document.getElementById('benefit-add-btn');

    if (!container || !picker || !addBtn) return;

    function nextIndex() { return container.querySelectorAll('[data-benefit-row]').length; }
    function disableOption(typeId, disabled) {
        const opt = picker.querySelector('option[value="' + typeId + '"]');
        if (opt) opt.disabled = disabled;
    }
    function addRow(typeId, typeName, typeUnit) {
        const idx = nextIndex();
        const row = document.createElement('div');
        const unit = typeUnit ? ' <span class="text-muted small">(' + escHtml(typeUnit) + ')</span>' : '';
        row.className = 'border rounded p-3 mb-3 bg-light position-relative';
        row.dataset.benefitRow = '1';
        row.innerHTML =
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
                '<span class="fw-semibold text-dark">' + escHtml(typeName) + unit + '</span>' +
                '<button type="button" class="btn btn-sm btn-outline-danger kf-remove-benefit-row">Kaldır</button>' +
            '</div>' +
            '<input type="hidden" name="benefits[' + idx + '][benefit_type_id]" value="' + escHtml(typeId) + '">' +
            '<div class="row g-2">' +
                '<div class="col-12 col-md-4">' +
                    '<input type="number" step="any" min="0" name="benefits[' + idx + '][expected_value]" class="form-control kf-form-control" placeholder="Beklenen değer">' +
                '</div>' +
                '<div class="col-12 col-md-8">' +
                    '<input type="text" name="benefits[' + idx + '][expected_note]" class="form-control kf-form-control" placeholder="Not (opsiyonel)" maxlength="2000">' +
                '</div>' +
            '</div>';
        row.querySelector('.kf-remove-benefit-row').addEventListener('click', function () {
            disableOption(typeId, false);
            row.remove();
            reindex();
        });
        container.appendChild(row);
        disableOption(typeId, true);
        picker.value = '';
    }
    function reindex() {
        container.querySelectorAll('[data-benefit-row]').forEach(function (row, i) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/benefits\[\d+\]/, 'benefits[' + i + ']');
            });
        });
    }
    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    addBtn.addEventListener('click', function () {
        const opt = picker.options[picker.selectedIndex];
        if (!opt || !opt.value) return;
        addRow(opt.value, opt.dataset.name, opt.dataset.unit);
    });
    container.querySelectorAll('[data-benefit-row]').forEach(function (row) {
        var hiddenInput = row.querySelector('input[type="hidden"]');
        if (!hiddenInput) return;
        var typeId = hiddenInput.value;
        row.querySelector('.kf-remove-benefit-row').addEventListener('click', function () {
            disableOption(typeId, false);
            row.remove();
            reindex();
        });
    });
}());
</script>
@endpush
