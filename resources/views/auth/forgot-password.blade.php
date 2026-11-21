@extends('layouts.smartroom-guest')

@section('title', 'Lupa password')

@section('content')
<div class="login-screen">
    <div class="login-brand">
        <a href="{{ url('/') }}" aria-label="Kembali ke halaman depan SmartRoom"><img class="login-logo" src="{{ asset('img/logo.png') }}" alt="SmartRoom — Ruang Cerdas, Hidup Lebih Nyaman" width="900" height="233"></a>
        <h1 class="sr-only">Lupa password</h1>
        <span>Masukkan email akunmu. Kami kirim tautan untuk membuat password baru.</span>
    </div>

    <form class="login-form" method="POST" action="{{ route('password.email') }}" novalidate data-submit-busy>
        @csrf

        <label>
            Alamat email
            <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" inputmode="email" autofocus
                   @error('email') aria-invalid="true" @enderror>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        @if (session('status'))
            <p class="auth-message success" role="status">{{ session('status') }}</p>
        @endif

        <button class="login-button" type="submit">Kirim tautan reset</button>
    </form>

    <a class="register-link" href="{{ route('login') }}">Ingat password? <strong>Kembali masuk</strong></a>
</div>
@endsection
