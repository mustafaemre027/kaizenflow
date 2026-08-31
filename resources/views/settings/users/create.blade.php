@extends('layouts.app')

@section('title', 'Yeni Kullanıcı Ekle - KaizenFlow')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('settings.users.index') }}" class="btn btn-light me-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
        </a>
        <div>
            <h1 class="h3 mb-0 text-gray-800">Yeni Kullanıcı Ekle</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('settings.users.store') }}" method="POST">
                        @csrf

                        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle flex-shrink-0 me-2" viewBox="0 0 16 16">
                              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                              <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                            </svg>
                            <div>
                                Kullanıcı parolasını güvenli davet bağlantısı üzerinden kendisi belirleyecektir.
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required maxlength="255">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">E-posta <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required maxlength="255">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold">Temel Rol <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="" disabled selected>Seçiniz</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->value }}" {{ old('role') == $role->value ? 'selected' : '' }}>
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
                                <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                                    <option value="">Seçiniz</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Sürekli İyileştirme Uzmanı ve Sistem Yöneticisi için isteğe bağlıdır.</div>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('settings.users.index') }}" class="btn btn-light">İptal</a>
                            <button type="submit" class="btn btn-primary">Oluştur ve Davet Et</button>
                        </div>
                    </form>
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
