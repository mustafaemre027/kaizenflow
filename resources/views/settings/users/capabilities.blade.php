@extends('layouts.app')

@section('title', 'Yetki Yönetimi - ' . $targetUser->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('settings.users.index') }}" class="text-decoration-none">Kullanıcı Yönetimi</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.users.edit', $targetUser) }}" class="text-decoration-none">{{ $targetUser->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Yetkiler</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0 text-gray-800">Yetki Yönetimi</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: User Summary & Actions -->
        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold text-gray-800">Kullanıcı Özeti</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Ad Soyad</small>
                        <div class="fw-medium">{{ $targetUser->name }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Rol</small>
                        <div><span class="badge bg-secondary">{{ $targetUser->role->label() }}</span></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Profil Departmanı</small>
                        <div class="fw-medium">{{ $targetUser->department ? $targetUser->department->name : '-' }}</div>
                    </div>
                    <div>
                        <small class="text-muted d-block mb-1">Hesap Durumu</small>
                        <div>
                            @if($targetUser->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Pasif</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Capabilities -->
        <div class="col-xl-8">
            
            @if(!$targetUser->is_active)
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img" aria-label="Warning:">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    <div>
                        Pasif kullanıcıya yeni yetki atanamaz. Sadece mevcut aktif yetkiler geri alınabilir.
                    </div>
                </div>
            @endif
            
            @if(auth()->id() === $targetUser->id)
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle-fill flex-shrink-0 me-2" viewBox="0 0 16 16">
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                    </svg>
                    <div>
                        Kendi yetkilerinizi yalnızca görüntüleyebilirsiniz, değiştiremezsiniz.
                    </div>
                </div>
            @endif

            @php
                $canManage = auth()->user()->can('manageCapabilities', $targetUser);
            @endphp

            <!-- System Capabilities -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold text-gray-800">Sistem Yetkileri</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3">Yetki</th>
                                    <th class="px-4 py-3 text-center">Durum</th>
                                    <th class="px-4 py-3 text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($systemCapabilities as $capability)
                                    @php
                                        $grant = $systemGrants->get($capability->value);
                                        $isActive = $grant && $grant->is_active;
                                        
                                        $actorCanGrant = auth()->user()->systemCapabilityGrants()->where('capability', $capability->value)->where('is_active', true)->exists()
                                                         && auth()->user()->systemCapabilityGrants()->where('capability', \App\Enums\UserCapability::AUTHORIZATION_MANAGE->value)->where('is_active', true)->exists();
                                        
                                        $actorCanRevoke = auth()->user()->systemCapabilityGrants()->where('capability', \App\Enums\UserCapability::AUTHORIZATION_MANAGE->value)->where('is_active', true)->exists();
                                        
                                        $showGrantButton = $canManage && $targetUser->is_active && !$isActive && $actorCanGrant;
                                        $showRevokeButton = $canManage && $isActive && $actorCanRevoke;
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="fw-medium text-gray-800">{{ $capability->label() }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($isActive)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Pasif</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            @if($showGrantButton)
                                                <form action="{{ route('settings.users.capabilities.system', $targetUser) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="capability" value="{{ $capability->value }}">
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Yetki Ver</button>
                                                </form>
                                            @elseif($showRevokeButton)
                                                <form action="{{ route('settings.users.capabilities.system', $targetUser) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="capability" value="{{ $capability->value }}">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu yetkiyi almak istediğinize emin misiniz?');">Yetkiyi Al</button>
                                                </form>
                                            @elseif($canManage && $targetUser->is_active && !$isActive && !$actorCanGrant)
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Bu yetkiyi devredebilmek için aynı yetkiye sahip olmanız gerekir.">Yetki Ver</button>
                                            @else
                                                <span class="text-muted text-sm">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Department Capabilities -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-semibold text-gray-800">Departman Yetkileri</h5>
                </div>
                <div class="card-body p-0">
                    
                    @php
                        $actorHasDeptAdmin = auth()->user()->systemCapabilityGrants()->where('capability', \App\Enums\UserCapability::AUTHORIZATION_MANAGE->value)->where('is_active', true)->exists()
                                             && auth()->user()->systemCapabilityGrants()->where('capability', \App\Enums\UserCapability::ORGANIZATION_VIEW->value)->where('is_active', true)->exists()
                                             && auth()->user()->systemCapabilityGrants()->where('capability', \App\Enums\UserCapability::ORGANIZATION_MANAGE->value)->where('is_active', true)->exists();
                    @endphp
                    
                    @if($canManage && $targetUser->is_active && $actorHasDeptAdmin)
                        <div class="p-4 border-bottom bg-light">
                            <h6 class="fw-semibold text-gray-800 mb-3">Yeni Departman Yetkisi Ata</h6>
                            <form action="{{ route('settings.users.capabilities.department', $targetUser) }}" method="POST" class="row g-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="1">
                                
                                <div class="col-md-5">
                                    <select name="department_id" class="form-select form-select-sm" required>
                                        <option value="" disabled selected>Departman Seçin</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <select name="capability" class="form-select form-select-sm" required>
                                        <option value="" disabled selected>Yetki Seçin</option>
                                        @foreach($departmentCapabilities as $capability)
                                            <option value="{{ $capability->value }}">{{ $capability->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Ata</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3">Departman</th>
                                    <th class="px-4 py-3">Yetki</th>
                                    <th class="px-4 py-3 text-center">Durum</th>
                                    <th class="px-4 py-3 text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departmentGrants as $grant)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="fw-medium text-gray-800">{{ $grant->department->name }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-gray-800">{{ $grant->capability->label() }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($grant->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Pasif</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            @if($canManage && $grant->is_active && $actorHasDeptAdmin)
                                                <form action="{{ route('settings.users.capabilities.department', $targetUser) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="department_id" value="{{ $grant->department_id }}">
                                                    <input type="hidden" name="capability" value="{{ $grant->capability->value }}">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu yetkiyi almak istediğinize emin misiniz?');">Yetkiyi Al</button>
                                                </form>
                                            @else
                                                <span class="text-muted text-sm">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Atanmış departman yetkisi bulunmuyor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
