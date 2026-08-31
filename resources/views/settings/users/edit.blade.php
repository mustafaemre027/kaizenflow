@extends('layouts.app')

@section('title', 'Kullanıcı Düzenle - KaizenFlow')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('settings.users.index') }}" class="btn btn-light me-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
        </a>
        <div>
            <h1 class="h3 mb-0 text-gray-800">Kullanıcı Düzenle: {{ $user->name }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-9">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Profil Bilgileri</h5>
                    <form action="{{ route('settings.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">E-posta <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                <div class="form-text">
                                    @if($user->must_set_password)
                                        Davet bekleyen kullanıcının e-posta adresi değiştirilirse eski davet geçersiz olur ve yeni adrese davet gönderilir.
                                    @else
                                        E-posta adresi değiştirildiğinde yeni adres tekrar doğrulanmalıdır.
                                    @endif
                                </div>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold">Temel Rol <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                    <option value="" disabled>Seçiniz</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->value }}" {{ old('role', $user->role->value) == $role->value ? 'selected' : '' }}>
                                            {{ $role->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-semibold">
                                    Departman
                                    <span id="department-required-mark" class="text-danger" style="display: none;">*</span>
                                </label>
                                <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                    <option value="">Seçiniz</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">OPEX Uzmanı ve Sistem Yöneticisi için isteğe bağlıdır.</div>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('settings.users.index') }}" class="btn btn-light">İptal</a>
                            @if(auth()->id() !== $user->id)
                                <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                            @else
                                <button type="button" class="btn btn-primary" disabled>Kendi Profilinizi Düzenleyemezsiniz</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Hesap Durumu</h5>
                    
                    <div class="mb-3">
                        <span class="d-block text-muted small fw-bold mb-1">DURUM</span>
                        @if($user->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Pasif</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <span class="d-block text-muted small fw-bold mb-1">KURULUM</span>
                        @if($user->must_set_password)
                            <span class="badge bg-warning text-dark">Davet Bekliyor</span>
                        @else
                            <span class="badge bg-success">Hazır</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <span class="d-block text-muted small fw-bold mb-1">E-POSTA ONAYI</span>
                        @if($user->email_verified_at)
                            <span class="badge bg-success">Doğrulandı</span>
                        @else
                            <span class="badge bg-secondary">Doğrulanmadı</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
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
