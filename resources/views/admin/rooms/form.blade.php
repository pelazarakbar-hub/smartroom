@extends('layouts.smartroom')

@php $editing = $room->exists; @endphp

@section('title', $editing ? 'Ubah Ruangan' : 'Tambah Ruangan')

@section('content')
@include('partials.admin-header', ['title' => $editing ? 'Ubah Ruangan' : 'Tambah Ruangan', 'caption' => 'Ruangan'])

<form class="card form-card" method="POST" action="{{ $editing ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" data-submit-busy>
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field">
        <label for="name">Nama ruangan</label>
        <input id="name" type="text" name="name" value="{{ old('name', $room->name) }}" required maxlength="100">
        @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="description">Keterangan (opsional)</label>
        <input id="description" type="text" name="description" value="{{ old('description', $room->description) }}" maxlength="255">
        @error('description') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="auto_fan_above">Nyalakan kipas otomatis di atas suhu (°C)</label>
        <input id="auto_fan_above" type="number" name="auto_fan_above" step="0.5" min="20" max="45"
               value="{{ old('auto_fan_above', $room->auto_fan_above) }}" placeholder="Kosongkan untuk menonaktifkan">
        @error('auto_fan_above') <span class="field-error">{{ $message }}</span> @enderror
        <span class="field-hint">Aturan ini juga diaktifkan pengguna lewat tombol “Terapkan Saran” di AI Insight.</span>
    </div>

    <div class="field">
        <label class="check-row">
            <input type="checkbox" name="auto_join" value="1" @checked(old('auto_join', $room->auto_join))>
            Berikan otomatis ke pengguna yang baru mendaftar
        </label>
    </div>

    <div class="field">
        <label>Pengguna yang boleh mengakses</label>
        @if ($users->isEmpty())
            <p class="muted">Belum ada pengguna.</p>
        @else
            <div class="check-list">
                @foreach ($users as $item)
                    <label class="check-row">
                        <input type="checkbox" name="users[]" value="{{ $item->id }}" @checked(in_array($item->id, old('users', $selected)))>
                        {{ $item->name }} <small class="muted">· {{ $item->email }}</small>
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="{{ route('admin.rooms.index') }}">Kembali</a>
    </div>
</form>

@if ($editing)
    <div class="section-heading"><h2>Sambungkan Perangkat Sungguhan</h2></div>
    <div class="card form-card">
        <p class="muted" style="margin-top:0">Perangkat (ESP32, Wemos, dll.) memakai kunci ini untuk mengirim data sensor dan membaca perintah. Rahasiakan kunci ini.</p>

        <div class="field">
            <label>Kunci ruangan</label>
            <div class="key-box" id="room-key">{{ $room->api_key }}</div>
            <div class="form-actions" style="margin-top:10px">
                <button class="btn btn-sm" type="button" x-data="copyText" data-copy="{{ $room->api_key }}"
                        x-on:click="copy($el.dataset.copy)" x-text="copied ? 'Tersalin' : 'Salin'">Salin</button>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.rooms.key', $room) }}" data-confirm="Buat kunci baru? Perangkat yang memakai kunci lama harus diperbarui.">
            @csrf
            <button class="btn btn-sm btn-danger" type="submit">Buat kunci baru</button>
        </form>

        <div class="field" style="margin-top:20px">
            <label>Kirim data sensor</label>
            <pre class="code-block">POST {{ url('/api/device/readings') }}
X-Room-Key: {{ $room->api_key }}
Content-Type: application/json

{"temperature": 29.5, "humidity": 64}</pre>
            <p class="field-hint">Hanya <code>temperature</code> yang wajib. Kirim <code>humidity</code>, <code>light</code>, <code>occupants</code> (sensor PIR), atau <code>air_quality</code> (sensor udara) <strong>hanya bila sensornya terpasang</strong>; kolom yang tidak dikirim tidak ditampilkan di aplikasi.</p>
        </div>

        <div class="field">
            <label>Baca perintah dari pengguna (nyala/mati dan level kipas)</label>
            <pre class="code-block">GET {{ url('/api/device/state') }}
X-Room-Key: {{ $room->api_key }}</pre>
        </div>

        <div class="field">
            <label>Laporkan status perangkat yang sebenarnya</label>
            <pre class="code-block">POST {{ url('/api/device/state') }}
X-Room-Key: {{ $room->api_key }}
Content-Type: application/json

{"devices": [{"id": 1, "is_on": true, "level": 2}]}</pre>
        </div>
    </div>
@endif
@endsection
