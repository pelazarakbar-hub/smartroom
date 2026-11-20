@extends('layouts.smartroom-guest')

@section('title', 'Daftar')

@section('content')
<div class="login-screen">
    <div class="login-brand">
        <a href="{{ url('/') }}" aria-label="Kembali ke halaman depan SmartRoom"><img class="login-logo" src="{{ asset('img/logo.png') }}" alt="SmartRoom — Ruang Cerdas, Hidup Lebih Nyaman" width="900" height="233"></a>
        <h1 class="sr-only">SmartRoom</h1>
        <span>Daftarkan akun sebelum menggunakan SmartRoom.</span>
    </div>

    <form class="login-form" method="POST" action="{{ route('register') }}" novalidate data-submit-busy>
        @csrf

        <label>
            Nama
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Masukkan nama Anda" autocomplete="name" autofocus
                   @error('name') aria-invalid="true" @enderror>
            @error('name') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label>
            Alamat email
            <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" inputmode="email"
                   @error('email') aria-invalid="true" @enderror>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label>
            Password
            <input type="password" name="password" placeholder="Minimal 8 karakter" autocomplete="new-password"
                   aria-describedby="password-guidance" @error('password') aria-invalid="true" @enderror>
            <span class="field-hint" id="password-guidance">Gunakan minimal 8 karakter.</span>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <button class="login-button" type="submit">Daftar akun</button>
    </form>

    <a class="register-link" href="{{ route('login') }}">Sudah punya akun? <strong>Masuk</strong></a>
</div>
@endsection
