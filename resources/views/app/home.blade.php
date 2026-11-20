@extends('layouts.smartroom')

@section('title', 'Beranda')

@section('content')
<div data-live-refresh>
    @include('partials.header', [
        'home' => true,
        'title' => 'Halo, '.$user->firstName(),
        'caption' => 'SmartRoom · '.$room->name,
    ])

    <article class="insight-banner insight-banner-top card">
        <div class="insight-icon"><x-icon name="spark" /></div>
        <div>
            <span class="label"><x-icon name="info" :size="14" /> AI Insight · {{ $reading ? $reading->recorded_at->diffForHumans() : 'Belum ada data' }}</span>
            <h3>{{ $banner['title'] }}</h3>
            <p>{{ $banner['description'] }}</p>
            @if ($fan)
                <button type="button"
                        data-fan-quick
                        data-url="{{ route('app.devices.toggle', $fan) }}"
                        data-next="{{ $fan->is_on ? '0' : '1' }}">
                    {{ $fan->is_on ? 'Matikan kipas' : 'Nyalakan kipas · One-Tap' }}
                </button>
            @endif
        </div>
    </article>

    <section class="room-status card">
        <div class="status-copy">
            <span class="status-marker {{ $fresh ? '' : 'off' }}"><x-icon :name="$fresh ? 'check' : 'warning'" :size="14" /></span>
            <div>
                <p>Status sistem</p>
                <h2>{{ $fresh ? 'Semua sensor aktif' : 'Sensor tidak mengirim data' }}</h2>
            </div>
        </div>
        <div class="room-pill {{ $fresh ? '' : 'off' }}">{{ $fresh ? 'Live' : 'Offline' }}</div>
    </section>

    <div class="section-heading"><h2>Sensor Real-Time</h2></div>
    <div class="sensor-grid">
        @foreach ($sensors as $sensor)
            <article class="sensor-card card">
                <div class="sensor-icon"><x-icon :name="$sensor['icon']" /></div>
                <p>{{ $sensor['label'] }}</p>
                <strong>{{ $sensor['value'] }}</strong>
                <span class="status-chip {{ $sensor['tone'] }}"><x-icon :name="$sensor['status_icon']" :size="14" /> {{ $sensor['note'] }}</span>
            </article>
        @endforeach
    </div>
</div>
@endsection
