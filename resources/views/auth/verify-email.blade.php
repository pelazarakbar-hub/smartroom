@extends('layouts.smartroom-guest')

@section('title', 'Verifikasi email')

@section('content')
<div class="login-screen">
    <div class="login-brand">
        <a href="{{ url('/') }}" aria-label="Kembali ke halaman depan SmartRoom"><img class="login-logo" src="{{ asset('img/logo.png') }}" alt="SmartRoom — Ruang Cerdas, Hidup Lebih Nyaman" width="900" height="233"></a>
        <h1 class="sr-only">Verifikasi email</h1>
        <span>Kami sudah mengirim tautan verifikasi ke emailmu. Klik tautan itu untuk mengaktifkan akun.</span>
    </div>

    @if (session('status') == 'verification-link-sent')
        <p class="auth-message success" role="status">Tautan verifikasi baru sudah dikirim ke emailmu.</p>
    @endif

    <form class="login-form" method="POST" action="{{ route('verification.send') }}" data-submit-busy>
        @csrf
        <button class="login-button" type="submit">Kirim ulang email verifikasi</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="register-link" type="submit" style="background:none;border:0;width:100%;cursor:pointer">Bukan akunmu? <strong>Keluar</strong></button>
    </form>
</div>
@endsection
