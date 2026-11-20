@extends('layouts.smartroom')

@section('title', 'Perangkat')

@section('content')
<div data-devices>
    @include('partials.header', ['title' => 'Kontrol Perangkat', 'caption' => 'Perangkat di ruangan ini', 'alert' => $alert])

    <div class="device-summary card">
        <div><strong>{{ $connected }}</strong><span>Terdaftar</span></div>
        <div><strong>{{ $online }}</strong><span>Online</span></div>
        <div><strong data-active-count>{{ $active }}</strong><span>Aktif</span></div>
    </div>

    @if ($simulated > 0)
        <div class="sim-notice card" role="note">
            <x-icon name="info" :size="18" />
            <p><strong>Mode simulasi.</strong> {{ $simulated }} perangkat belum tersambung ke alat sungguhan (ESP32/Wemos). Perintah Anda tetap tersimpan dan akan dijalankan alat begitu terhubung lewat API.</p>
        </div>
    @endif

    <div class="live-impact card" style="margin-top:12px" aria-live="polite">
        <div><x-icon name="thermometer" :size="18" /><span>Suhu terakhir</span><strong>{{ $reading ? number_format($reading->temperature, 1).'°C' : '–' }}</strong></div>
        @if ($reading && $reading->light !== null)
            <div><x-icon name="sun" :size="18" /><span>Cahaya terakhir</span><strong>{{ $reading->light }} lx</strong></div>
        @endif
    </div>

    <div class="section-heading"><h2>Perangkat Saya</h2></div>

    @if ($devices->isEmpty())
        <div class="card empty-state"><p>Belum ada perangkat di ruangan ini. Admin dapat menambahkannya.</p></div>
    @else
        <div class="device-list card">
            @foreach ($devices as $device)
                <div class="device-control">
                    <div class="device-row">
                        <div class="device-icon"><x-icon :name="$device->icon()" /></div>
                        <div class="device-copy">
                            <strong>{{ $device->name }}</strong>
                            <span>{{ $room->name }} · {{ $device->typeLabel() }}</span>
                            @php $state = $device->connectionState(); @endphp
                            @if ($state === 'live')
                                <em class="status-badge"><x-icon name="check" :size="14" /> Online · tersinkron</em>
                            @elseif ($state === 'simulated')
                                <em class="status-badge simulated"><x-icon name="info" :size="14" /> Simulasi · belum tersambung ke alat</em>
                            @else
                                <em class="status-badge offline">
                                    <x-icon name="warning" :size="14" /> Offline
                                    @if ($device->is_online && $device->last_seen_at)
                                        · terakhir {{ $device->last_seen_at->diffForHumans() }}
                                    @endif
                                </em>
                            @endif
                        </div>
                        <div class="device-switch">
                            <small>{{ $device->is_on ? 'ON' : 'OFF' }}</small>
                            <button type="button" role="switch"
                                    class="toggle {{ $device->is_on ? 'is-on' : '' }}"
                                    aria-checked="{{ $device->is_on ? 'true' : 'false' }}"
                                    aria-label="{{ $device->name }}: {{ $device->is_on ? 'aktif' : 'nonaktif' }}"
                                    data-device-toggle
                                    data-type="{{ $device->type }}"
                                    data-name="{{ $device->name }}"
                                    data-url="{{ route('app.devices.toggle', $device) }}"
                                    @disabled(! $device->isOnline())>
                                <span></span>
                            </button>
                        </div>
                    </div>

                    @if ($device->type === 'fan')
                        <div class="fan-speed">
                            <div><span>Kecepatan kipas</span><strong data-speed-label>Level {{ $device->level }}</strong></div>
                            <input type="range" min="1" max="3" value="{{ $device->level }}" aria-label="Kecepatan kipas"
                                   data-speed-input data-url="{{ route('app.devices.speed', $device) }}"
                                   @disabled(! $device->is_on || ! $device->isOnline())>
                            <div class="range-labels"><span>Rendah</span><span>Sedang</span><span>Tinggi</span></div>
                            <p class="control-guidance" data-speed-guidance @if ($device->is_on) hidden @endif>Nyalakan kipas untuk mengatur kecepatan.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <p class="device-footnote">Setiap perubahan disimpan dan dibaca oleh perangkat di ruangan.</p>
</div>
@endsection
