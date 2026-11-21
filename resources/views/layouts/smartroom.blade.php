<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2f7a4b">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <title>@yield('title', 'SmartRoom') · SmartRoom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/smartroom.css') }}">
    {{-- Alpine.js (lewat Vite) untuk dialog, panel, dan tombol salin --}}
    @vite(['resources/js/app.js'])
</head>
<body>
@php
    $nav = \App\Support\Nav::items(auth()->user());
    $isActive = fn (array $item) => request()->routeIs(...$item['match']);
@endphp
<a class="skip-link" href="#konten">Lewati ke konten</a>

<div class="app-shell">
    {{-- Sidebar: hanya tampil di laptop (>= 900px) --}}
    <aside class="sidebar" aria-label="Menu utama">
        <div class="sidebar-brand">
            <img class="sidebar-logo" src="{{ asset('img/logo.png') }}" alt="SmartRoom" width="900" height="233">
            <span>{{ auth()->user()->isAdmin() ? 'Panel Admin' : 'Smart Home Control' }}</span>
        </div>
        <nav>
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="{{ $isActive($item) ? 'active' : '' }}" @if ($isActive($item)) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" :size="22" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="sidebar-account">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ auth()->user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="app-main" id="konten">
        <div class="screen-content">
            @if (session('status'))
                @php
                    // Kunci status bawaan Breeze diterjemahkan; teks lain ditampilkan apa adanya.
                    $statusText = [
                        'profile-updated' => 'Perubahan akun berhasil disimpan.',
                        'password-updated' => 'Password berhasil diganti.',
                    ][session('status')] ?? session('status');
                @endphp
                <p class="flash" role="status"><x-icon name="check" :size="16" /> {{ $statusText }}</p>
            @endif
            @if (session('error'))
                <p class="flash error" role="alert"><x-icon name="warning" :size="16" /> {{ session('error') }}</p>
            @endif

            @yield('content')
        </div>
    </main>
</div>

{{-- Navigasi bawah: hanya tampil di HP --}}
<nav class="bottom-nav" aria-label="Navigasi utama">
    @foreach ($nav as $item)
        <a href="{{ route($item['route']) }}" class="{{ $isActive($item) ? 'active' : '' }}" @if ($isActive($item)) aria-current="page" @endif>
            <x-icon :name="$item['icon']" :size="22" />
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
    @if (auth()->user()->isAdmin())
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">
                <x-icon name="logout" :size="22" />
                <span>Keluar</span>
            </button>
        </form>
    @endif
</nav>

<div id="notice-host" aria-live="polite"></div>

<script src="{{ asset('js/smartroom.js') }}" defer></script>
@stack('scripts')
</body>
</html>
