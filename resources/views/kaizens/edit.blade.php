@extends('layouts.app')

@section('title', 'Kaizen\'i Düzenle')

@section('content')
<x-page-header 
    title="Kaizen'i Düzenle" 
    subtitle="{{ $kaizen->code }} kodlu taslağı güncelleyin."
/>

<form method="POST" action="{{ route('kaizens.update', $kaizen) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PATCH')

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <x-section-card title="1. Temel Bilgiler" description="Fikrinizin başlığını veya kategorisini güncelleyin.">
                <div class="row g-4">
                    <div class="col-12 col-md-4">
                        <label for="category_id" class="kf-form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select kf-form-control @error('category_id') is-invalid @enderror" required>
                            <option value="">-- Seçiniz --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $kaizen->category_id) == $category->id ? 'selected' : '' }}>
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
                        <input type="text" name="title" id="title" class="form-control kf-form-control @error('title') is-invalid @enderror" value="{{ old('title', $kaizen->title) }}" required maxlength="255" placeholder="Kaizen'inizi özetleyen kısa başlık">
                        <div class="form-text small">Örn: Depo alanındaki etiketleme sürecinin iyileştirilmesi</div>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-section-card>

            <x-section-card title="2. Mevcut Durum" description="Mevcut problemi ve yaşanan sıkıntıları güncelleyin.">
                <div class="mb-3">
                    <label for="current_situation" class="kf-form-label visually-hidden">Mevcut Durum Açıklaması</label>
                    <textarea name="current_situation" id="current_situation" class="form-control kf-form-control @error('current_situation') is-invalid @enderror" rows="4" required maxlength="5000">{{ old('current_situation', $kaizen->current_situation) }}</textarea>
                    @error('current_situation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if($currentSituationAttachments->isNotEmpty())
                    <div class="mb-3 kf-edit-gallery" data-context="current_situation" data-existing-count="{{ $currentSituationAttachments->count() }}">
                        <p class="kf-form-label mb-2">Mevcut Fotoğraflar</p>
                        <div class="row g-2">
                            @foreach($currentSituationAttachments as $index => $attachment)
                            <div class="col-6 col-sm-4 col-md-3 kf-edit-gallery-item-wrapper">
                                <div class="kf-edit-gallery-item rounded overflow-hidden position-relative border" data-attachment-id="{{ $attachment->id }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}" alt="Mevcut durum fotoğrafı {{ $index + 1 }}" class="w-100 h-100 object-fit-cover" style="aspect-ratio: 1/1;">
                                    <div class="kf-edit-gallery-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-dark bg-opacity-75 text-white opacity-0 transition-all">
                                        <span class="small fw-medium mb-2">Kaydedince kaldırılacak</span>
                                        <button type="button" class="btn btn-sm btn-light kf-btn-toggle-remove">Kaldır</button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="p-3 border rounded bg-light" data-evidence-picker data-context="current_situation" data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label for="current_situation_images" class="kf-form-label mb-0">Yeni Fotoğraf Ekle</label>
                        <span class="text-muted small picker-counter kf-dynamic-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }}</span>
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

            <x-section-card title="3. Önerilen Durum" description="İyileştirme sonrasında sürecin nasıl işleyeceğini güncelleyin.">
                <div class="mb-3">
                    <label for="proposed_situation" class="kf-form-label visually-hidden">Önerilen Durum Açıklaması</label>
                    <textarea name="proposed_situation" id="proposed_situation" class="form-control kf-form-control @error('proposed_situation') is-invalid @enderror" rows="4" required maxlength="5000">{{ old('proposed_situation', $kaizen->proposed_situation) }}</textarea>
                    @error('proposed_situation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if($proposedSituationAttachments->isNotEmpty())
                    <div class="mb-3 kf-edit-gallery" data-context="proposed_situation" data-existing-count="{{ $proposedSituationAttachments->count() }}">
                        <p class="kf-form-label mb-2">Mevcut Fotoğraflar</p>
                        <div class="row g-2">
                            @foreach($proposedSituationAttachments as $index => $attachment)
                            <div class="col-6 col-sm-4 col-md-3 kf-edit-gallery-item-wrapper">
                                <div class="kf-edit-gallery-item rounded overflow-hidden position-relative border" data-attachment-id="{{ $attachment->id }}">
                                    <img src="{{ route('kaizens.attachments.show', [$kaizen, $attachment]) }}" alt="Önerilen durum fotoğrafı {{ $index + 1 }}" class="w-100 h-100 object-fit-cover" style="aspect-ratio: 1/1;">
                                    <div class="kf-edit-gallery-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-dark bg-opacity-75 text-white opacity-0 transition-all">
                                        <span class="small fw-medium mb-2">Kaydedince kaldırılacak</span>
                                        <button type="button" class="btn btn-sm btn-light kf-btn-toggle-remove">Kaldır</button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="p-3 border rounded bg-light" data-evidence-picker data-context="proposed_situation" data-max-files="{{ config('kaizen.attachments.max_images_per_context', 8) }}" data-max-kb="{{ config('kaizen.attachments.max_image_kb', 8192) }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label for="proposed_situation_images" class="kf-form-label mb-0">Yeni Fotoğraf Ekle</label>
                        <span class="text-muted small picker-counter kf-dynamic-counter">0 / {{ config('kaizen.attachments.max_images_per_context', 8) }}</span>
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

            <x-section-card title="4. Beklenen Faydalar" description="Opsiyonel — Bu kaizen ile elde etmeyi beklediğiniz ölçülebilir faydaları güncelleyin.">
                @if($benefitTypes->isEmpty())
                    <div class="alert alert-light border text-muted small py-2 mb-0">Sistemde tanımlı aktif veya bağlı fayda türü bulunmuyor.</div>
                @else
                    <div id="benefits-container">
                        @php
                            $oldBenefits = old('benefits');
                            if ($oldBenefits !== null) {
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
                            <div class="border rounded p-3 mb-3 bg-light position-relative" data-benefit-row data-type-id="{{ $type->id }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold text-dark">
                                        {{ $type->name }}
                                        @if($type->unit_label) <span class="text-muted small">({{ $type->unit_label }})</span> @endif
                                        @if(!$type->is_active) <span class="badge bg-secondary ms-1">Pasif</span> @endif
                                    </span>
                                    @if($type->is_active)
                                        <button type="button" class="btn btn-sm btn-outline-danger kf-remove-benefit-row">Kaldır</button>
                                    @else
                                        <span class="text-muted small fst-italic">Tarihsel kayıt</span>
                                    @endif
                                </div>
                                <input type="hidden" name="benefits[{{ $idx }}][benefit_type_id]" value="{{ $type->id }}">
                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <input type="number" step="any" min="0" name="benefits[{{ $idx }}][expected_value]" class="form-control kf-form-control @error('benefits.'.$idx.'.expected_value') is-invalid @enderror" placeholder="Beklenen değer" value="{{ $row['expected_value'] }}" {{ !$type->is_active ? 'readonly' : '' }}>
                                        @error('benefits.'.$idx.'.expected_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <input type="text" name="benefits[{{ $idx }}][expected_note]" class="form-control kf-form-control @error('benefits.'.$idx.'.expected_note') is-invalid @enderror" placeholder="Not (opsiyonel)" maxlength="2000" value="{{ $row['expected_note'] }}" {{ !$type->is_active ? 'readonly' : '' }}>
                                        @error('benefits.'.$idx.'.expected_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('benefits')<div class="text-danger small mt-1 mb-2">{{ $message }}</div>@enderror
                    @error('benefits.*')<div class="text-danger small mt-1 mb-2">{{ $message }}</div>@enderror

                    @php
                        $prefillTypeIds = $prefillRows->pluck('type.id')->filter()->all();
                        $pickableTypes  = $benefitTypes->filter(fn($t) => $t->is_active && !in_array($t->id, $prefillTypeIds, true));
                    @endphp
                    @if($pickableTypes->isNotEmpty())
                        <div class="d-flex align-items-center gap-2 mt-2" id="benefit-add-area">
                            <select id="benefit-type-picker" class="form-select kf-form-control" style="max-width: 280px;" aria-label="Eklenecek fayda türü seçin">
                                <option value="">-- Fayda türü seçin --</option>
                                @foreach($pickableTypes as $type)
                                    <option value="{{ $type->id }}" data-name="{{ $type->name }}" data-unit="{{ $type->unit_label ?? '' }}">
                                        {{ $type->name }}{{ $type->unit_label ? ' ('.$type->unit_label.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" id="benefit-add-btn" class="kf-btn kf-btn-secondary btn-sm flex-shrink-0">+ Ekle</button>
                        </div>
                    @endif
                @endif
            </x-section-card>
            
            <div id="kf-remove-inputs-container"></div>
            
            <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
                <a href="{{ route('kaizens.show', $kaizen) }}" class="kf-btn kf-btn-secondary">İptal</a>
                <button type="submit" class="kf-btn kf-btn-primary px-4">
                    Değişiklikleri Kaydet
                </button>
            </div>
        </div>
        
        <div class="col-12 col-lg-4 d-none d-lg-block">
            <div class="position-sticky" style="top: 2rem;">
                <x-section-card title="Taslak Bilgisi">
                    <ul class="list-unstyled mb-0 text-muted small">
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-1 flex-shrink-0"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span>Henüz onaya sunmadığınız bu taslağı istediğiniz zaman güncelleyebilirsiniz.</span>
                        </li>
                    </ul>
                </x-section-card>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<style>
.kf-edit-gallery-overlay {
    background: rgba(0,0,0,0.6);
}
.kf-edit-gallery-item-wrapper:hover .kf-edit-gallery-overlay {
    opacity: 1 !important;
}
.kf-edit-gallery-item.kf-pending-removal .kf-edit-gallery-overlay {
    opacity: 1 !important;
    background: rgba(220,53,69,0.8);
}
.kf-edit-gallery-item.kf-pending-removal img {
    opacity: 0.5;
}
</style>
<script>
(function () {
    'use strict';
    const container = document.getElementById('benefits-container');
    const picker    = document.getElementById('benefit-type-picker');
    const addBtn    = document.getElementById('benefit-add-btn');

    if (container && picker && addBtn) {
        var usedTypeIds = [];
        container.querySelectorAll('[data-benefit-row]').forEach(function (row) {
            var tid = row.dataset.typeId;
            if (tid) usedTypeIds.push(String(tid));
        });

        function disableOption(typeId, disabled) {
            var opt = picker.querySelector('option[value="' + typeId + '"]');
            if (opt) opt.disabled = disabled;
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

        function addRow(typeId, typeName, typeUnit) {
            var idx = container.querySelectorAll('[data-benefit-row]').length;
            var row = document.createElement('div');
            var unit = typeUnit ? ' <span class="text-muted small">(' + escHtml(typeUnit) + ')</span>' : '';
            row.className = 'border rounded p-3 mb-3 bg-light position-relative';
            row.dataset.benefitRow = '1';
            row.dataset.typeId = typeId;
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

        addBtn.addEventListener('click', function () {
            var opt = picker.options[picker.selectedIndex];
            if (!opt || !opt.value) return;
            addRow(opt.value, opt.dataset.name, opt.dataset.unit);
        });

        container.querySelectorAll('[data-benefit-row]').forEach(function (row) {
            var removeBtn = row.querySelector('.kf-remove-benefit-row');
            if (!removeBtn) return;
            var typeId = row.dataset.typeId;
            removeBtn.addEventListener('click', function () {
                disableOption(typeId, false);
                row.remove();
                reindex();
            });
        });
    }
}());
</script>
@endpush
