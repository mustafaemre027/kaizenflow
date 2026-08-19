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
        <form method="POST" action="{{ route('kaizens.update', $kaizen) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="p-4 p-md-5">
                <div class="kf-form-section">
                    <h2 class="kf-form-section-title">01 &nbsp; Temel Bilgiler</h2>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="category_id" class="kf-form-label">Kategori</label>
                            <select name="category_id" id="category_id" class="kf-form-control @error('category_id') is-invalid @enderror" required>
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

                        <div class="col-md-8">
                            <label for="title" class="kf-form-label">Başlık</label>
                            <input type="text" name="title" id="title" class="kf-form-control @error('title') is-invalid @enderror" value="{{ old('title', $kaizen->title) }}" required maxlength="255" placeholder="Kaizen'inizi özetleyen kısa başlık">
                            <span class="kf-form-help">Örn: Depo alanındaki etiketleme sürecinin iyileştirilmesi</span>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="kf-form-section">
                    <h2 class="kf-form-section-title">02 &nbsp; Problem ve İyileştirme</h2>

                    <div class="kf-form-group">
                        <label for="current_situation" class="kf-form-label">Mevcut Durum</label>
                        <textarea name="current_situation" id="current_situation" class="kf-form-control @error('current_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="Şu anki süreci ve yaşanan problemi detaylı olarak açıklayın...">{{ old('current_situation', $kaizen->current_situation) }}</textarea>
                        @error('current_situation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

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
                        <label for="proposed_situation" class="kf-form-label">Önerilen Durum</label>
                        <textarea name="proposed_situation" id="proposed_situation" class="kf-form-control @error('proposed_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="İyileştirme sonrasında sürecin nasıl işleyeceğini açıklayın...">{{ old('proposed_situation', $kaizen->proposed_situation) }}</textarea>
                        @error('proposed_situation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

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
                    <h2 class="kf-form-section-title">03 &nbsp; Beklenen Etki</h2>

                    <div class="kf-form-group mb-0">
                        <label for="expected_benefit" class="kf-form-label">Beklenen Fayda</label>
                        <textarea name="expected_benefit" id="expected_benefit" class="kf-form-control @error('expected_benefit') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="Öneriniz uygulandığında elde edilecek zaman, maliyet veya kalite faydalarını belirtin...">{{ old('expected_benefit', $kaizen->expected_benefit) }}</textarea>
                        @error('expected_benefit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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
