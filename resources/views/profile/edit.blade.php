@extends('layouts.smartroom')

@section('title', 'Akun & keamanan')

@php
    $user = auth()->user();
    $backUrl = $user->isAdmin() ? route('admin.dashboard') : route('app.profile');
    $backLabel = $user->isAdmin() ? 'Kembali ke Dashboard' : 'Kembali ke Profil';
@endphp

@section('content')
@include('partials.admin-header', ['title' => 'Akun & keamanan', 'caption' => 'Profil'])

{{-- Informasi akun --}}
<div class="section-heading"><h2>Informasi akun</h2></div>
<form class="card form-card" method="POST" action="{{ route('profile.update') }}" data-submit-busy>
    @csrf
    @method('PATCH')

    <div class="field">
        <label for="name">Nama</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name"
               @error('name') aria-invalid="true" @enderror>
        @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
               @error('email') aria-invalid="true" @enderror>
        @error('email') <span class="field-error">{{ $message }}</span> @enderror
        <span class="field-hint">Email dipakai untuk masuk ke SmartRoom.</span>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Simpan perubahan</button>
    </div>
</form>

{{-- Ganti password --}}
<div class="section-heading"><h2>Ganti password</h2></div>
<form class="card form-card" method="POST" action="{{ route('password.update') }}" data-submit-busy>
    @csrf
    @method('PUT')

    <p class="muted" style="margin-top:0">Gunakan password yang panjang dan sulit ditebak, minimal 8 karakter.</p>

    <div class="field">
        <label for="current_password">Password saat ini</label>
        <input id="current_password" type="password" name="current_password" autocomplete="current-password"
               @if ($errors->updatePassword->has('current_password')) aria-invalid="true" @endif>
        @if ($errors->updatePassword->has('current_password'))
            <span class="field-error">{{ $errors->updatePassword->first('current_password') }}</span>
        @endif
    </div>

    <div class="field">
        <label for="new_password">Password baru</label>
        <input id="new_password" type="password" name="password" autocomplete="new-password"
               @if ($errors->updatePassword->has('password')) aria-invalid="true" @endif>
        @if ($errors->updatePassword->has('password'))
            <span class="field-error">{{ $errors->updatePassword->first('password') }}</span>
        @endif
    </div>

    <div class="field">
        <label for="password_confirmation">Ulangi password baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Ganti password</button>
    </div>
</form>

{{-- Hapus akun (hanya pengguna biasa; admin dihapus lewat menu Pengguna oleh admin lain) --}}
@unless ($user->isAdmin())
    <div class="section-heading"><h2>Hapus akun</h2></div>
    <div class="card form-card" x-data="{ confirming: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }"
         x-on:keydown.escape.window="confirming = false">
        <p class="muted" style="margin-top:0">Setelah akun dihapus, semua data dan akses ruanganmu ikut hilang. Tindakan ini tidak bisa dibatalkan.</p>
        <button class="btn btn-danger" type="button" x-on:click="confirming = true; $nextTick(() => $refs.pw.focus())">Hapus akun saya</button>

        <div class="dialog-backdrop" x-show="confirming" x-cloak x-on:click.self="confirming = false">
            <form class="confirm-dialog" method="POST" action="{{ route('profile.destroy') }}" role="alertdialog" aria-modal="true" aria-labelledby="delete-title">
                @csrf
                @method('DELETE')
                <h2 id="delete-title">Hapus akun?</h2>
                <p>Masukkan password untuk memastikan ini memang kamu.</p>
                <div class="field" style="text-align:left">
                    <label for="delete_password">Password</label>
                    <input id="delete_password" type="password" name="password" x-ref="pw" autocomplete="current-password"
                           @if ($errors->userDeletion->has('password')) aria-invalid="true" @endif>
                    @if ($errors->userDeletion->has('password'))
                        <span class="field-error">{{ $errors->userDeletion->first('password') }}</span>
                    @endif
                </div>
                <div class="actions">
                    <button type="button" x-on:click="confirming = false">Batal</button>
                    <button type="submit" class="confirm-action">Ya, hapus</button>
                </div>
            </form>
        </div>
    </div>
@endunless

<p style="margin-top:20px"><a class="btn" href="{{ $backUrl }}">{{ $backLabel }}</a></p>
@endsection
