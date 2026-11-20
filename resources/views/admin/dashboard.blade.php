@extends('layouts.smartroom')

@section('title', 'Dashboard Admin')

@section('content')
<div data-live-refresh>
    @include('partials.admin-header', ['title' => 'Dashboard Admin', 'caption' => 'SmartRoom · Ringkasan sistem'])

    <div class="stat-grid">
        <div class="stat-card card"><span>Pengguna</span><strong>{{ $stats['users'] }}</strong></div>
        <div class="stat-card card"><span>Ruangan</span><strong>{{ $stats['rooms'] }}</strong></div>
        <div class="stat-card card"><span>Perangkat</span><strong>{{ $stats['devices'] }}</strong></div>
        <div class="stat-card card"><span>Perangkat online</span><strong>{{ $stats['online'] }}</strong></div>
        <div class="stat-card card"><span>Perangkat aktif</span><strong>{{ $stats['active'] }}</strong></div>
    </div>

    <div class="section-heading">
        <h2>Status Ruangan</h2>
        <a href="{{ route('admin.rooms.index') }}">Kelola</a>
    </div>
    @if ($rooms->isEmpty())
        <div class="card empty-state"><p>Belum ada ruangan. Tambahkan ruangan pertama dari menu Ruangan.</p></div>
    @else
        <div class="card table-card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Ruangan</th><th>Suhu</th><th>Kelembapan</th><th>Cahaya</th><th>Data terakhir</th><th>Pengguna</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($rooms as $room)
                            @php
                                $reading = $readings[$room->id];
                                $fresh = $reading && $reading->recorded_at->gte(now()->subMinutes(10));
                            @endphp
                            <tr>
                                <td><strong>{{ $room->name }}</strong><small>{{ $room->devices_count }} perangkat</small></td>
                                <td>{{ $reading ? number_format($reading->temperature, 1).'°C' : '–' }}</td>
                                <td>{{ $reading ? $reading->humidity.'%' : '–' }}</td>
                                <td>{{ $reading ? $reading->light.' lx' : '–' }}</td>
                                <td>
                                    @if ($reading)
                                        <span class="badge {{ $fresh ? 'success' : 'error' }}">{{ $fresh ? 'Online' : 'Offline' }}</span>
                                        <small>{{ $reading->recorded_at->diffForHumans() }}</small>
                                    @else
                                        <span class="badge">Belum ada data</span>
                                    @endif
                                </td>
                                <td>{{ $room->users_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="section-heading"><h2>Aktivitas Terbaru</h2></div>
    @if ($logs->isEmpty())
        <div class="card empty-state"><p>Belum ada aktivitas perangkat.</p></div>
    @else
        <div class="activity-list card">
            @foreach ($logs as $log)
                <div class="activity-row">
                    <div class="setting-icon"><x-icon :name="$log->device?->icon() ?? 'device'" /></div>
                    <div>
                        <strong>{{ $log->message }}</strong>
                        <span>{{ $log->device?->room?->name }} · {{ $log->user?->name ?? 'Otomatis' }} · {{ $log->created_at?->diffForHumans() }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
