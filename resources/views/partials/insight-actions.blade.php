{{-- Tombol terapkan saran + umpan balik untuk satu rekomendasi. Variabel: $item --}}
@if ($item['actionable'])
    <button class="primary-action" type="button" data-apply-insight data-url="{{ route('app.insight.apply', $item['key']) }}"
            data-done-label="{{ $item['done_label'] }}" @disabled($item['applied'])>
        {{ $item['applied'] ? $item['done_label'] : $item['action_label'] }}
    </button>
    <p class="inline-success" role="status" data-applied-text @if (! $item['applied']) hidden @endif>{{ $item['applied_text'] }}</p>
@endif

<div class="feedback">
    <span>Apakah saran ini membantu?</span>
    <div data-feedback data-url="{{ route('app.insight.feedback', $item['key']) }}">
        <button type="button" data-helpful="1" class="{{ $item['helpful'] === true ? 'selected' : '' }}" aria-label="Membantu"><x-icon name="thumb-up" :size="16" /></button>
        <button type="button" data-helpful="0" class="{{ $item['helpful'] === false ? 'selected' : '' }}" aria-label="Tidak membantu"><x-icon name="thumb-down" :size="16" /></button>
    </div>
</div>
