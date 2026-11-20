@extends('layouts.smartroom')

@section('title', 'Ruangan')

@php
    $chartCard = function (string $label, string $value, string $trend, array $chart, array $axis) {
        return compact('label', 'value', 'trend', 'chart', 'axis');
    };
    $charts = [
        $chartCard('Suhu', $reading ? number_format($reading->temperature, 1).'°C' : '–', $tempTrend, $tempChart, $axis),
        $chartCard('Kelembapan', $reading ? $reading->humidity.'%' : '–', $humTrend, $humChart, $axis),
    ];
@endphp

@section('content')
<div data-live-refresh>
    @include('partials.header', ['title' => 'Monitoring Ruangan', 'caption' => 'Live · diperbarui otomatis', 'alert' => $alert])

    <form method="POST" action="{{ route('app.room.select') }}" class="room-selector card">
        @csrf
        <span>Ruangan aktif</span>
        <select name="room_id" aria-label="Pilih ruangan" data-auto-submit>
            @foreach ($rooms as $item)
                <option value="{{ $item->id }}" @selected($item->id === $room->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <span class="chev"><x-icon name="chevron" :size="18" /></span>
        <noscript><button type="submit">Pilih</button></noscript>
    </form>

    <div class="section-heading"><h2>Tren 6 Jam Terakhir</h2></div>
    <div class="grid-2">
        @foreach ($charts as $card)
            <article class="chart-card card">
                <div class="chart-title">
                    <div><span>{{ $card['label'] }}</span><strong>{{ $card['value'] }}</strong></div>
                    <span class="trend-label">{{ $card['trend'] }}</span>
                </div>
                @if ($card['chart']['points'] !== '')
                    <svg class="mini-chart" viewBox="0 0 272 66" preserveAspectRatio="none" role="img" aria-label="Grafik {{ strtolower($card['label']) }} 6 jam terakhir">
                        <path class="chart-grid" d="M0 13h272M0 33h272M0 53h272"/>
                        <polyline points="{{ $card['chart']['points'] }}"/>
                        <circle cx="{{ $card['chart']['x'] }}" cy="{{ $card['chart']['y'] }}" r="3.5"/>
                    </svg>
                    <div class="chart-axis"><span>{{ $card['axis'][0] }}</span><span>{{ $card['axis'][1] }}</span><span>{{ $card['axis'][2] }}</span></div>
                @else
                    <p class="chart-empty">Belum cukup data dalam 6 jam terakhir.</p>
                @endif
            </article>
        @endforeach
    </div>

    <div class="section-heading"><h2>Status Lingkungan</h2></div>
    @if (count($details))
        <div class="detail-list card">
            @foreach ($details as $item)
                <div class="detail-row {{ ! $loop->last ? 'with-border' : '' }}">
                    <div><strong>{{ $item['label'] }}</strong><span>{{ $item['range'] }}</span></div>
                    <div class="detail-value">
                        <strong>{{ $item['value'] }}</strong>
                        <span class="status-chip {{ $item['tone'] }}"><x-icon :name="$item['icon']" :size="14" /> {{ $item['state'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card empty-state"><p>Belum ada data sensor untuk ruangan ini.</p></div>
    @endif
</div>
@endsection
