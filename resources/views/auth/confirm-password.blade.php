@extends('layouts.smartroom-guest')

@section('title', 'Konfirmasi password')

@section('content')
<div class="login-screen">
    <div class="login-brand">
        <a href="{{ url('/') }}" aria-label="Kembali ke halaman depan SmartRoom"><img class="login-logo" src="{{ asset('img/logo.png') }}" alt="SmartRoom — Ruang Cerdas, Hidup Lebih Nyaman" width="900" height="233"></a>
        <h1 class="sr-only">Konfirmasi password</h1>
        <span>Ini area aman. Masukkan password untuk melanjutkan.</span>
    </div>

    <form class="login-form" method="POST" action="{{ route('password.confirm') }}" novalidate data-submit-busy>
        @csrf

        <label>
            Password
            <input type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" autofocus required
                   @error('password') aria-invalid="true" @enderror>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <button class="login-button" type="submit">Konfirmasi</button>
    </form>
</div>
@endsection
