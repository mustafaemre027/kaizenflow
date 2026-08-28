@extends('layouts.app')

@section('title', 'Kaizen\'i Düzenle')

@section('content')
<div class="kf-page-header">
    <span class="kf-page-eyebrow">Kaizen Yönetimi</span>
    <h1 class="kf-page-title">Kaizen'i Düzenle</h1>
    <p class="kf-page-desc">{{ $kaizen->code }} kodlu taslağı güncelleyin.</p>
</div>

<div class="kf-composer-layout">
    <!-- Left Context Rail -->
    <div class="kf-context-rail">
        <h2 class="kf-context-rail-title">Kaizen Güncelle</h2>
        <p class="kf-context-rail-desc">Sürekli iyileştirme fikrinizi mevcut taslak üzerinden geliştirin.</p>

        <div class="kf-rail-steps">
            <div class="kf-rail-step">
                <span class="kf-rail-step-num">01</span>
                <div class="kf-rail-step-content">
                    <h5>Temel Bilgiler</h5>
                    <p>Fikrinizin başlığını veya kategorisini güncelleyin.</p>
                </div>
            </div>

            <div class="kf-rail-step">
                <span class="kf-rail-step-num">02</span>
                <div class="kf-rail-step-content">
                    <h5>Problem ve İyileştirme</h5>
                    <p>Mevcut problemi ve önerilen çözümü daha net ifade edin.</p>
                </div>
            </div>

            <div class="kf-rail-step">
                <span class="kf-rail-step-num">03</span>
                <div class="kf-rail-step-content">
                    <h5>Beklenen Etki</h5>
                    <p>Önerinin sağlayacağı değeri gözden geçirin.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Form Workspace -->
    <div class="kf-form-workspace">
        <form method="POST" action="{{ route('kaizens.update', $kaizen) }}" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PATCH')

            <div class="p-4 p-md-5">
                <div class="kf-form-section">
                    <h2 class="kf-form-section-title">01 &nbsp; Temel Bilgiler</h2>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="category_id" class="kf-form-label">Kategori <span class="badge bg-light text-secondary border ms-2 fw-normal">Zorunlu</span></label>
                            <select name="category_id" id="category_id" class="kf-form-control @error('category_id') is-invalid @enderror" required data-kf-required="true" aria-describedby="category_id_error">
                                <option value="">-- Seçiniz --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $kaizen->category_id) == $category->id ? 'selected' : '' }}>
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
                            <input type="text" name="title" id="title" class="kf-form-control @error('title') is-invalid @enderror" value="{{ old('title', $kaizen->title) }}" required maxlength="255" placeholder="Kaizen'inizi özetleyen kısa başlık" data-kf-required="true" aria-describedby="title_error">
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
                        <textarea name="current_situation" id="current_situation" class="kf-form-control @error('current_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="Şu anki süreci ve yaşanan problemi detaylı olarak açıklayın..." aria-describedby="current_situation_error" data-kf-required="true">{{ old('current_situation', $kaizen->current_situation) }}</textarea>
                        <div id="current_situation_error" class="invalid-feedback kf-client-error">
                            @error('current_situation')
                                {{ $message }}
                            @else
                                Mevcut durum alanı zorunludur.
                            @enderror
                        </div>

                        @if($currentSituationAttachments->isNotEmpty())
                        <div class="mt-3 kf-edit-gallery" data-context="current_situation" data-existing-count="{{ $currentSituationAttachments->count() }}">
                            <p class="kf-form-help mb-2">Mevcut Fotoğraflar</p>
                            <div class="row g-2">
                                @foreach($currentSituationAttachments as $index => $attachment)
                                <div class="col-6 col-md-4 col-lg-3 kf-edit-gallery-item-wrapper">
                                    <div class="kf-edit-gallery-item" data-attachment-id="{{ $attachment->id }}">
                                        <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}" alt="Mevcut durum fotoğrafı {{ $index + 1 }}">
                                        <div class="kf-edit-gallery-overlay">
                                            <span class="kf-edit-gallery-status">Kaydedince kaldırılacak</span>
                                            <button type="button" class="btn btn-sm btn-light kf-btn-toggle-remove" aria-label="Mevcut durum fotoğrafı {{ $index + 1 }}'i kaldır">Kaldır</button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="mt-3 p-3 border rounded bg-light kf-edit-picker-container" data-evidence-picker data-context="current_situation" data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label for="current_situation_images" class="kf-form-label mb-0">Yeni Fotoğraf Ekle</label>
                                <span class="text-muted small picker-counter kf-dynamic-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }} fotoğraf</span>
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
                        <textarea name="proposed_situation" id="proposed_situation" class="kf-form-control @error('proposed_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="İyileştirme sonrasında sürecin nasıl işleyeceğini açıklayın..." aria-describedby="proposed_situation_error" data-kf-required="true">{{ old('proposed_situation', $kaizen->proposed_situation) }}</textarea>
                        <div id="proposed_situation_error" class="invalid-feedback kf-client-error">
                            @error('proposed_situation')
                                {{ $message }}
                            @else
                                Önerilen durum alanı zorunludur.
                            @enderror
                        </div>

                        @if($proposedSituationAttachments->isNotEmpty())
                        <div class="mt-3 kf-edit-gallery" data-context="proposed_situation" data-existing-count="{{ $proposedSituationAttachments->count() }}">
                            <p class="kf-form-help mb-2">Mevcut Fotoğraflar</p>
                            <div class="row g-2">
                                @foreach($proposedSituationAttachments as $index => $attachment)
                                <div class="col-6 col-md-4 col-lg-3 kf-edit-gallery-item-wrapper">
                                    <div class="kf-edit-gallery-item" data-attachment-id="{{ $attachment->id }}">
                                        <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}" alt="Önerilen durum fotoğrafı {{ $index + 1 }}">
                                        <div class="kf-edit-gallery-overlay">
                                            <span class="kf-edit-gallery-status">Kaydedince kaldırılacak</span>
                                            <button type="button" class="btn btn-sm btn-light kf-btn-toggle-remove" aria-label="Önerilen durum fotoğrafı {{ $index + 1 }}'i kaldır">Kaldır</button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="mt-3 p-3 border rounded bg-light kf-edit-picker-container" data-evidence-picker data-context="proposed_situation" data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label for="proposed_situation_images" class="kf-form-label mb-0">Yeni Fotoğraf Ekle</label>
                                <span class="text-muted small picker-counter kf-dynamic-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }} fotoğraf</span>
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
                            Sistemde tanımlı aktif veya bağlı fayda türü bulunmuyor.
                        </div>
                    @else
                        <div id="benefits-container">
                            @php
                                // old() takes priority (validation failure); otherwise prefill from DB
                                $oldBenefits = old('benefits');
                                if ($oldBenefits !== null) {
                                    // Validation failure: restore from old()
                                    $prefillRows = collect($oldBenefits)->map(function ($row) use ($benefitTypes) {
                                        $type = $benefitTypes->firstWhere('id', $row['benefit_type_id'] ?? null);
                                        if (!$type) return null;
                                        return [
                                            'type'           => $type,
                                            'expected_value' => $row['expected_value'] ?? '',
                                            'expected_note'  => $row['expected_note'] ?? '',
                                        ];
                                    })->filter()->values();
                                } else {
                                    // Normal load: prefill from DB
                                    $prefillRows = $kaizen->benefits->map(function ($benefit) {
                                        return [
                                            'type'           => $benefit->benefitType,
                                            'expected_value' => $benefit->expected_value ?? '',
                                            'expected_note'  => $benefit->expected_note ?? '',
                                        ];
                                    })->filter(fn($r) => $r['type'] !== null)->values();
                                }
                            @endphp

                            @foreach($prefillRows as $idx => $row)
                                @php $type = $row['type']; @endphp
                                <div class="kf-benefit-row border rounded p-3 mb-2 bg-light" data-benefit-row data-type-id="{{ $type->id }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-medium text-dark">
                                            {{ e($type->name) }}
                                            @if($type->unit_label)
                                                <span class="text-muted small">({{ e($type->unit_label) }})</span>
                                            @endif
                                            @if(!$type->is_active)
                                                <span class="badge bg-secondary ms-1" title="Bu fayda türü pasif edilmiştir">Pasif</span>
                                            @endif
                                        </span>
                                        @if($type->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-danger kf-remove-benefit-row" aria-label="{{ e($type->name) }} fayda satırını kaldır">Kaldır</button>
                                        @else
                                            {{-- Inactive linked: cannot be removed via UI to preserve historical record --}}
                                            <span class="text-muted small fst-italic">Tarihsel kayıt</span>
                                        @endif
                                    </div>
                                    <input type="hidden" name="benefits[{{ $idx }}][benefit_type_id]" value="{{ $type->id }}">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="kf-form-label visually-hidden" for="benefit_value_{{ $idx }}">Beklenen Değer</label>
                                            <input type="number" step="any" min="0"
                                                id="benefit_value_{{ $idx }}"
                                                name="benefits[{{ $idx }}][expected_value]"
                                                class="kf-form-control @error('benefits.'.$idx.'.expected_value') is-invalid @enderror"
                                                placeholder="Beklenen değer"
                                                value="{{ $row['expected_value'] }}"
                                                {{ !$type->is_active ? 'readonly' : '' }}>
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
                                                value="{{ $row['expected_note'] }}"
                                                {{ !$type->is_active ? 'readonly' : '' }}>
                                            @error('benefits.'.$idx.'.expected_note')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('benefits')
                            <div class="text-danger small mt-1 mb-2">{{ $message }}</div>
                        @enderror
                        @error('benefits.*')
                            <div class="text-danger small mt-1 mb-2">{{ $message }}</div>
                        @enderror

                        {{-- Type picker: only active types not already in prefillRows --}}
                        @php
                            $prefillTypeIds = $prefillRows->pluck('type.id')->filter()->all();
                            $pickableTypes  = $benefitTypes->filter(fn($t) => $t->is_active && !in_array($t->id, $prefillTypeIds, true));
                        @endphp
                        @if($pickableTypes->isNotEmpty())
                            <div class="d-flex align-items-center gap-2 mt-2" id="benefit-add-area">
                                <select id="benefit-type-picker" class="kf-form-control" style="max-width: 280px;" aria-label="Eklenecek fayda türü seçin">
                                    <option value="">-- Fayda türü seçin --</option>
                                    @foreach($pickableTypes as $type)
                                        <option value="{{ $type->id }}"
                                            data-name="{{ e($type->name) }}"
                                            data-unit="{{ e($type->unit_label ?? '') }}">
                                            {{ e($type->name) }}{{ $type->unit_label ? ' ('.$type->unit_label.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" id="benefit-add-btn" class="kf-btn kf-btn-secondary btn-sm">+ Fayda Ekle</button>
                            </div>
                        @endif
                    @endif
                </div>

            </div>

            <div id="kf-remove-inputs-container"></div>

            <div class="kf-form-footer">
                <a href="{{ route('kaizens.show', $kaizen) }}" class="kf-btn kf-btn-secondary">Vazgeç</a>
                <button type="submit" class="kf-btn kf-btn-primary">Değişiklikleri Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const container = document.getElementById('benefits-container');
    const picker    = document.getElementById('benefit-type-picker');
    const addBtn    = document.getElementById('benefit-add-btn');

    if (!container) return;

    // Track which type IDs are already displayed (both active and inactive linked rows)
    var usedTypeIds = [];
    container.querySelectorAll('[data-benefit-row]').forEach(function (row) {
        var tid = row.dataset.typeId;
        if (tid) usedTypeIds.push(String(tid));
    });

    function nextIndex() {
        return container.querySelectorAll('[data-benefit-row]').length;
    }

    function disableOption(typeId, disabled) {
        if (!picker) return;
        var opt = picker.querySelector('option[value="' + typeId + '"]');
        if (opt) opt.disabled = disabled;
    }

    function addRow(typeId, typeName, typeUnit) {
        var idx = nextIndex();
        var row = document.createElement('div');

        row.className          = 'kf-benefit-row border rounded p-3 mb-2 bg-light';
        row.dataset.benefitRow = '1';
        row.dataset.typeId     = typeId;

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
        if (picker) picker.value = '';
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

    // Wire add button
    if (addBtn && picker) {
        addBtn.addEventListener('click', function () {
            var opt = picker.options[picker.selectedIndex];
            if (!opt || !opt.value) return;
            addRow(opt.value, opt.dataset.name, opt.dataset.unit);
        });
    }

    // Wire remove buttons on active server-rendered rows
    container.querySelectorAll('[data-benefit-row]').forEach(function (row) {
        var removeBtn = row.querySelector('.kf-remove-benefit-row');
        if (!removeBtn) return; // inactive rows have no remove button
        var typeId = row.dataset.typeId;
        removeBtn.addEventListener('click', function () {
            disableOption(typeId, false);
            row.remove();
            reindex();
        });
    });
}());
</script>
@endpush
