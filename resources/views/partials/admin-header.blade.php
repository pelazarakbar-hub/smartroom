{{-- Header halaman admin. Variabel: $title, $caption (opsional), $action_url + $action_label (opsional) --}}
<header class="page-header">
    <div>
        @if (!empty($caption)) <p>{{ $caption }}</p> @endif
        <h1>{{ $title }}</h1>
    </div>
    @if (!empty($action_url))
        <div class="header-actions">
            <a class="btn btn-light" href="{{ $action_url }}"><x-icon name="plus" :size="16" /> {{ $action_label }}</a>
        </div>
    @endif
</header>
