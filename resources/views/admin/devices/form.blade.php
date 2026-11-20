@extends('layouts.smartroom')

@php $editing = $device->exists; @endphp

@section('title', $editing ? 'Ubah Perangkat' : 'Tambah Perangkat')

@section('content')
@include('partials.admin-header', ['title' => $editing ? 'Ubah Perangkat' : 'Tambah Perangkat', 'caption' => 'Perangkat'])

<form class="card form-card" method="POST" action="{{ $editing ? route('admin.devices.update', $device) : route('admin.devices.store') }}" data-submit-busy>
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field">
        <label for="room_id">Ruangan</label>
        <select id="room_id" name="room_id" required>
            <option value="" disabled @selected(! old('room_id', $device->room_id))>Pilih ruangan</option>
            @foreach ($rooms as $item)
                <option value="{{ $item->id }}" @selected((int) old('room_id', $device->room_id) === $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        @error('room_id') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="name">Nama perangkat</label>
        <input id="name" type="text" name="name" value="{{ old('name', $device->name) }}" required maxlength="100" placeholder="Contoh: Lampu Utama">
        @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="type">Jenis</label>
        <select id="type" name="type" required>
            @foreach (\App\Models\Device::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $device->type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label class="check-row">
            <input type="checkbox" name="is_online" value="1" @checked(old('is_online', $device->is_online))>
            Perangkat online
        </label>
        <span class="field-hint">Perangkat sungguhan otomatis dianggap offline bila tidak mengirim data lebih dari 2 menit. Mencentang ini menandainya online lagi secara manual.</span>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="{{ route('admin.devices.index') }}">Kembali</a>
    </div>
</form>
@endsection
