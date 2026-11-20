@extends('layouts.smartroom')

@section('title', 'Riwayat Aktivitas')

@section('content')
@include('partials.header', ['title' => 'Riwayat Aktivitas', 'caption' => 'Aktivitas perangkat terbaru', 'alert' => $alert])

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

<p class="device-footnote"><a href="{{ route('app.profile') }}">← Kembali ke Profil</a></p>
@endsection
