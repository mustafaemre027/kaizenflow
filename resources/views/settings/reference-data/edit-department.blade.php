@extends('layouts.app')

@section('title', 'Departman Düzenle')

@section('content')
<x-page-header 
    title="Departman Düzenle" 
    subtitle="Yapılandırma / Departmanlar"
>
    <x-slot:actions>
        @if($department->is_active)
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">Aktif</span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill">Pasif</span>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <x-section-card>
            <form action="{{ route('settings.departments.update', $department) }}" method="POST">
                @csrf
                @method('PATCH')
                
                <div class="mb-4">
                    <label for="name" class="kf-form-label mb-1">Departman Adı</label>
                    <input type="text" id="name" name="name" class="form-control kf-form-control @error('name') is-invalid @enderror" value="{{ old('name', $department->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label for="code" class="kf-form-label mb-1">Kod</label>
                    <input type="text" id="code" name="code" class="form-control kf-form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $department->code) }}" required>
                    <div class="form-text small">Sistemde benzersiz teknik tanımlayıcı olarak kullanılır. Örn: LOGISTICS</div>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-5">
                    <label for="description" class="kf-form-label mb-1">Açıklama <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                    <textarea id="description" name="description" class="form-control kf-form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $department->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('settings.reference-data.index') }}" class="kf-btn kf-btn-secondary">Vazgeç</a>
                    <button type="submit" class="kf-btn kf-btn-primary">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </x-section-card>
    </div>
</div>
@endsection
