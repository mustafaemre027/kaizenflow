@extends('layouts.app')

@section('title', 'Kullanıcı Yönetimi - KaizenFlow')

@section('content')
<x-page-header 
    title="Kullanıcı Yönetimi" 
    subtitle="Sistem kullanıcılarını yönetin ve yeni davetler gönderin."
>
    <x-slot:actions>
        @can('create', App\Models\User::class)
        <a href="{{ route('settings.users.create') }}" class="kf-btn kf-btn-primary d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16">
              <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
              <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
            </svg>
            Yeni Kullanıcı
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-flash-messages />

<!-- Filters -->
<x-section-card class="mb-4">
    <form action="{{ route('settings.users.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="q" class="kf-form-label mb-1">Arama</label>
            <input type="text" class="form-control kf-form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Ad veya e-posta">
        </div>
        <div class="col-md-2">
            <label for="role" class="kf-form-label mb-1">Rol</label>
            <select class="form-select kf-form-control" id="role" name="role">
                <option value="">Tümü</option>
                @foreach(\App\Enums\UserRole::cases() as $role)
                    <option value="{{ $role->value }}" {{ request('role') == $role->value ? 'selected' : '' }}>
                        {{ $role->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="department_id" class="kf-form-label mb-1">Departman</label>
            <select class="form-select kf-form-control" id="department_id" name="department_id">
                <option value="">Tümü</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="status" class="kf-form-label mb-1">Durum</label>
            <select class="form-select kf-form-control" id="status" name="status">
                <option value="">Tümü</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Pasif</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="setup" class="kf-form-label mb-1">Kurulum</label>
            <select class="form-select kf-form-control" id="setup" name="setup">
                <option value="">Tümü</option>
                <option value="pending" {{ request('setup') === 'pending' ? 'selected' : '' }}>Davet Bekliyor</option>
                <option value="ready" {{ request('setup') === 'ready' ? 'selected' : '' }}>Hazır</option>
            </select>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('settings.users.index') }}" class="kf-btn kf-btn-secondary">Temizle</a>
            <button type="submit" class="kf-btn kf-btn-primary">Filtrele</button>
        </div>
    </form>
</x-section-card>

<!-- Table -->
@if($users->isEmpty())
    <x-empty-state 
        title="Kullanıcı bulunamadı" 
        description="Arama ve filtrelerle eşleşen veya sisteme kayıtlı bir kullanıcı bulunmuyor."
    >
        <x-slot:icon>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </x-slot:icon>
    </x-empty-state>
@else
    <div class="kf-table-shell">
        <div class="table-responsive">
            <table class="kf-table">
                <thead>
                    <tr>
                        <th scope="col">Kullanıcı</th>
                        <th scope="col" class="d-none d-md-table-cell">Departman</th>
                        <th scope="col">Rol</th>
                        <th scope="col" class="d-none d-lg-table-cell">Hesap Durumu</th>
                        <th scope="col">Kurulum</th>
                        <th scope="col" class="d-none d-xl-table-cell">Oluşturulma</th>
                        <th scope="col" class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ $user->name }}</div>
                                <div class="text-muted small mt-1">{{ $user->email }}</div>
                            </td>
                            <td class="d-none d-md-table-cell text-secondary">
                                {{ $user->department ? $user->department->name : '-' }}
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                @if($user->is_active)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Aktif</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Pasif</span>
                                @endif
                            </td>
                            <td>
                                @if($user->must_set_password)
                                    <div class="d-flex flex-column">
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1" style="width: fit-content;">Davet Bekliyor</span>
                                        @if($user->invitation_sent_at)
                                            <small class="text-muted mt-1" style="font-size: 0.75rem;">Son gönderim: {{ $user->invitation_sent_at->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">Hazır</span>
                                @endif
                            </td>
                            <td class="d-none d-xl-table-cell text-muted small">
                                {{ $user->created_at->format('d.m.Y') }}
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="kf-btn kf-btn-secondary" style="padding: 0.25rem 0.5rem;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                            <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('settings.users.edit', $user) }}">Düzenle</a>
                                        </li>
                                        @if(auth()->user()->can('viewCapabilities', $user))
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.users.capabilities', $user) }}">Yetkileri Yönet</a>
                                            </li>
                                        @endif
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
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($users->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endif

@endsection
