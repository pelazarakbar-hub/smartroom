<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SmartRoom — Ruang cerdas, hidup lebih nyaman</title>
    <meta name="description" content="SmartRoom memantau suhu dan kondisi ruangan, mengendalikan lampu dan kipas dari HP, dan memberi saran lewat AI Insight. Dibuka langsung di browser.">
    <meta name="theme-color" content="#F2F6F1">
    <meta property="og:title" content="SmartRoom — Ruang cerdas, hidup lebih nyaman">
    <meta property="og:description" content="Pantau dan kendalikan ruangan dari HP, dengan saran AI Insight.">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600..800&family=Figtree:wght@400..600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <script src="{{ asset('js/landing.js') }}" defer></script>
</head>
<body>
<a class="skip-link" href="#utama">Lewati ke konten</a>

<header class="site-header">
    <div class="wrap bar">
        <a class="brand" href="{{ url('/') }}" aria-label="SmartRoom, halaman depan">
            <img src="{{ asset('img/logo-icon.png') }}" alt="" width="40" height="40">
            <span>Smart<b>Room</b></span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu" data-menu-toggle>
            <span class="sr-only">Buka menu</span>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <nav class="menu" id="menu" aria-label="Menu utama" data-menu>
            <ul>
                <li><a href="#fitur">Fitur</a></li>
                <li><a href="#cara-kerja">Cara kerja</a></li>
                <li><a href="#peran">Pengguna dan admin</a></li>
            </ul>
            <div class="menu-actions">
                <a class="btn btn-ghost" href="{{ route('login') }}">Masuk</a>
                <a class="btn btn-solid" href="{{ route('register') }}">Daftar</a>
            </div>
        </nav>
    </div>
</header>

