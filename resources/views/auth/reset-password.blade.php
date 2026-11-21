@extends('layouts.smartroom-guest')

@section('title', 'Password baru')

@section('content')
<div class="login-screen">
    <div class="login-brand">
        <a href="{{ url('/') }}" aria-label="Kembali ke halaman depan SmartRoom"><img class="login-logo" src="{{ asset('img/logo.png') }}" alt="SmartRoom — Ruang Cerdas, Hidup Lebih Nyaman" width="900" height="233"></a>
        <h1 class="sr-only">Password baru</h1>
        <span>Buat password baru untuk akunmu.</span>
    </div>

    <form class="login-form" method="POST" action="{{ route('password.store') }}" novalidate data-submit-busy>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label>
            Alamat email
            <input type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" inputmode="email" required
                   @error('email') aria-invalid="true" @enderror>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label>
            Password baru
            <input type="password" name="password" placeholder="Minimal 8 karakter" autocomplete="new-password" autofocus required
                   @error('password') aria-invalid="true" @enderror>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label>
            Ulangi password baru
            <input type="password" name="password_confirmation" placeholder="Ketik ulang password" autocomplete="new-password" required>
        </label>

        <button class="login-button" type="submit">Simpan password baru</button>
    </form>
</div>
@endsection
