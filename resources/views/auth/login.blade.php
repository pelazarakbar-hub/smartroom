@extends('layouts.smartroom-guest')

@section('title', 'Masuk')

@section('content')
<div class="login-screen">
    <div class="login-brand">
        <div class="house-sketch"><x-icon name="home" :size="42" /></div>
        <p>SMART HOME CONTROL</p>
        <h1>SmartRoom</h1>
        <span>Masuk dengan akun yang sudah terdaftar.</span>
    </div>

    <form class="login-form" method="POST" action="{{ route('login') }}" novalidate data-submit-busy>
        @csrf

        <label>
            Alamat email
            <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" inputmode="email" autofocus
                   @error('email') aria-invalid="true" @enderror>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label>
            Password
            <input type="password" name="password" placeholder="Masukkan password" autocomplete="current-password"
                   @error('password') aria-invalid="true" @enderror>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        @if (session('status'))
            <p class="auth-message success" role="status">{{ session('status') }}</p>
        @endif

        <button class="login-button" type="submit">Masuk</button>
    </form>

    <a class="register-link" href="{{ route('register') }}">Belum punya akun? <strong>Daftar dulu</strong></a>
</div>
@endsection
