{{-- Header halaman. Variabel: $title, $caption (opsional), $alert (titik notifikasi), $home (true = sapaan Beranda) --}}
<header class="{{ ($home ?? false) ? 'brand-header' : 'page-header' }}">
    <div>
        @if (!empty($caption)) <p>{{ $caption }}</p> @endif
        <h1>{{ $title }}</h1>
    </div>
    <div class="header-actions">
        <a class="icon-button" href="{{ route('app.insight') }}" aria-label="Notifikasi dan saran">
            <x-icon name="bell" />
            @if (!empty($alert)) <span class="notification-dot"></span> @endif
        </a>
        @if ($home ?? false)
            <a class="avatar" href="{{ route('app.profile') }}" aria-label="Buka profil">{{ $user->initials() }}</a>
        @endif
    </div>
</header>
