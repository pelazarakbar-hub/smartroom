@extends('layouts.smartroom')

@section('title', 'Ruangan')

@section('content')
@include('partials.admin-header', [
    'title' => 'Ruangan',
    'caption' => 'Kelola ruangan dan akses pengguna',
    'action_url' => route('admin.rooms.create'),
    'action_label' => 'Tambah',
])

@if ($rooms->isEmpty())
    <div class="card empty-state">
        <div class="house-sketch"><x-icon name="room" :size="30" /></div>
        <h2>Belum ada ruangan</h2>
        <p>Tambahkan ruangan, lalu pasang perangkat di dalamnya.</p>
    </div>
@else
    <div class="card table-card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Ruangan</th><th>Perangkat</th><th>Pengguna</th><th>Aturan kipas</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $room)
                        <tr>
                            <td>
                                <strong>{{ $room->name }}</strong>
                                @if ($room->description)<small>{{ $room->description }}</small>@endif
                                @if ($room->auto_join)<small>Otomatis untuk pengguna baru</small>@endif
                            </td>
                            <td>{{ $room->devices_count }}</td>
                            <td>{{ $room->users_count }}</td>
                            <td>{{ $room->auto_fan_above !== null ? '≥ '.rtrim(rtrim(number_format($room->auto_fan_above, 1), '0'), '.').'°C' : 'Nonaktif' }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-sm" href="{{ route('admin.rooms.edit', $room) }}">Ubah</a>
                                    <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" data-confirm="Hapus ruangan {{ $room->name }} beserta semua perangkat dan data sensornya?">
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
@endif
@endsection
