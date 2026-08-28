@extends('layouts.app')

@section('title', 'Yeni Kaizen Oluştur')

@section('content')
<div class="kf-page-header">
    <span class="kf-page-eyebrow">Kaizen Yönetimi</span>
    <h1 class="kf-page-title">Yeni Kaizen Oluştur</h1>
</div>

<div class="kf-composer-layout">
    <!-- Left Context Rail -->
    <div class="kf-context-rail">
        <h2 class="kf-context-rail-title">Kaizen Oluştur</h2>
        <p class="kf-context-rail-desc">Sürekli iyileştirme fikrinizi yapılandırılmış bir form ile tanımlayın.</p>

        <div class="kf-rail-steps">
            <div class="kf-rail-step">
                <span class="kf-rail-step-num">01</span>
                <div class="kf-rail-step-content">
                    <h5>Temel Bilgiler</h5>
                    <p>Fikrinizi sınıflandırın ve net bir başlık verin.</p>
                </div>
            </div>

            <div class="kf-rail-step">
                <span class="kf-rail-step-num">02</span>
                <div class="kf-rail-step-content">
                    <h5>Problem ve İyileştirme</h5>
                    <p>Mevcut problemi ve önerilen çözümü açıklayın.</p>
                </div>
            </div>

            <div class="kf-rail-step">
                <span class="kf-rail-step-num">03</span>
                <div class="kf-rail-step-content">
                    <h5>Beklenen Etki</h5>
                    <p>Önerinin sağlayacağı değeri tanımlayın.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Form Workspace -->
    <div class="kf-form-workspace">
        <form method="POST" action="{{ route('kaizens.store') }}" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="p-4 p-md-5">
                <div class="kf-form-section">
                    <h2 class="kf-form-section-title">01 &nbsp; Temel Bilgiler</h2>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="category_id" class="kf-form-label">Kategori <span class="badge bg-light text-secondary border ms-2 fw-normal">Zorunlu</span></label>
                            <select name="category_id" id="category_id" class="kf-form-control @error('category_id') is-invalid @enderror" required data-kf-required="true" aria-describedby="category_id_error">
                                <option value="">-- Seçiniz --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="category_id_error" class="invalid-feedback kf-client-error">
                                @error('category_id')
                                    {{ $message }}
                                @else
                                    Kategori seçimi zorunludur.
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label for="title" class="kf-form-label">Başlık <span class="badge bg-light text-secondary border ms-2 fw-normal">Zorunlu</span></label>
                            <input type="text" name="title" id="title" class="kf-form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required maxlength="255" placeholder="Kaizen'inizi özetleyen kısa başlık" data-kf-required="true" aria-describedby="title_error">
                            <span class="kf-form-help">Örn: Depo alanındaki etiketleme sürecinin iyileştirilmesi</span>
                            <div id="title_error" class="invalid-feedback kf-client-error">
                                @error('title')
                                    {{ $message }}
                                @else
                                    Başlık alanı zorunludur.
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kf-form-section">
                    <h2 class="kf-form-section-title">02 &nbsp; Problem ve İyileştirme</h2>

                    <div class="kf-form-group">
                        <label for="current_situation" class="kf-form-label">Mevcut Durum <span class="badge bg-light text-secondary border ms-2 fw-normal">Zorunlu</span></label>
                        <textarea name="current_situation" id="current_situation" class="kf-form-control @error('current_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="Şu anki süreci ve yaşanan problemi detaylı olarak açıklayın..." aria-describedby="current_situation_error" data-kf-required="true">{{ old('current_situation') }}</textarea>
                        <div id="current_situation_error" class="invalid-feedback kf-client-error">
                            @error('current_situation')
                                {{ $message }}
                            @else
                                Mevcut durum alanı zorunludur.
                            @enderror
                        </div>

                        <div class="mt-3 p-3 border rounded bg-light" data-evidence-picker data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label for="current_situation_images" class="kf-form-label mb-0">Mevcut Durum Fotoğrafları</label>
                                <span class="text-muted small picker-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }} fotoğraf</span>
                            </div>

                            <div class="picker-header mb-3">
                                <div class="position-relative d-inline-block">
                                    <input type="file" name="current_situation_images[]" id="current_situation_images" multiple accept="image/jpeg,image/png,image/webp" class="visually-hidden picker-input @error('current_situation_images') is-invalid @enderror @error('current_situation_images.*') is-invalid @enderror" aria-describedby="current_situation_help">
                                    <label for="current_situation_images" class="btn btn-outline-secondary btn-sm mb-0">
                                        Fotoğrafları Seç
                                    </label>
                                </div>
                                <small id="current_situation_help" class="form-text text-muted d-block mt-2">
                                    JPEG, PNG veya WEBP &bull; Dosya başına en fazla {{ round(config('kaizen.attachments.max_image_kb', 8192) / 1024) }} MB
                                </small>
                            </div>

                            <div class="picker-preview-area d-flex flex-wrap gap-2"></div>
                            <div class="picker-error-region text-danger small mt-2 fw-medium" aria-live="polite"></div>

                            @error('current_situation_images')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @error('current_situation_images.*')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="kf-form-group mb-0">
                        <label for="proposed_situation" class="kf-form-label">Önerilen Durum <span class="badge bg-light text-secondary border ms-2 fw-normal">Zorunlu</span></label>
                        <textarea name="proposed_situation" id="proposed_situation" class="kf-form-control @error('proposed_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="İyileştirme sonrasında sürecin nasıl işleyeceğini açıklayın..." aria-describedby="proposed_situation_error" data-kf-required="true">{{ old('proposed_situation') }}</textarea>
                        <div id="proposed_situation_error" class="invalid-feedback kf-client-error">
                            @error('proposed_situation')
                                {{ $message }}
                            @else
                                Önerilen durum alanı zorunludur.
                            @enderror
                        </div>

                        <div class="mt-3 p-3 border rounded bg-light" data-evidence-picker data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label for="proposed_situation_images" class="kf-form-label mb-0">Önerilen Durum Fotoğrafları</label>
                                <span class="text-muted small picker-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }} fotoğraf</span>
                            </div>

                            <div class="picker-header mb-3">
                                <div class="position-relative d-inline-block">
                                    <input type="file" name="proposed_situation_images[]" id="proposed_situation_images" multiple accept="image/jpeg,image/png,image/webp" class="visually-hidden picker-input @error('proposed_situation_images') is-invalid @enderror @error('proposed_situation_images.*') is-invalid @enderror" aria-describedby="proposed_situation_help">
                                    <label for="proposed_situation_images" class="btn btn-outline-secondary btn-sm mb-0">
                                        Fotoğrafları Seç
                                    </label>
                                </div>
                                <small id="proposed_situation_help" class="form-text text-muted d-block mt-2">
                                    JPEG, PNG veya WEBP &bull; Dosya başına en fazla {{ round(config('kaizen.attachments.max_image_kb', 8192) / 1024) }} MB
                                </small>
                            </div>

                            <div class="picker-preview-area d-flex flex-wrap gap-2"></div>
                            <div class="picker-error-region text-danger small mt-2 fw-medium" aria-live="polite"></div>

                            @error('proposed_situation_images')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @error('proposed_situation_images.*')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="kf-form-section border-bottom-0 pb-0 mb-0">
                    <h2 class="kf-form-section-title">03 &nbsp; Beklenen Faydalar</h2>
                    <p class="text-muted small mb-3">Opsiyonel — Bu kaizen ile elde etmeyi beklediğiniz ölçülebilir faydaları belirtin.</p>

                    @if($benefitTypes->isEmpty())
                        <div class="alert alert-light border text-muted small py-2">
                            Sistemde tanımlı aktif fayda türü bulunmuyor.
                        </div>
                    @else
                        <div id="benefits-container">
                            {{-- Populated dynamically via JS; restored on validation failure via old() --}}
                            @php
                                $oldBenefits = old('benefits', []);
                            @endphp
                            @foreach($oldBenefits as $idx => $oldBenefit)
                                @php
                                    $matchedType = $benefitTypes->firstWhere('id', $oldBenefit['benefit_type_id'] ?? null);
                                @endphp
                                @if($matchedType)
                                    <div class="kf-benefit-row border rounded p-3 mb-2 bg-light" data-benefit-row>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-medium text-dark">{{ e($matchedType->name) }}
                                                @if($matchedType->unit_label)
                                                    <span class="text-muted small">({{ e($matchedType->unit_label) }})</span>
                                                @endif
                                            </span>
                                            <button type="button" class="btn btn-sm btn-outline-danger kf-remove-benefit-row" aria-label="{{ e($matchedType->name) }} fayda satırını kaldır">Kaldır</button>
                                        </div>
                                        <input type="hidden" name="benefits[{{ $idx }}][benefit_type_id]" value="{{ $matchedType->id }}">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="kf-form-label visually-hidden" for="benefit_value_{{ $idx }}">Beklenen Değer</label>
                                                <input type="number" step="any" min="0"
                                                    id="benefit_value_{{ $idx }}"
                                                    name="benefits[{{ $idx }}][expected_value]"
                                                    class="kf-form-control @error('benefits.'.$idx.'.expected_value') is-invalid @enderror"
                                                    placeholder="Beklenen değer"
                                                    value="{{ old('benefits.'.$idx.'.expected_value') }}">
                                                @error('benefits.'.$idx.'.expected_value')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-8">
                                                <label class="kf-form-label visually-hidden" for="benefit_note_{{ $idx }}">Not</label>
                                                <input type="text"
                                                    id="benefit_note_{{ $idx }}"
                                                    name="benefits[{{ $idx }}][expected_note]"
                                                    class="kf-form-control @error('benefits.'.$idx.'.expected_note') is-invalid @enderror"
                                                    placeholder="Not (opsiyonel)"
                                                    maxlength="2000"
                                                    value="{{ old('benefits.'.$idx.'.expected_note') }}">
                                                @error('benefits.'.$idx.'.expected_note')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @error('benefits')
                            <div class="text-danger small mt-1 mb-2">{{ $message }}</div>
                        @enderror
                        @error('benefits.*')
                            <div class="text-danger small mt-1 mb-2">{{ $message }}</div>
                        @enderror

                        {{-- Type picker dropdown --}}
                        @php
                            $usedTypeIds = collect(old('benefits', []))->pluck('benefit_type_id')->map(fn($v) => (int)$v)->filter()->all();
                            $availableTypes = $benefitTypes->reject(fn($t) => in_array($t->id, $usedTypeIds, true));
                        @endphp
                        <div class="d-flex align-items-center gap-2 mt-2" id="benefit-add-area">
                            <select id="benefit-type-picker" class="kf-form-control" style="max-width: 280px;" aria-label="Eklenecek fayda türü seçin">
                                <option value="">-- Fayda türü seçin --</option>
                                @foreach($benefitTypes as $type)
                                    <option value="{{ $type->id }}"
                                        data-name="{{ e($type->name) }}"
                                        data-unit="{{ e($type->unit_label ?? '') }}"
                                        {{ in_array($type->id, $usedTypeIds, true) ? 'disabled' : '' }}>
                                        {{ e($type->name) }}{{ $type->unit_label ? ' ('.$type->unit_label.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" id="benefit-add-btn" class="kf-btn kf-btn-secondary btn-sm">+ Fayda Ekle</button>
                        </div>
                    @endif
                </div>

            </div>

            <div class="kf-form-footer">
                <span class="text-muted small">Taslak olarak kaydedilir.</span>
                <button type="submit" class="kf-btn kf-btn-primary">
                    Taslağı Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const container  = document.getElementById('benefits-container');
    const picker     = document.getElementById('benefit-type-picker');
    const addBtn     = document.getElementById('benefit-add-btn');

    if (!container || !picker || !addBtn) return;

    function nextIndex() {
        return container.querySelectorAll('[data-benefit-row]').length;
    }

    function disableOption(typeId, disabled) {
        const opt = picker.querySelector('option[value="' + typeId + '"]');
        if (opt) opt.disabled = disabled;
    }

    function addRow(typeId, typeName, typeUnit) {
        const idx  = nextIndex();
        const row  = document.createElement('div');
        const unit = typeUnit ? ' (' + typeUnit + ')' : '';

        row.className        = 'kf-benefit-row border rounded p-3 mb-2 bg-light';
        row.dataset.benefitRow = '1';
        row.dataset.typeId   = typeId;

        row.innerHTML =
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
                '<span class="fw-medium text-dark">' + escHtml(typeName) + (typeUnit ? ' <span class="text-muted small">(' + escHtml(typeUnit) + ')</span>' : '') + '</span>' +
                '<button type="button" class="btn btn-sm btn-outline-danger kf-remove-benefit-row" aria-label="' + escHtml(typeName) + ' fayda satırını kaldır">Kaldır</button>' +
            '</div>' +
            '<input type="hidden" name="benefits[' + idx + '][benefit_type_id]" value="' + escHtml(typeId) + '">' +
            '<div class="row g-2">' +
                '<div class="col-md-4">' +
                    '<label class="kf-form-label visually-hidden" for="benefit_value_' + idx + '">Beklenen Değer</label>' +
                    '<input type="number" step="any" min="0" id="benefit_value_' + idx + '" name="benefits[' + idx + '][expected_value]" class="kf-form-control" placeholder="Beklenen değer">' +
                '</div>' +
                '<div class="col-md-8">' +
                    '<label class="kf-form-label visually-hidden" for="benefit_note_' + idx + '">Not</label>' +
                    '<input type="text" id="benefit_note_' + idx + '" name="benefits[' + idx + '][expected_note]" class="kf-form-control" placeholder="Not (opsiyonel)" maxlength="2000">' +
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
            row.querySelectorAll('[id]').forEach(function (el) {
                el.id = el.id.replace(/_\d+$/, '_' + i);
            });
        });
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    addBtn.addEventListener('click', function () {
        const opt = picker.options[picker.selectedIndex];
        if (!opt || !opt.value) return;
        addRow(opt.value, opt.dataset.name, opt.dataset.unit);
    });

    // Wire up remove buttons for server-side restored rows (old() loop)
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
