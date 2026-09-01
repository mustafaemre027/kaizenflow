<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KaizenFlow')</title>
    <!-- Basic Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    @if (!request()->routeIs('login', 'password.request', 'password.reset', 'verification.notice'))
    <div class="kf-app-wrapper">
        <header class="kf-navbar">
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center">
                    
                    <!-- Brand & Desktop Nav -->
                    <div class="d-flex align-items-center gap-4">
                        <a class="kf-navbar-brand text-decoration-none" href="{{ url('/') }}">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                            KaizenFlow
                        </a>
                        
                        @auth
                            <nav class="d-none d-lg-flex gap-2 ms-4" aria-label="Primary Navigation">
                                <a href="{{ url('/') }}" class="kf-nav-link text-decoration-none {{ request()->is('/') ? 'active' : '' }}" {{ request()->is('/') ? 'aria-current=page' : '' }}>Çalışma Alanım</a>
                                
                                @if($navContext['canViewDashboard'] ?? false)
                                    <a href="{{ route('dashboard.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" {{ request()->routeIs('dashboard.index') ? 'aria-current=page' : '' }}>Dashboard</a>
                                @endif
                                
                                <a href="{{ route('kaizens.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('kaizens.index', 'kaizens.show', 'kaizens.create', 'kaizens.edit') ? 'active' : '' }}">Kaizenler</a>
                                
                                <a href="{{ route('implementation.work-queue.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('implementation.work-queue.index') ? 'active' : '' }}">İşlerim</a>
                                
                                @if($navContext['canViewApprovals'] ?? false)
                                    <a href="{{ route('approvals.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('approvals.index') ? 'active' : '' }}">Onaylar</a>
                                @endif
                                
                                @if($navContext['canViewHistory'] ?? false)
                                    <a href="{{ route('history.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('history.index') ? 'active' : '' }}">Geçmiş</a>
                                @endif
                                
                                @if(($navContext['canViewSettings'] ?? false) || ($navContext['canViewUsers'] ?? false))
                                    <a href="{{ route('settings.reference-data.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('settings.*') ? 'active' : '' }}">Yönetim</a>
                                @endif
                            </nav>
                        @endauth
                    </div>

                    <!-- User Menu & Mobile Toggle -->
                    <div class="d-flex align-items-center gap-3">
                        @auth
                            <div class="dropdown d-none d-lg-block">
                                <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-weight: 600; font-size: 0.875rem; background-color: var(--kf-primary);">
                                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                    <span class="text-dark fw-medium">{{ auth()->user()->name }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userMenuDropdown">
                                    <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('logout') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger fw-medium">Çıkış Yap</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Mobile Hamburger -->
                            <button class="btn d-lg-none p-1 text-dark" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="Menüyü aç">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="3" y1="12" x2="21" y2="12"></line>
                                    <line x1="3" y1="6" x2="21" y2="6"></line>
                                    <line x1="3" y1="18" x2="21" y2="18"></line>
                                </svg>
                            </button>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Offcanvas Menu -->
        @auth
        <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title" id="mobileMenuLabel">KaizenFlow</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-3 mb-2 p-2 bg-light rounded">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px; font-weight: 600; background-color: var(--kf-primary);">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-bold">{{ auth()->user()->name }}</div>
                        <div class="text-muted small">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                
                <nav class="d-flex flex-column gap-1" aria-label="Mobile Navigation">
                    <a href="{{ url('/') }}" class="kf-nav-link text-decoration-none {{ request()->is('/') ? 'active' : '' }}">Çalışma Alanım</a>
                    
                    @if($navContext['canViewDashboard'] ?? false)
                        <a href="{{ route('dashboard.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">Dashboard</a>
                    @endif
                    
                    <a href="{{ route('kaizens.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('kaizens.*') ? 'active' : '' }}">Kaizenler</a>
                    <a href="{{ route('implementation.work-queue.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('implementation.work-queue.index') ? 'active' : '' }}">İşlerim</a>
                    
                    @if($navContext['canViewApprovals'] ?? false)
                        <a href="{{ route('approvals.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('approvals.index') ? 'active' : '' }}">Onaylar</a>
                    @endif
                    
                    @if($navContext['canViewHistory'] ?? false)
                        <a href="{{ route('history.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('history.index') ? 'active' : '' }}">Geçmiş</a>
                    @endif
                    
                    @if(($navContext['canViewSettings'] ?? false) || ($navContext['canViewUsers'] ?? false))
                        <a href="{{ route('settings.reference-data.index') }}" class="kf-nav-link text-decoration-none {{ request()->routeIs('settings.*') ? 'active' : '' }}">Yönetim</a>
                    @endif
                </nav>

                <div class="mt-auto border-top pt-3">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100 fw-medium">Çıkış Yap</button>
                    </form>
                </div>
            </div>
        </div>
        @endauth

        <main class="kf-main-content">
            <div class="container-fluid px-4" style="max-width: 1440px; margin: 0 auto;">
                
                <x-flash-messages />

                @yield('content')
            </div>
        </main>

        <footer class="py-4 mt-auto border-top text-center text-muted" style="background-color: var(--kf-surface);">
            <div class="container-fluid px-4">
                <small>&copy; {{ date('Y') }} KaizenFlow. Tüm hakları saklıdır.</small>
            </div>
        </footer>
    </div>
    @else
        <!-- Auth Shell -->
        <div class="kf-auth-layout">
            <div class="kf-auth-card">
                <div class="text-center mb-4">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--kf-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    <h2 class="mt-3 fw-bold" style="letter-spacing: -0.02em;">KaizenFlow</h2>
                </div>
                
                <x-flash-messages />
                
                <div class="card shadow-sm border-0" style="border-radius: var(--kf-radius-lg);">
                    <div class="card-body p-4 p-sm-5">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    @endif

@stack('styles')
@stack('scripts')
</body>
</html>
