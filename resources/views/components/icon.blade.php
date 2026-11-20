@props(['name', 'size' => 20])
@php
    // Ikon sama dengan desain Figma; "grid" dan "logout" ditambahkan untuk menu admin.
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7"/><path d="M5 9v11h14V9M9 20v-6h6v6"/>',
        'room' => '<path d="M4 3h16v18H4zM4 8h16"/><path d="M8 12h3v3H8zM15 12h1M15 16h1"/>',
        'device' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M9 6h6M9 10h6M10 18h4"/>',
        'spark' => '<path d="M12 2c.4 4.1 2.6 6.2 6.5 6.5C14.6 8.9 12.4 11 12 15c-.4-4-2.6-6.1-6.5-6.5C9.4 8.2 11.6 6.1 12 2Z"/><path d="M18.5 14c.2 2.2 1.4 3.3 3.5 3.5-2.1.2-3.3 1.3-3.5 3.5-.2-2.2-1.4-3.3-3.5-3.5 2.1-.2 3.3-1.3 3.5-3.5Z"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.7-4.2 3.4-6 8-6s7.3 1.8 8 6"/>',
        'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 7H3s3 0 3-7Z"/><path d="M10 20h4"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'thermometer' => '<path d="M9 4a3 3 0 0 1 6 0v9a5 5 0 1 1-6 0Z"/><path d="M12 6v10"/>',
        'droplet' => '<path d="M12 2S5 10 5 15a7 7 0 0 0 14 0c0-5-7-13-7-13Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 19c.5-3.5 2.5-5 6-5s5.5 1.5 6 5M16 5c2 .2 3 1.2 3 3s-1 2.8-3 3M17 14c2.5.4 3.7 2 4 5"/>',
        'bulb' => '<path d="M8 15a7 7 0 1 1 8 0l-1 2H9Z"/><path d="M9 20h6M10 17h4"/>',
        'fan' => '<circle cx="12" cy="12" r="2"/><path d="M12 10c-1-5 1-8 4-7 3 1 2 5-2 8M14 12c5-1 8 1 7 4-1 3-5 2-8-2M12 14c1 5-1 8-4 7-3-1-2-5 2-8M10 12c-5 1-8-1-7-4 1-3 5-2 8 2"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'shield' => '<path d="M12 2 4 5v6c0 5 3 8.5 8 11 5-2.5 8-6 8-11V5Z"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.3 2.4c-.8.3-.8.9-.8 1.6M12 17h.01"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'thumb-up' => '<path d="M7 10v11H3V10h4Zm0 9h10.5a2 2 0 0 0 2-1.7l1-6A2 2 0 0 0 18.5 9H14l1-4c.3-1.4-1.5-2.2-2.3-1L7 10"/>',
        'thumb-down' => '<path d="M7 14V3H3v11h4Zm0-9h10.5a2 2 0 0 1 2 1.7l1 6a2 2 0 0 1-2 2.3H14l1 4c.3 1.4-1.5 2.2-2.3 1L7 14"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'warning' => '<path d="M12 3 2.8 20h18.4Z"/><path d="M12 9v4M12 17h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
        'grid' => '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
        'logout' => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
    ];
    $body = $paths[$name] ?? $paths['info'];
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $body !!}</svg>
