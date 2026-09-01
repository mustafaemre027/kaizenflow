@extends('layouts.app')

@section('title', 'Kullanıcı Düzenle - KaizenFlow')

@section('content')
<x-page-header 
    title="Kullanıcı Düzenle: {{ $user->name }}" 
    subtitle="Kullanıcının profil ve sistem bilgilerini yönetin."
>
    <x-slot:actions>
        @if(auth()->user()->can('viewCapabilities', $user))
            <a href="{{ route('settings.users.capabilities', $user) }}" class="kf-btn kf-btn-secondary d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-lock" viewBox="0 0 16 16">
                    <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                    <path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99a1.5 1.5 0 1 1 2-1.415z"/>
                </svg>
                Yetkileri Görüntüle
            </a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-xl-8 col-lg-9">
        <x-section-card title="Profil Bilgileri">
            <form action="{{ route('settings.users.update', $user) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="name" class="kf-form-label mb-1">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control kf-form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="kf-form-label mb-1">E-posta <span class="text-danger">*</span></label>
                        <input type="email" class="form-control kf-form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                        <div class="form-text small">
                            @if($user->must_set_password)
                                Davet bekleyen kullanıcının e-posta adresi değiştirilirse eski davet geçersiz olur ve yeni adrese davet gönderilir.
                            @else
                                E-posta adresi değiştirildiğinde yeni adres tekrar doğrulanmalıdır.
                            @endif
                        </div>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="kf-form-label mb-1">Temel Rol <span class="text-danger">*</span></label>
                        <select class="form-select kf-form-control @error('role') is-invalid @enderror" id="role" name="role" required {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                            <option value="" disabled>Seçiniz</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ old('role', $user->role->value) == $role->value ? 'selected' : '' }}>
                                    {{ $role->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="department_id" class="kf-form-label mb-1">
                            Departman
                            <span id="department-required-mark" class="text-danger" style="display: none;">*</span>
                        </label>
                        <select class="form-select kf-form-control @error('department_id') is-invalid @enderror" id="department_id" name="department_id" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                            <option value="">Seçiniz</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">Sürekli İyileştirme Uzmanı ve Sistem Yöneticisi için isteğe bağlıdır.</div>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('settings.users.index') }}" class="kf-btn kf-btn-secondary">İptal</a>
                    @if(auth()->id() !== $user->id)
                        <button type="submit" class="kf-btn kf-btn-primary">Değişiklikleri Kaydet</button>
                    @else
                        <button type="button" class="kf-btn kf-btn-primary opacity-50" disabled>Kendi Profilinizi Düzenleyemezsiniz</button>
                    @endif
                </div>
            </form>
        </x-section-card>
    </div>

    <div class="col-xl-4 col-lg-3">
        <x-section-card title="Hesap Durumu" :no-padding="true">
            <ul class="list-group list-group-flush rounded-bottom">
                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1">
                    <span class="small text-muted fw-semibold text-uppercase">Durum</span>
                    <div>
                        @if($user->is_active)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Aktif</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Pasif</span>
                        @endif
                    </div>
                </li>

                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1 bg-light">
                    <span class="small text-muted fw-semibold text-uppercase">Kurulum</span>
                    <div>
                        @if($user->must_set_password)
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">Davet Bekliyor</span>
                        @else
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">Hazır</span>
                        @endif
                    </div>
                </li>

                <li class="list-group-item px-4 py-3 d-flex flex-column gap-1">
                    <span class="small text-muted fw-semibold text-uppercase">E-posta Onayı</span>
                    <div>
                        @if($user->email_verified_at)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Doğrulandı</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">Doğrulanmadı</span>
                        @endif
                    </div>
                </li>
            </ul>
        </x-section-card>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const deptSelect = document.getElementById('department_id');
        const deptRequiredMark = document.getElementById('department-required-mark');
        
        if (!roleSelect || !deptSelect) return;

        const EMPLOYEE_ROLE = '{{ \App\Enums\UserRole::EMPLOYEE->value }}';
        const MANAGER_ROLE = '{{ \App\Enums\UserRole::MANAGER->value }}';

        function updateDepartmentRequirement() {
            const role = roleSelect.value;
            const isRequired = role === EMPLOYEE_ROLE || role === MANAGER_ROLE;
            
            if (isRequired) {
                deptRequiredMark.style.display = 'inline';
                deptSelect.required = true;
            } else {
                deptRequiredMark.style.display = 'none';
                deptSelect.required = false;
            }
        }

        roleSelect.addEventListener('change', updateDepartmentRequirement);
        updateDepartmentRequirement(); // Initial check
    });
</script>
@endpush
@endsection
