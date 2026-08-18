@extends('layouts.app')

@section('title', 'Yeni Kaizen Oluştur')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Yeni Kaizen Taslağı Oluştur</h4>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Lütfen önerdiğiniz iyileştirmeyle ilgili bilgileri detaylı bir şekilde doldurun. Formu kaydederek daha sonra düzenlemeye devam edebilirsiniz.</p>

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('kaizens.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="category_id" class="form-label fw-bold">Kategori</label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">-- Kategori Seçin --</option>
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

                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Başlık</label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required maxlength="255">
                        <div class="form-text">Kaizen'inizi özetleyen kısa ve net bir başlık girin (en az 5 karakter).</div>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="current_situation" class="form-label fw-bold">Mevcut Durum</label>
                        <textarea name="current_situation" id="current_situation" class="form-control @error('current_situation') is-invalid @enderror" rows="4" required maxlength="5000">{{ old('current_situation') }}</textarea>
                        <div class="form-text">Şu anki süreci ve yaşanan problemi detaylı olarak açıklayın.</div>
                        @error('current_situation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="proposed_situation" class="form-label fw-bold">Önerilen Durum</label>
                        <textarea name="proposed_situation" id="proposed_situation" class="form-control @error('proposed_situation') is-invalid @enderror" rows="4" required maxlength="5000">{{ old('proposed_situation') }}</textarea>
                        <div class="form-text">İyileştirme sonrasında sürecin nasıl işleyeceğini açıklayın.</div>
                        @error('proposed_situation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="expected_benefit" class="form-label fw-bold">Beklenen Fayda</label>
                        <textarea name="expected_benefit" id="expected_benefit" class="form-control @error('expected_benefit') is-invalid @enderror" rows="4" required maxlength="5000">{{ old('expected_benefit') }}</textarea>
                        <div class="form-text">Öneriniz uygulandığında elde edilecek zaman, maliyet veya kalite faydalarını belirtin.</div>
                        @error('expected_benefit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            Taslağı Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
