<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KaizenFlow')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ !request()->routeIs('login') ? 'kf-app-body' : '' }}">

    @if (!request()->routeIs('login'))
    <div class="kf-app-wrapper">
        <header class="kf-app-header">
            <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-4">
                    <a class="kf-app-brand" href="{{ url('/') }}">KaizenFlow</a>
                    @auth
                        <nav class="d-none d-md-flex gap-2 ms-4">
                            <a href="{{ url('/') }}" class="kf-app-nav-link {{ request()->is('/') ? 'active' : '' }}">Ana Sayfa</a>
                            <a href="{{ route('kaizens.index') }}" class="kf-app-nav-link {{ request()->routeIs('kaizens.index', 'kaizens.show') ? 'active' : '' }}">Kaizenler</a>
                            <a href="{{ route('kaizens.create') }}" class="kf-app-nav-link {{ request()->routeIs('kaizens.create') ? 'active' : '' }}">Yeni Kaizen</a>
                            @if(auth()->user()->approvalGroupMemberships()->where('is_active', true)->whereHas('group', function ($query) { $query->where('is_active', true); })->exists())
                                <a href="{{ route('approvals.index') }}" class="kf-app-nav-link {{ request()->routeIs('approvals.index') ? 'active' : '' }}">Onay Bekleyenler</a>
                            @endif
                            @if(auth()->user()->role === \App\Enums\UserRole::ADMIN)
                                <a href="{{ route('settings.reference-data.index') }}" class="kf-app-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">Yönetim</a>
                            @endif
                        </nav>
                    @endauth
                </div>

                @auth
                    <div class="dropdown">
                        <button class="kf-user-dropdown-btn" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-weight: 700; font-size: 0.8rem;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end kf-user-dropdown-menu" aria-labelledby="userMenuDropdown">
                            <li><h6 class="dropdown-header text-muted">{{ auth()->user()->email }}</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item kf-user-dropdown-item text-danger fw-medium">Çıkış Yap</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth

                @guest
                    @if (!request()->routeIs('login'))
                        <a href="{{ route('login') }}" class="kf-btn kf-btn-header-guest kf-btn-sm">Giriş Yap</a>
                    @endif
                @endguest
            </div>
        </header>

        <main class="flex-grow-1">
            <div class="kf-page-container">
                @if (session('success'))
                    <div class="kf-alert kf-alert-success" role="alert">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="kf-alert kf-alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; border-color: #f5c2c7; padding: 1rem; border-radius: 4px; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="kf-app-footer">
            <div class="container">
                <span>&copy; {{ date('Y') }} KaizenFlow. Tüm hakları saklıdır.</span>
            </div>
        </footer>
    </div>
    @else
        <div class="kf-auth-shell">
            @yield('content')
        </div>
    @endif

</body>
</html>
