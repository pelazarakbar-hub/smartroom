@extends('layouts.smartroom')

@section('title', 'AI Insight')

@php
    $items = $analysis['insights'];
    $first = $items[0];
    $rest = array_slice($items, 1);
    $priorityLabel = ['high' => 'Prioritas tinggi', 'medium' => 'Perlu perhatian', 'low' => 'Saran', 'ok' => 'Kondisi baik'];
@endphp

@section('content')
@include('partials.header', ['title' => 'AI Insight', 'caption' => 'Analisis pintar harian', 'alert' => $alert])

<div class="comfort-card card">
    <div class="comfort-score" aria-hidden="true">
        @if ($analysis['score'] !== null)
            <strong>{{ $analysis['score'] }}</strong><span>dari 100</span>
        @else
            <strong>–</strong>
        @endif
    </div>
    <div class="comfort-body">
        <div class="comfort-head">
            <h2>Skor kenyamanan</h2>
            <span class="status-chip {{ $analysis['tone'] }}">{{ $analysis['label'] }}</span>
        </div>
        @if ($analysis['score'] !== null)
            <div class="score-bar" role="img" aria-label="Skor kenyamanan {{ $analysis['score'] }} dari 100">
                <span class="{{ $analysis['tone'] }}" style="width: {{ $analysis['score'] }}%"></span>
            </div>
        @endif
        <p>{{ $analysis['headline'] }}</p>
        <small>Berdasarkan {{ $analysis['samples'] }} data sensor dalam 2 jam terakhir · {{ count($items) }} rekomendasi</small>
    </div>
</div>

<div class="section-heading"><h2>Prioritas Utama</h2></div>
<article class="recommendation-card card">
    <div class="recommendation-head">
        <span class="number-box">01</span>
        <span class="priority">{{ $priorityLabel[$first['severity']] }} · {{ now()->format('H.i') }}</span>
    </div>
    <h2>{{ $first['title'] }}</h2>
    <p>{{ $first['description'] }}</p>

    @if ($first['reason'])
        <div class="explain-box">
            <strong>Mengapa disarankan?</strong>
            <span>{{ $first['reason'] }}</span>
        </div>
    @endif

    @include('partials.insight-actions', ['item' => $first])
</article>

@if (count($rest) > 0)
    <div class="section-heading"><h2>Rekomendasi Lainnya</h2></div>
    <div class="grid-2">
        @foreach ($rest as $index => $item)
            <article class="compact-recommendation card">
                <div class="compact-recommendation-main">
                    <div class="number-box">{{ str_pad((string) ($index + 2), 2, '0', STR_PAD_LEFT) }}</div>
                    <div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                        @if ($item['reason'])
                            <p class="compact-reason">{{ $item['reason'] }}</p>
                        @endif
                        @if ($item['chip'])
                            <span class="status-chip {{ $item['chip']['tone'] }}"><x-icon :name="$item['chip']['icon']" :size="14" /> {{ $item['chip']['label'] }}</span>
                        @endif
                    </div>
                </div>
                @include('partials.insight-actions', ['item' => $item])
            </article>
        @endforeach
    </div>
@endif
@endsection
