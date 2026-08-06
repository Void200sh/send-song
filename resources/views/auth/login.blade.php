{{-- ─── HALAMAN LOGIN ADMIN — SKANIDASONG ─── --}}
{{-- Desain nyambung sama landing page: putih + tinta #171717 + wordmark Reenie Beanie + rounded-xl --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Login — SkanidaSong Admin</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=reenie-beanie:400|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .auth-reveal {
            animation: auth-reveal .55s cubic-bezier(.16, 1, .3, 1) both;
        }

        @keyframes auth-reveal {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .auth-reveal { animation: none; }
        }
    </style>
</head>

<body class="bg-white text-gray-950 font-sans antialiased">
    <div class="min-h-[100dvh] grid lg:grid-cols-[1.05fr_1fr]">

        {{-- ─── PANEL BRAND (DESKTOP) ─── --}}
        <aside class="hidden lg:flex flex-col justify-between bg-[#171717] text-white px-16 py-14">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                    <span class="font-reenie text-[52px] leading-[100%]">SkanidaSong</span>
                    <span class="text-[10px] uppercase tracking-[0.22em] border border-white/25 rounded-full px-2.5 py-1 text-white/70">admin</span>
                </a>

                <blockquote class="mt-24 max-w-[36ch]">
                    <p class="font-reenie text-[34px] leading-[115%] text-white/90">"Kata-kata yang tak pernah terkatakan, tersampaikan lewat sebuah lagu."</p>
                </blockquote>
            </div>

            <div>
                <div class="flex gap-12 border-t border-white/10 pt-6">
                    <div>
                        <p class="font-reenie text-[34px] leading-[100%]">{{ number_format($totalMessages) }}</p>
                        <p class="text-xs text-white/45 mt-1.5">stories told</p>
                    </div>
                    <div>
                        <p class="font-reenie text-[34px] leading-[100%]">{{ number_format($totalKelas) }}</p>
                        <p class="text-xs text-white/45 mt-1.5">classes reached</p>
                    </div>
                </div>
                <p class="text-xs text-white/35 mt-6">SMK Negeri 2 &copy; {{ date('Y') }}</p>
            </div>
        </aside>

        {{-- ─── BRAND COMPACT (MOBILE) ─── --}}
        <div class="lg:hidden bg-[#171717] text-white px-5 py-6 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-reenie text-[34px] leading-[100%]">SkanidaSong</a>
            <span class="text-[10px] uppercase tracking-[0.22em] border border-white/25 rounded-full px-2.5 py-1 text-white/70">admin</span>
        </div>

        {{-- ─── FORM LOGIN ─── --}}
        <main class="flex items-center justify-center px-5 sm:px-10 py-12 lg:py-0">
            <div class="w-full max-w-sm auth-reveal">
                <a href="{{ url('/') }}"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-950 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 19l-7-7 7-7" />
                    </svg>
                    kembali ke situs
                </a>

                <h1 class="mt-8 text-2xl font-bold tracking-tight">Masuk ke panel admin</h1>
                <p class="mt-2 text-sm text-gray-500">Kelola pesan dan pantau statistik SkanidaSong.</p>

                {{-- Status session (contoh: "email verification sent") --}}
                <x-auth-session-status class="mt-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username"
                            class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors"
                            placeholder="nama@email.com">
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                autocomplete="current-password"
                                class="w-full px-4 py-3 pr-12 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors"
                                placeholder="password anda">
                            <button type="button" id="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-950 transition-colors"
                                aria-label="Tampilkan password">
                                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg id="eyeOffIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true" class="hidden">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.6 5.1A10.9 10.9 0 0112 5c6.5 0 10 7 10 7a17.6 17.6 0 01-2.9 3.9M6.6 6.6A16.8 16.8 0 002 12s3.5 7 10 7a10.5 10.5 0 004.1-.8" />
                                    <path d="M9.9 9.9a3 3 0 004.2 4.2" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>

                    {{-- Remember me --}}
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="remember" id="remember_me"
                            class="w-4 h-4 rounded border-[#D9D9D9] accent-[#171717]">
                        <span class="text-sm text-gray-600">Ingat saya di perangkat ini</span>
                    </label>

                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full py-3 px-6 rounded-xl bg-[#171717] hover:bg-gray-800 active:translate-y-[1px] text-white text-sm font-semibold transition-all">
                        Masuk
                    </button>

                    {{-- Lupa password --}}
                    @if (Route::has('password.request'))
                        <p class="text-center">
                            <a href="{{ route('password.request') }}"
                                class="text-sm text-gray-500 hover:text-gray-950 transition-colors">Lupa password?</a>
                        </p>
                    @endif
                </form>
            </div>
        </main>
    </div>

    <script>
        (function () {
            var toggle = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            var eye = document.getElementById('eyeIcon');
            var eyeOff = document.getElementById('eyeOffIcon');
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eye.classList.toggle('hidden', show);
                eyeOff.classList.toggle('hidden', !show);
            });
        })();
    </script>
</body>

</html>
