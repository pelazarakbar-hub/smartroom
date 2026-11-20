@extends('layouts.smartroom')

@section('title', 'Pengguna')

@section('content')
@include('partials.admin-header', ['title' => 'Pengguna', 'caption' => 'Kelola akun dan peran'])

<div class="card table-card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Nama</th><th>Peran</th><th>Ruangan</th><th>Terdaftar</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @foreach ($users as $item)
                    @php $self = $item->is(auth()->user()); @endphp
                    <tr>
                        <td><strong>{{ $item->name }}</strong>{{ $self ? ' (Anda)' : '' }}<small>{{ $item->email }}</small></td>
                        <td><span class="badge {{ $item->isAdmin() ? 'info' : '' }}">{{ $item->isAdmin() ? 'Admin' : 'Pengguna' }}</span></td>
                        <td>{{ $item->isAdmin() ? 'Semua' : $item->rooms_count }}</td>
                        <td>{{ $item->created_at?->translatedFormat('d M Y') }}</td>
                        <td>
                            @if ($self)
                                <small>–</small>
                            @else
                                <div class="actions">
                                    <form method="POST" action="{{ route('admin.users.role', $item) }}" data-confirm="Ubah peran {{ $item->name }} menjadi {{ $item->isAdmin() ? 'Pengguna' : 'Admin' }}?">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="role" value="{{ $item->isAdmin() ? 'user' : 'admin' }}">
                                        <button type="submit" class="btn btn-sm">{{ $item->isAdmin() ? 'Jadikan Pengguna' : 'Jadikan Admin' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $item) }}" data-confirm="Hapus akun {{ $item->name }}? Tindakan ini tidak bisa dibatalkan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<p class="device-footnote">Akses pengguna ke ruangan diatur dari menu Ruangan.</p>
@endsection
