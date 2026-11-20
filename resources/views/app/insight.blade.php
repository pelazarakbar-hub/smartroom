@extends('layouts.smartroom')

@section('title', 'AI Insight')

@php
    $first = $insights[0];
    $rest = array_slice($insights, 1);
    $feedbackUrl = fn (string $key) => route('app.insight.feedback', $key);
@endphp

@section('content')
@include('partials.header', ['title' => 'AI Insight', 'caption' => 'Analisis pintar harian', 'alert' => $alert])

<div class="analysis-summary card">
    <x-icon name="spark" :size="24" />
    <div><strong>{{ count($insights) }} rekomendasi hari ini</strong><span>Berdasarkan pola sensor 2 jam terakhir</span></div>
</div>

<div class="section-heading"><h2>Detail Rekomendasi Terbaru</h2></div>
<article class="recommendation-card card">
    <div class="recommendation-head">
        <span class="number-box">01</span>
        <span class="priority">Hari ini · {{ now()->format('H.i') }}</span>
    </div>
    <h2>{{ $first['title'] }}</h2>
    <p>{{ $first['description'] }}</p>

    @if ($first['reason'])
        <div class="explain-box">
            <strong>Mengapa disarankan?</strong>
            <span>{{ $first['reason'] }}</span>
        </div>
    @endif

    @if ($first['actionable'])
        <button class="primary-action" type="button" data-apply-insight data-url="{{ route('app.insight.apply', $first['key']) }}" @disabled($first['applied'])>
            {{ $first['applied'] ? 'Saran diterapkan' : 'Terapkan Saran' }}
        </button>
        <p class="inline-success" role="status" data-applied-text @if (! $first['applied']) hidden @endif>{{ $first['applied_text'] }}</p>
    @endif

    <div class="feedback">
        <span>Apakah saran ini membantu?</span>
        <div data-feedback data-url="{{ $feedbackUrl($first['key']) }}">
            <button type="button" data-helpful="1" class="{{ $first['helpful'] === true ? 'selected' : '' }}" aria-label="Membantu"><x-icon name="thumb-up" :size="16" /></button>
            <button type="button" data-helpful="0" class="{{ $first['helpful'] === false ? 'selected' : '' }}" aria-label="Tidak membantu"><x-icon name="thumb-down" :size="16" /></button>
        </div>
    </div>
</article>

<div class="section-heading"><h2>Riwayat Rekomendasi</h2></div>
<div class="grid-2">
    @foreach ($rest as $index => $item)
        <article class="compact-recommendation card">
            <div class="compact-recommendation-main">
                <div class="number-box">{{ str_pad((string) ($index + 2), 2, '0', STR_PAD_LEFT) }}</div>
                <div>
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['description'] }}</p>
                    @if ($item['chip'])
                        <span class="status-chip {{ $item['chip']['tone'] }}"><x-icon :name="$item['chip']['icon']" :size="14" /> {{ $item['chip']['label'] }}</span>
                    @endif
                </div>
            </div>
            <div class="feedback">
                <span>Apakah saran ini membantu?</span>
                <div data-feedback data-url="{{ $feedbackUrl($item['key']) }}">
                    <button type="button" data-helpful="1" class="{{ $item['helpful'] === true ? 'selected' : '' }}" aria-label="Membantu"><x-icon name="thumb-up" :size="16" /></button>
                    <button type="button" data-helpful="0" class="{{ $item['helpful'] === false ? 'selected' : '' }}" aria-label="Tidak membantu"><x-icon name="thumb-down" :size="16" /></button>
                </div>
            </div>
        </article>
    @endforeach
</div>
@endsection
