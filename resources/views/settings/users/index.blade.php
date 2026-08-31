@extends('layouts.app')

@section('title', 'Kullanıcı Yönetimi - KaizenFlow')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Kullanıcı Yönetimi</h1>
            <p class="text-muted mb-0">Sistem kullanıcılarını yönetin ve yeni davetler gönderin.</p>
        </div>
        <div>
            @can('create', App\Models\User::class)
            <a href="{{ route('settings.users.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16">
                  <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                  <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
                </svg>
                Yeni Kullanıcı
            </a>
            @endcan
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('settings.users.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="q" class="form-label text-sm text-gray-600">Arama</label>
                    <input type="text" class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Ad veya e-posta">
                </div>
                <div class="col-md-2">
                    <label for="role" class="form-label text-sm text-gray-600">Rol</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">Tümü</option>
                        @foreach(\App\Enums\UserRole::cases() as $role)
                            <option value="{{ $role->value }}" {{ request('role') == $role->value ? 'selected' : '' }}>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="department_id" class="form-label text-sm text-gray-600">Departman</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Tümü</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label text-sm text-gray-600">Durum</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tümü</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="setup" class="form-label text-sm text-gray-600">Kurulum</label>
                    <select class="form-select" id="setup" name="setup">
                        <option value="">Tümü</option>
                        <option value="pending" {{ request('setup') === 'pending' ? 'selected' : '' }}>Davet Bekliyor</option>
                        <option value="ready" {{ request('setup') === 'ready' ? 'selected' : '' }}>Hazır</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('settings.users.index') }}" class="btn btn-light">Temizle</a>
                    <button type="submit" class="btn btn-secondary">Filtrele</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Kullanıcı</th>
                            <th class="px-4 py-3">Departman</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3">Hesap Durumu</th>
                            <th class="px-4 py-3">Kurulum</th>
                            <th class="px-4 py-3">Oluşturulma</th>
                            <th class="px-4 py-3 text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <div class="text-muted text-sm">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ $user->department ? $user->department->name : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge bg-secondary">
                                        {{ $user->role->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($user->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Pasif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($user->must_set_password)
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 w-auto" style="width: fit-content;">Davet Bekliyor</span>
                                            @if($user->invitation_sent_at)
                                                <small class="text-muted mt-1" style="font-size: 0.75rem;">Son gönderim: {{ $user->invitation_sent_at->diffForHumans() }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Hazır</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-muted text-sm">
                                    {{ $user->created_at->format('d.m.Y') }}
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                                <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                            </svg>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.users.edit', $user) }}">Düzenle</a>
                                            </li>
                                            
                                            @if(auth()->id() !== $user->id)
                                                <li><hr class="dropdown-divider"></li>
                                                @if($user->must_set_password && $user->is_active)
                                                    <li>
                                                        <form action="{{ route('settings.users.invitation', $user) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">Daveti Yeniden Gönder</button>
                                                        </form>
                                                    </li>
                                                @endif
                                                
                                                <li>
                                                    <form action="{{ route('settings.users.status', $user) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                                        @if($user->is_active)
                                                            <button type="submit" class="dropdown-item text-danger">Pasife Al</button>
                                                        @else
                                                            <button type="submit" class="dropdown-item text-success">Aktifleştir</button>
                                                        @endif
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    Arama kriterlerinize uygun kullanıcı bulunamadı.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($users->hasPages())
                <div class="card-footer bg-white border-top-0 px-4 py-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