<main id="utama">
    {{-- ------------------------------------------------------------ hero --}}
    <section class="hero">
        <svg class="hero-signal" viewBox="0 0 400 400" fill="none" aria-hidden="true" focusable="false">
            <path d="M40 360A320 320 0 0 1 360 40" stroke="currentColor" stroke-width="2"/>
            <path d="M40 360A240 240 0 0 1 280 120" stroke="currentColor" stroke-width="2" transform="translate(0 0)"/>
            <path d="M40 360A160 160 0 0 1 200 200" stroke="currentColor" stroke-width="2"/>
        </svg>

        <div class="wrap hero-grid">
            <div class="hero-copy">
                <h1>Ruang cerdas, hidup lebih nyaman.</h1>
                <p class="lead">Pantau suhu, nyalakan lampu dan kipas, lalu baca saran AI Insight. Semuanya dari satu aplikasi web yang dibuka lewat Chrome di HP.</p>
                <div class="cta-row">
                    <a class="btn btn-solid btn-lg" href="{{ route('register') }}">Buat akun</a>
                    <a class="btn btn-outline btn-lg" href="{{ route('login') }}">Masuk</a>
                </div>
                <p class="fine">Tanpa instal aplikasi. Buka di browser HP atau laptop.</p>
            </div>

            {{-- Demo: penampang ruangan yang bisa dicoba --}}
            <section class="demo" aria-labelledby="demo-judul" data-demo>
                <h2 id="demo-judul" class="sr-only">Coba kendalikan ruangan</h2>

                <div class="stage">
                    <svg viewBox="0 0 520 340" role="img" aria-label="Ilustrasi ruangan berisi sofa, lampu gantung, kipas berdiri, tanaman, dan sensor di dinding" data-room>
                        <defs>
                            <linearGradient id="cone" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#FFD98A" stop-opacity=".75"/>
                                <stop offset="1" stop-color="#FFD98A" stop-opacity="0"/>
                            </linearGradient>
                            <radialGradient id="glow">
                                <stop offset="0" stop-color="#FFE9A8" stop-opacity=".95"/>
                                <stop offset="1" stop-color="#FFE9A8" stop-opacity="0"/>
                            </radialGradient>
                        </defs>

                        <rect class="r-wall" width="520" height="270"/>
                        <rect class="r-heat" width="520" height="270" data-heat/>
                        <rect class="r-floor" y="270" width="520" height="70"/>
                        <rect class="r-skirt" y="266" width="520" height="8"/>

                        {{-- jendela --}}
                        <rect class="r-glass" x="38" y="46" width="120" height="140" rx="6"/>
                        <circle class="r-sun" cx="122" cy="84" r="17"/>
                        <path class="r-frame" d="M98 46v140M38 116h120" />
                        <rect class="r-frame-box" x="38" y="46" width="120" height="140" rx="6"/>

                        {{-- sensor di dinding, mengirim data lewat Wi-Fi --}}
                        <g class="r-sensor">
                            <rect x="296" y="86" width="40" height="26" rx="6"/>
                            <circle class="led" cx="308" cy="99" r="3.2"/>
                            <path d="M318 96h10M318 102h6"/>
                            <path class="arc a1" d="M306 76a14 14 0 0 1 20 0"/>
                            <path class="arc a2" d="M301 70a22 22 0 0 1 30 0"/>
                            <path class="arc a3" d="M296 64a30 30 0 0 1 40 0"/>
                        </g>

                        {{-- lampu gantung --}}
                        <g class="r-lamp" data-lamp>
                            <polygon class="cone" points="236,108 264,108 372,268 128,268"/>
                            <circle class="glow" cx="250" cy="108" r="64" fill="url(#glow)"/>
                            <path class="cord" d="M250 0v66"/>
                            <path class="shade" d="M222 66h56l16 30h-88z"/>
                            <circle class="bulb" cx="250" cy="102" r="9"/>
                        </g>

                        {{-- sofa --}}
                        <g class="r-sofa">
                            <rect class="back" x="168" y="186" width="164" height="56" rx="18"/>
                            <rect class="seat" x="150" y="226" width="200" height="42" rx="14"/>
                            <rect class="arm" x="138" y="212" width="32" height="56" rx="13"/>
                            <rect class="arm" x="330" y="212" width="32" height="56" rx="13"/>
                            <path class="seam" d="M250 196v40"/>
                        </g>

                        {{-- kipas berdiri --}}
                        <g class="r-fan" data-fan>
                            <path class="flow f1" d="M366 176c-18 0-18-8-36-8s-18 8-36 8"/>
                            <path class="flow f2" d="M366 192c-18 0-18-8-36-8s-18 8-36 8"/>
                            <path class="flow f3" d="M366 208c-18 0-18-8-36-8s-18 8-36 8"/>
                            <path class="pole" d="M414 268v-34"/>
                            <rect class="foot" x="396" y="262" width="36" height="8" rx="4"/>
                            <circle class="guard" cx="414" cy="192" r="42"/>
                            <g class="blades">
                                <ellipse cx="414" cy="172" rx="9" ry="19"/>
                                <ellipse cx="414" cy="172" rx="9" ry="19" transform="rotate(120 414 192)"/>
                                <ellipse cx="414" cy="172" rx="9" ry="19" transform="rotate(240 414 192)"/>
                            </g>
                            <circle class="hub" cx="414" cy="192" r="7"/>
                        </g>

                        {{-- tanaman --}}
                        <g class="r-plant">
                            <path class="pot" d="M474 270l4-26h24l4 26z"/>
                            <ellipse cx="490" cy="222" rx="9" ry="22" transform="rotate(-24 490 240)"/>
                            <ellipse cx="490" cy="218" rx="9" ry="24"/>
                            <ellipse cx="490" cy="222" rx="9" ry="22" transform="rotate(24 490 240)"/>
                        </g>
                    </svg>
                    <span class="demo-badge"><i class="live-dot" aria-hidden="true"></i>Demo, coba sendiri</span>
                </div>

                <div class="panel">
                    <dl class="readouts">
                        <div class="read">
                            <dt>Suhu</dt>
                            <dd><span data-temp>31.0</span><small>°C</small></dd>
                        </div>
                        <div class="read">
                            <dt>Cahaya</dt>
                            <dd><span data-light>180</span><small>lx</small></dd>
                        </div>
                        <div class="read score">
                            <dt>Skor kenyamanan</dt>
                            <dd>
                                <svg viewBox="0 0 64 64" width="64" height="64" aria-hidden="true">
                                    <circle class="ring-bg" cx="32" cy="32" r="26"/>
                                    <circle class="ring" cx="32" cy="32" r="26" data-ring transform="rotate(-90 32 32)"/>
                                </svg>
                                <span class="score-num" data-score>67</span>
                            </dd>
                        </div>
                    </dl>

                    <p class="insight warn" data-insight role="status" aria-live="polite">
                        <x-icon name="spark" :size="18" />
                        <span data-insight-text>Suhu 31.0°C, di atas batas nyaman 30°C. Nyalakan kipas.</span>
                    </p>

                    <div class="switches">
                        <button class="switch" type="button" role="switch" aria-checked="false" data-switch="fan">
                            <span class="track"><span class="knob"></span></span>
                            <span class="switch-name">Kipas</span>
                        </button>
                        <button class="switch" type="button" role="switch" aria-checked="false" data-switch="lamp">
                            <span class="track"><span class="knob"></span></span>
                            <span class="switch-name">Lampu</span>
                        </button>
                    </div>
                    <p class="note">Simulasi di halaman ini, bukan data sungguhan.</p>
                </div>
            </section>
        </div>
    </section>

    {{-- ------------------------------------------------------------ fitur --}}
    <section class="section" id="fitur" aria-labelledby="fitur-judul">
        <div class="wrap features">
            <div class="features-head">
                <h2 id="fitur-judul">Satu layar untuk memantau dan mengendalikan ruangan.</h2>
                <p>SmartRoom menampilkan apa yang sedang terjadi di ruangan, lalu membantu memutuskan apa yang perlu dilakukan.</p>
            </div>

            <div class="feature-list">
                <article class="feature">
                    <div class="feature-text">
                        <h3>Pantau kondisi ruangan</h3>
                        <p>Suhu, kelembapan, cahaya, keberadaan, dan kualitas udara tampil di Beranda. Hanya sensor yang benar-benar terpasang yang ditampilkan, jadi tidak ada angka kosong atau angka karangan.</p>
                    </div>
                    <div class="feature-visual" aria-hidden="true">
                        <ul class="chips">
                            <li><x-icon name="thermometer" :size="18" /><b>27.5°C</b><span>Nyaman</span></li>
                            <li><x-icon name="droplet" :size="18" /><b>62%</b><span>Normal</span></li>
                            <li><x-icon name="sun" :size="18" /><b>420 lx</b><span>Terang</span></li>
                        </ul>
                    </div>
                </article>

                <article class="feature">
                    <div class="feature-text">
                        <h3>Kendalikan perangkat dari HP</h3>
                        <p>Nyalakan atau matikan lampu dan kipas, lalu atur kecepatan kipas dari level 1 sampai 3. Perangkat yang offline ditandai jelas, sehingga kamu tahu kapan perintah tidak akan sampai.</p>
                    </div>
                    <div class="feature-visual" aria-hidden="true">
                        <div class="segments"><span>Level 1</span><span class="on">Level 2</span><span>Level 3</span></div>
                    </div>
                </article>

                <article class="feature">
                    <div class="feature-text">
                        <h3>Baca saran dari AI Insight</h3>
                        <p>Skor kenyamanan 0 sampai 100, tren suhu 30 menit terakhir, dan saran yang bisa langsung dijalankan, misalnya mematikan lampu saat ruangan kosong.</p>
                    </div>
                    <div class="feature-visual" aria-hidden="true">
                        <div class="mini-insight">
                            <svg viewBox="0 0 64 64" width="56" height="56"><circle class="ring-bg" cx="32" cy="32" r="26"/><circle class="ring ok" cx="32" cy="32" r="26" style="stroke-dasharray: 140 163.4" transform="rotate(-90 32 32)"/></svg>
                            <div><b>86</b><span>Suhu naik 2°C dalam 30 menit. Kipas otomatis bisa menahannya.</span></div>
                        </div>
                    </div>
                </article>

                <article class="feature">
                    <div class="feature-text">
                        <h3>Kelola dari panel admin</h3>
                        <p>Tambah ruangan dan perangkat, atur siapa yang boleh mengakses, dan ambil kunci API untuk menyambungkan ESP32 atau Wemos.</p>
                    </div>
                    <div class="feature-visual" aria-hidden="true">
                        <div class="key-line"><x-icon name="shield" :size="18" /><code>X-Room-Key</code><span>kunci per ruangan</span></div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------ cara kerja --}}
    <section class="section how" id="cara-kerja" aria-labelledby="cara-judul">
        <div class="wrap">
            <h2 id="cara-judul">Dari sensor ke layar HP dalam tiga langkah.</h2>

            <ol class="steps">
                <li>
                    <span class="step-no">1</span>
                    <h3>Pasang sensor</h3>
                    <p>Hubungkan ESP32 atau Wemos dengan sensor suhu dan kelembapan seperti DHT11. Sensor lain bersifat opsional.</p>
                </li>
                <li>
                    <span class="step-no">2</span>
                    <h3>Kirim data lewat Wi-Fi</h3>
                    <p>Alat mengirim bacaan ke SmartRoom memakai kunci ruangan, dan mengambil perintah nyala atau mati dari aplikasi.</p>
                </li>
                <li>
                    <span class="step-no">3</span>
                    <h3>Pantau dan kendalikan</h3>
                    <p>Buka SmartRoom di Chrome, lihat kondisi ruangan, dan ubah status lampu atau kipas dari mana saja.</p>
                </li>
            </ol>

            <div class="how-foot">
                <pre class="code" aria-label="Contoh permintaan dari perangkat"><span class="c-m">POST</span> /api/device/readings
