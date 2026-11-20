@extends('layouts.smartroom')

@section('title', 'Perangkat')

@section('content')
@include('partials.admin-header', [
    'title' => 'Perangkat',
    'caption' => 'Semua perangkat di semua ruangan',
    'action_url' => route('admin.devices.create'),
    'action_label' => 'Tambah',
])

@if ($devices->isEmpty())
    <div class="card empty-state">
        <div class="house-sketch"><x-icon name="device" :size="30" /></div>
        <h2>Belum ada perangkat</h2>
        <p>Tambahkan lampu atau kipas ke sebuah ruangan.</p>
    </div>
@else
    <div class="card table-card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Perangkat</th><th>Ruangan</th><th>Status</th><th>Koneksi</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($devices as $device)
                        <tr>
                            <td>{{ $device->id }}</td>
                            <td><strong>{{ $device->name }}</strong><small>{{ $device->typeLabel() }}</small></td>
                            <td>{{ $device->room?->name }}</td>
                            <td><span class="badge {{ $device->is_on ? 'success' : '' }}">{{ $device->is_on ? 'ON' : 'OFF' }}</span></td>
                            <td>
                                <span class="badge {{ $device->is_online ? 'success' : 'error' }}">{{ $device->is_online ? 'Online' : 'Offline' }}</span>
                                @if ($device->last_seen_at)<small>{{ $device->last_seen_at->diffForHumans() }}</small>@endif
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST" action="{{ route('admin.devices.toggle', $device) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">{{ $device->is_on ? 'Matikan' : 'Nyalakan' }}</button>
                                    </form>
                                    <a class="btn btn-sm" href="{{ route('admin.devices.edit', $device) }}">Ubah</a>
                                    <form method="POST" action="{{ route('admin.devices.destroy', $device) }}" data-confirm="Hapus perangkat {{ $device->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="device-footnote">ID perangkat dipakai di API perangkat (lihat halaman Ruangan &rarr; Ubah).</p>
@endif
@endsection
