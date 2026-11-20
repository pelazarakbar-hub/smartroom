@extends('layouts.smartroom')

@section('title', 'Profil')

@section('content')
@include('partials.header', ['title' => 'Profil & Pengaturan', 'caption' => 'Kelola akun dan preferensi', 'alert' => $alert])

<section class="profile-card card" x-data="{ editing: {{ $errors->has('name') ? 'true' : 'false' }} }">
    <div class="profile-avatar">{{ $user->initials() }}</div>

    <div x-show="!editing" @if ($errors->has('name')) x-cloak @endif>
        <h2>{{ $user->name }}</h2>
        <p>{{ $user->email }}</p>
        <button type="button" x-on:click="editing = true; $nextTick(() => $refs.name.focus())">Edit profil</button>
    </div>

    <form class="profile-edit" method="POST" action="{{ route('app.profile.name') }}" data-submit-busy x-show="editing" @if (! $errors->has('name')) x-cloak @endif>
        @csrf
        <label>
            Nama tampilan
            <input name="name" x-ref="name" value="{{ old('name', $user->name) }}" autocomplete="name" @error('name') aria-invalid="true" @enderror>
            @error('name') <span class="field-error">{{ $message }}</span> @enderror
        </label>
        <div>
            <button type="submit" class="save">Simpan</button>
            <button type="button" x-on:click="editing = false">Batal</button>
        </div>
    </form>
</section>

<div class="section-heading"><h2>Privasi &amp; Data</h2></div>
<div class="settings-list card">
    <div class="setting-row">
        <div class="setting-icon"><x-icon name="shield" /></div>
        <div class="setting-copy">
            <strong>Mode privasi data</strong>
            <span data-setting-caption="privacy_mode">Batasi penyimpanan data sensor</span>
        </div>
        <button type="button" role="switch" class="toggle {{ $user->privacy_mode ? 'is-on' : '' }}" aria-checked="{{ $user->privacy_mode ? 'true' : 'false' }}"
                aria-label="Mode privasi data" data-setting="privacy_mode" data-url="{{ route('app.profile.settings') }}"><span></span></button>
    </div>
    <div class="privacy-explainer">
        <strong>Kontrol data Anda</strong>
        <p>Sensor keberadaan hanya membaca status “ada” atau “kosong” dan tidak merekam gambar, suara, atau identitas pribadi.</p>
        <span data-privacy-status>Status: {{ $user->privacy_mode ? 'Mode privasi aktif' : 'Penyimpanan standar' }}</span>
    </div>
</div>

<div class="section-heading"><h2>Preferensi</h2></div>
<div class="settings-list card">
    <div class="setting-row">
        <div class="setting-icon"><x-icon name="bell" /></div>
        <div class="setting-copy">
            <strong>Notifikasi</strong>
            <span data-setting-caption="notifications">Peringatan perangkat dan insight</span>
        </div>
        <button type="button" role="switch" class="toggle {{ $user->notifications ? 'is-on' : '' }}" aria-checked="{{ $user->notifications ? 'true' : 'false' }}"
                aria-label="Notifikasi" data-setting="notifications" data-url="{{ route('app.profile.settings') }}"><span></span></button>
    </div>
    <a class="setting-row clickable" href="{{ route('app.activity') }}">
        <div class="setting-icon"><x-icon name="clock" /></div>
        <div class="setting-copy"><strong>Riwayat aktivitas</strong><span>Lihat aktivitas perangkat</span></div>
        <x-icon name="chevron" :size="18" />
    </a>
    <a class="setting-row clickable" href="{{ route('profile.edit') }}">
        <div class="setting-icon"><x-icon name="user" /></div>
        <div class="setting-copy"><strong>Akun &amp; keamanan</strong><span>Ubah email, password, atau hapus akun</span></div>
        <x-icon name="chevron" :size="18" />
    </a>
</div>

<div class="section-heading"><h2>Bantuan</h2></div>
<div class="settings-list card" x-data="{ open: false }">
    <button class="setting-row clickable" type="button" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-expanded="false">
        <div class="setting-icon"><x-icon name="help" /></div>
        <div class="setting-copy"><strong>Panduan SmartRoom</strong><span>Langkah penggunaan dan pemecahan masalah</span></div>
        <x-icon name="chevron" :size="18" />
    </button>
    <div class="help-panel" x-show="open" x-cloak>
        <strong>Mulai dalam 3 langkah</strong>
        <ol>
            <li>Cek status sensor di Beranda.</li>
            <li>Atur perangkat dari menu Perangkat.</li>
            <li>Tinjau saran hemat energi di AI Insight.</li>
        </ol>
        <p>Perangkat tidak merespons? Pastikan statusnya “Online”, lalu coba kembali.</p>
        <a href="mailto:{{ config('mail.from.address') }}"><x-icon name="mail" :size="15" /> Hubungi bantuan</a>
    </div>
</div>

<div x-data="{ confirming: false }" x-on:keydown.escape.window="confirming = false">
    <button class="logout-button" type="button" x-ref="opener"
            x-on:click="confirming = true; $nextTick(() => $refs.cancel.focus())">Keluar dari akun</button>
    <p class="version">SmartRoom · Versi 1.0.0</p>

    <div class="dialog-backdrop" x-show="confirming" x-cloak x-on:click.self="confirming = false">
        <div class="confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="logout-title" aria-describedby="logout-description">
            <h2 id="logout-title">Keluar dari akun?</h2>
            <p id="logout-description">Anda perlu masuk kembali untuk mengontrol perangkat SmartRoom.</p>
            <div class="actions">
                <button type="button" x-ref="cancel" x-on:click="confirming = false; $refs.opener.focus()">Batal</button>
                <form method="POST" action="{{ route('logout') }}" style="display:contents">
                    @csrf
                    <button type="submit" class="confirm-action">Ya, keluar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
