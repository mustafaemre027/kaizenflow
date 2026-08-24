@extends('layouts.app')

@section('title', 'Yeni Onay Yapılandırması - KaizenFlow')

@section('content')
<div class="kf-page-header">
    <span class="kf-page-eyebrow">YÖNETİM > ONAY YAPILANDIRMALARI</span>
    <h1 class="kf-page-title">Yeni Yapılandırma Oluştur</h1>
</div>

@if ($errors->any())
    <div class="kf-alert kf-alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; border-color: #f5c2c7; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
        <h2 style="font-size: 1rem; margin-bottom: 0.5rem; margin-top: 0;">Lütfen hataları düzeltin:</h2>
        <ul style="margin-bottom: 0; padding-left: 1.5rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('settings.approval-configurations.store') }}" method="POST">
    @csrf

    <div class="kf-form-section">
        <h2 class="kf-form-section-title">Temel Bilgiler</h2>
        
        <div class="kf-form-group">
            <label for="code" class="kf-form-label">Kod <span class="text-danger">*</span></label>
            <input type="text" name="code" id="code" class="kf-form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required aria-required="true" @error('code') aria-invalid="true" aria-describedby="code_error" @enderror>
            @error('code')
                <div id="code_error" class="invalid-feedback d-block">{{ $message }}</div>
            @else
                <small class="kf-form-help">Örn: WF_IT_PURCHASE</small>
            @enderror
        </div>

        <div class="kf-form-group">
            <label for="name" class="kf-form-label">Ad <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="kf-form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required aria-required="true" @error('name') aria-invalid="true" aria-describedby="name_error" @enderror>
            @error('name')
                <div id="name_error" class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="kf-form-group">
            <label for="description" class="kf-form-label">Açıklama</label>
            <textarea name="description" id="description" rows="3" class="kf-form-control @error('description') is-invalid @enderror" @error('description') aria-invalid="true" aria-describedby="desc_error" @enderror>{{ old('description') }}</textarea>
            @error('description')
                <div id="desc_error" class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @include('settings.approval-configurations.partials.stage-editor', ['workflow' => null])

    <div class="mt-4 pt-4 border-top d-flex justify-content-end gap-2">
        <a href="{{ route('settings.approval-configurations.index') }}" class="kf-btn kf-btn-secondary">İptal</a>
        <button type="submit" class="kf-btn kf-btn-primary">Taslağı Kaydet</button>
    </div>
</form>
@endsection
