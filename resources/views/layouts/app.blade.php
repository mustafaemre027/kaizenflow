<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KaizenFlow')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    @if (!request()->routeIs('login', 'password.request', 'password.reset', 'verification.notice'))
    <div class="kf-app">
        
        <!-- Desktop Sidebar -->
        @auth
        <aside class="kf-sidebar d-none d-lg-flex">
            <a class="kf-sidebar-brand" href="{{ url('/') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                KaizenFlow
            </a>
            
            <nav class="kf-sidebar-nav" aria-label="Primary Navigation">
                @include('layouts.partials.navigation')
            </nav>
            
            <div class="kf-sidebar-user">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; font-weight: 600; font-size: 14px; background-color: var(--kf-primary);">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                        <div class="fw-semibold text-truncate" style="font-size: 14px; color: var(--kf-text);">{{ auth()->user()->name }}</div>
                        <div class="text-truncate" style="font-size: 12px; color: var(--kf-text-muted);">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="kf-btn kf-btn-ghost w-100 justify-content-start" style="color: var(--kf-danger);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        Çıkış Yap
                    </button>
                </form>
            </div>
        </aside>
        @endauth
        
        <!-- Mobile Offcanvas Menu -->
        @auth
        <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title d-flex align-items-center gap-2 fw-bold" id="mobileMenuLabel">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--kf-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    KaizenFlow
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
                <nav class="kf-sidebar-nav" aria-label="Mobile Navigation">
                    @include('layouts.partials.navigation')
                </nav>
                
                <div class="kf-sidebar-user mt-auto">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px; font-weight: 600; background-color: var(--kf-primary);">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                            <div class="fw-semibold text-truncate" style="font-size: 14px; color: var(--kf-text);">{{ auth()->user()->name }}</div>
                            <div class="text-truncate" style="font-size: 12px; color: var(--kf-text-muted);">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="kf-btn kf-btn-ghost w-100 justify-content-center" style="color: var(--kf-danger); border: 1px solid var(--kf-border-light);">Çıkış Yap</button>
                    </form>
                </div>
            </div>
        </div>
        @endauth

        <div class="kf-main">
            <!-- Mobile Topbar -->
            <header class="kf-topbar d-lg-none">
                <a class="text-decoration-none fw-bold" href="{{ url('/') }}" style="font-size: 1.1rem; color: var(--kf-text); letter-spacing: -0.02em;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--kf-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    KaizenFlow
                </a>
                
                @auth
                <button class="btn btn-link text-dark p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="Menüyü aç">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                @endauth
                
                @guest
                <a href="{{ route('login') }}" class="kf-btn kf-btn-primary">Giriş</a>
                @endguest
            </header>
            
            <!-- Desktop Topbar Spacer if needed (currently omitted to keep content flush if no breadcrumbs) -->

            <main class="kf-content">
                <x-flash-messages />
                @yield('content')
            </main>
        </div>
    </div>
    @else
        <!-- Auth View Renders Its Own Shell -->
        @yield('content')
    @endif

@stack('styles')
@stack('scripts')
</body>
</html>
