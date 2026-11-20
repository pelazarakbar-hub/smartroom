@extends('layouts.smartroom')

@section('title', $title)

@section('content')
    @include('partials.header', ['title' => $title, 'caption' => 'SmartRoom', 'alert' => false])

    <section class="card empty-state">
        <div class="house-sketch"><x-icon name="room" :size="30" /></div>
        <h2>Belum ada ruangan</h2>
        <p>Akun Anda belum terhubung ke ruangan mana pun. Hubungi admin agar ruangan ditambahkan ke akun Anda.</p>
    </section>
@endsection