<span class="c-k">X-Room-Key:</span> kunci-ruangan-anda

{"temperature": 29.5, "humidity": 64}</pre>
                <p class="how-note">Hanya suhu yang wajib dikirim. Belum punya alat? Mode simulasi membuat aplikasi tetap bisa dicoba sampai alatmu siap.</p>
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------ peran --}}
    <section class="section" id="peran" aria-labelledby="peran-judul">
        <div class="wrap">
            <h2 id="peran-judul">Dua peran dalam satu aplikasi.</h2>

            <div class="roles">
                <article class="role role-user">
                    <h3>Pengguna</h3>
                    <p>Untuk orang yang tinggal atau memakai ruangan.</p>
                    <ul>
                        <li><x-icon name="check" :size="18" /> Melihat suhu dan kondisi ruangan</li>
                        <li><x-icon name="check" :size="18" /> Menyalakan lampu dan kipas</li>
                        <li><x-icon name="check" :size="18" /> Membaca dan menerapkan saran AI Insight</li>
                        <li><x-icon name="check" :size="18" /> Melihat riwayat aktivitas perangkat</li>
                    </ul>
                </article>

                <article class="role role-admin">
                    <h3>Admin</h3>
                    <p>Untuk orang yang mengelola ruangan dan perangkat.</p>
                    <ul>
                        <li><x-icon name="check" :size="18" /> Menambah ruangan dan perangkat</li>
                        <li><x-icon name="check" :size="18" /> Mengatur akses pengguna ke tiap ruangan</li>
                        <li><x-icon name="check" :size="18" /> Mengambil kunci untuk alat ESP32 atau Wemos</li>
                        <li><x-icon name="check" :size="18" /> Memantau semua ruangan dari satu dashboard</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------ ajakan --}}
    <section class="closing" aria-labelledby="penutup-judul">
        <svg class="closing-signal" viewBox="0 0 400 400" fill="none" aria-hidden="true" focusable="false">
            <path d="M40 360A320 320 0 0 1 360 40" stroke="currentColor" stroke-width="2"/>
            <path d="M40 360A240 240 0 0 1 280 120" stroke="currentColor" stroke-width="2"/>
            <path d="M40 360A160 160 0 0 1 200 200" stroke="currentColor" stroke-width="2"/>
        </svg>
        <div class="wrap closing-inner">
            <h2 id="penutup-judul">Mulai dari satu ruangan.</h2>
            <p>Buat akun, lalu lihat kondisi ruanganmu dari HP.</p>
            <div class="cta-row">
                <a class="btn btn-light btn-lg" href="{{ route('register') }}">Buat akun</a>
                <a class="btn btn-line-light btn-lg" href="{{ route('login') }}">Masuk</a>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="wrap foot">
        <a class="brand" href="{{ url('/') }}" aria-label="SmartRoom, halaman depan">
            <img src="{{ asset('img/logo-icon.png') }}" alt="" width="32" height="32">
            <span>Smart<b>Room</b></span>
        </a>
        <p>Ruang cerdas, hidup lebih nyaman.</p>
        <nav aria-label="Tautan kaki">
            <a href="{{ route('login') }}">Masuk</a>
            <a href="{{ route('register') }}">Daftar</a>
        </nav>
        <small>&copy; {{ date('Y') }} SmartRoom</small>
    </div>
</footer>
</body>
</html>
