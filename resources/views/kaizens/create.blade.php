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
        <form method="POST" action="{{ route('kaizens.store') }}">
            @csrf

            <div class="p-4 p-md-5">
                <div class="kf-form-section">
                    <h2 class="kf-form-section-title">01 &nbsp; Temel Bilgiler</h2>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="category_id" class="kf-form-label">Kategori</label>
                            <select name="category_id" id="category_id" class="kf-form-control @error('category_id') is-invalid @enderror" required>
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
                        
                        <div class="col-md-8">
                            <label for="title" class="kf-form-label">Başlık</label>
                            <input type="text" name="title" id="title" class="kf-form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required maxlength="255" placeholder="Kaizen'inizi özetleyen kısa başlık">
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
                        <textarea name="current_situation" id="current_situation" class="kf-form-control @error('current_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="Şu anki süreci ve yaşanan problemi detaylı olarak açıklayın...">{{ old('current_situation') }}</textarea>
                        @error('current_situation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="kf-form-group mb-0">
                        <label for="proposed_situation" class="kf-form-label">Önerilen Durum</label>
                        <textarea name="proposed_situation" id="proposed_situation" class="kf-form-control @error('proposed_situation') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="İyileştirme sonrasında sürecin nasıl işleyeceğini açıklayın...">{{ old('proposed_situation') }}</textarea>
                        @error('proposed_situation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="kf-form-section border-bottom-0 pb-0 mb-0">
                    <h2 class="kf-form-section-title">03 &nbsp; Beklenen Etki</h2>
                    
                    <div class="kf-form-group mb-0">
                        <label for="expected_benefit" class="kf-form-label">Beklenen Fayda</label>
                        <textarea name="expected_benefit" id="expected_benefit" class="kf-form-control @error('expected_benefit') is-invalid @enderror" rows="3" required maxlength="5000" placeholder="Öneriniz uygulandığında elde edilecek zaman, maliyet veya kalite faydalarını belirtin...">{{ old('expected_benefit') }}</textarea>
                        @error('expected_benefit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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
