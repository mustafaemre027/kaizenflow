@extends('layouts.app')

@section('title', 'Departman Düzenle')

@section('content')
<div class="kf-page-header d-flex justify-content-between align-items-center">
    <div>
        <span class="kf-page-eyebrow">Yapılandırma / Departmanlar</span>
        <h1 class="kf-page-title">Departman Düzenle</h1>
    </div>
    <div>
        @if($department->is_active)
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">Aktif</span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill">Pasif</span>
        @endif
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="kf-panel">
            <div class="kf-panel-body p-4 p-md-5">
                <form action="{{ route('settings.departments.update', $department) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="mb-4">
                        <label for="name" class="form-label fw-medium text-dark">Departman Adı</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $department->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="code" class="form-label fw-medium text-dark">Kod</label>
                        <input type="text" id="code" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $department->code) }}" required>
                        <div class="form-text">Sistemde benzersiz teknik tanımlayıcı olarak kullanılır. Örn: LOGISTICS</div>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label for="description" class="form-label fw-medium text-dark">Açıklama <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $department->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-3">
                        <a href="{{ route('settings.reference-data.index') }}" class="btn btn-light border">Vazgeç</a>
                        <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
