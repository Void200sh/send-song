{{-- ─── LAYOUT ADMIN — MERIDIAN / STISLA SHELL ─── --}}
{{-- Template: public/assets/css/meridian + public/assets/css/style.css (compiled Stisla components) --}}
{{-- Struktur: .app-shell > (.sidebar--app | .app-shell__main > .navbar + .content) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()->theme ?? 'light' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Skanida Songs SMK</title>

    {{-- Inline guard: baca tema tersimpan dari localStorage SEBELUM render pertama (anti flash) --}}
    <script>
        (function () {
            var key = 'stisla-theme';
            var t;
            try { t = localStorage.getItem(key); } catch (e) {}
            if (!t) { t = 'light'; }
            document.documentElement.dataset.theme = t;
        })();
    </script>

    {{-- Font — sama kayak landing page: Plus Jakarta Sans (utama) + Reenie Beanie (dekoratif) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=reenie-beanie:400|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />

    {{-- CSS template Meridian / Stisla — sudah dicompile di public/assets --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    {{-- Override font bawaan template (Inter) pake font brand --}}
    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue',
                'Noto Sans', 'Liberation Sans', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
                'Segoe UI Symbol', 'Noto Color Emoji';
        }

        .font-reenie {
            font-family: 'Reenie Beanie', cursive;
        }

        .page__title.font-reenie {
            font-size: 36px;
            font-weight: 400;
        }

        /* Hanya HEADER yang pakai font latin — sisanya font biasa (Plus Jakarta Sans) */
        .card__title {
            font-family: 'Reenie Beanie', cursive;
            font-weight: 400;
            font-size: 24px;
        }

        .stat__value {
            font-family: 'Reenie Beanie', cursive;
            font-weight: 400;
            font-size: 44px;
        }

        /* Light mode: teks font latin dibuat hitam pekat biar jelas terbaca */
        [data-theme="light"] .card__title,
        [data-theme="light"] .stat__value {
            color: #171717;
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="app-shell" data-stisla-app-shell data-stisla-app-shell-auto-collapse="true">

        {{-- ─── SIDEBAR ─── --}}
        <aside class="sidebar sidebar--app" id="adminSidebar">
            {{-- Brand / logo --}}
            <div class="sidebar__header">
                <a href="{{ route('admin.dashboard') }}" class="sidebar__brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467 1.803 1.803 0 01.99 3.467 2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467m9.894-1.1l-6.87-2.867a1.5 1.5 0 01-.927-1.41V7.24a1.5 1.5 0 011.5-1.5h.75a1.5 1.5 0 011.5 1.5v.691l6.87 2.867a.75.75 0 01.427.902l-.75 2.201z" />
                    </svg>
                    <span class="font-reenie text-[24px] leading-[100%]">Skanida Songs</span>
                </a>
            </div>

            {{-- Navigasi --}}
            <div class="sidebar__content">
                <div class="sidebar__menu">
                    <div class="sidebar__group">
                        <p class="sidebar__group-title">Menu</p>
                        <nav class="sidebar__list">
                            <a href="{{ route('admin.dashboard') }}"
                                class="sidebar__button"
                                @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="7" height="9" rx="1.5" />
                                    <rect x="14" y="3" width="7" height="5" rx="1.5" />
                                    <rect x="14" y="12" width="7" height="9" rx="1.5" />
                                    <rect x="3" y="16" width="7" height="5" rx="1.5" />
                                </svg>
                                Dashboard
                            </a>

                            <a href="{{ route('admin.messages') }}"
                                class="sidebar__button"
                                @if (request()->routeIs('admin.messages')) aria-current="page" @endif>
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="M3.5 7l8.5 6 8.5-6" />
                                </svg>
                                Pesan Masuk
                            </a>

                            <a href="{{ url('/') }}" target="_blank"
                                class="sidebar__button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18" />
                                </svg>
                                Lihat Website
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            {{-- Footer sidebar: copyright --}}
            <div class="sidebar__footer">
                <p class="copyright text-muted-foreground text-sm">SMK Negeri 1 &copy; {{ date('Y') }}</p>
            </div>
        </aside>

        {{-- ─── AREA UTAMA ─── --}}
        <div class="app-shell__main">

            {{-- Topbar --}}
            <header class="navbar">
                <button class="navbar__toggle" data-stisla-app-shell-toggle type="button" aria-controls="adminSidebar"
                    aria-label="Toggle sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <a href="{{ route('admin.dashboard') }}" class="navbar__brand">
                    <span class="font-reenie text-[22px] leading-[100%]">Skanida Songs</span>
               
                </a>

                <div class="navbar__menu">
                    <nav class="navbar__nav">
                        {{-- Toggle tema (gelap/terang) --}}
                        <button class="navbar__button" data-theme-toggle type="button" aria-label="Toggle theme">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 3a9 9 0 000 18c-4 0-6-6-3-9s5-9 3-9z" />
                            </svg>
                        </button>

                        {{-- Avatar user yang lagi login --}}
                        <div class="avatar avatar--sm ms-2" title="{{ auth()->user()->name }}">
                            <span class="avatar__fallback">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        </div>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}" class="ms-2">
                            @csrf
                            <button type="submit" class="navbar__button" title="Keluar" aria-label="Keluar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M15 12H3m0 0l4-4m-4 4l4 4" />
                                    <path d="M15 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3" />
                                </svg>
                            </button>
                        </form>
                    </nav>
                </div>
            </header>

            {{-- ─── KONTEN ─── --}}
            <main class="content">
                <div class="content__container">
                    @yield('content')
                </div>
            </main>

        </div>
    </div>

    {{-- ─── SCRIPT TEMPLATE ─── --}}
    <script src="{{ asset('assets/js/app-shell.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    @stack('scripts')
</body>

</html>