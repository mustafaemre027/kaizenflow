<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KaizenFlow')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">KaizenFlow</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @guest
                        @if (!request()->routeIs('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Giriş Yap</a>
                            </li>
                        @endif
                    @endguest

                    @auth
                        <li class="nav-item d-flex align-items-center">
                            <span class="nav-link text-white me-2">{{ auth()->user()->name }}</span>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-light btn-sm">Çıkış Yap</button>
                            </form>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-4">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="bg-light text-center py-3 mt-auto">
        <div class="container text-muted">
            <small>&copy; {{ date('Y') }} KaizenFlow. Tüm hakları saklıdır.</small>
        </div>
    </footer>

</body>
</html>
