# SmartRoom (Laravel + Breeze) — Panduan Pemasangan

Isi folder ini adalah **file tambahan** untuk proyek Laravel yang sudah dipasangi **Breeze**. Urutannya penting: buat proyek, pasang Breeze, baru salin file ini (supaya file SmartRoom menimpa file bawaan Breeze, bukan sebaliknya).

> **Catatan jujur:** kode ini sudah dicek sintaks PHP dan Blade-nya, dan JavaScript-nya (termasuk Alpine.js) sudah diuji di browser tiruan. Tetapi Laravel dan Breeze sendiri belum pernah saya jalankan (Composer diblokir di lingkungan saya bekerja), jadi halaman dan tes PHP belum pernah berjalan sungguhan. Jalankan `php artisan test` di langkah 7; kalau ada yang gagal atau ada error di halaman, kirim pesannya agar diperbaiki.

## Susunan teknologi

| Teknologi | Dipakai untuk |
|---|---|
| Laravel + PHP 8.3 | routing, backend, database |
| **Laravel Breeze (stack Blade)** | login, daftar, logout, lupa password, pengaturan akun |
| Blade, HTML, CSS | tampilan; CSS mengikuti desain Figma (`public/css/smartroom.css`) |
| **Alpine.js** | dialog keluar, ubah nama, panduan, tombol salin kunci |
| **Vite** | membangun Alpine.js (`resources/js/app.js`) |
| MySQL | pengguna, ruangan, perangkat, data sensor |

Tailwind ikut terpasang oleh Breeze, tetapi **hanya dipakai halaman bawaan Breeze** (lupa password dan "Akun & keamanan"). Halaman SmartRoom memakai CSS dari desain Figma agar tampilannya sama dengan desain.

## 1. Buat proyek dan pasang Breeze (Git Bash)

```bash
cd /c/xampp/htdocs
composer create-project laravel/laravel smartroom
cd smartroom
composer require laravel/breeze --dev
php artisan breeze:install blade
```

Jika ditanya: dark mode → **No**, testing framework → **PHPUnit**.

## 2. Salin file dari folder ini

Salin **isi** folder `smartroom-app` (folder `app`, `bootstrap`, `database`, `lang`, `public`, `resources`, `routes`, `tests`) ke dalam folder `smartroom`, lalu pilih **Replace / timpa** jika ditanya.

File Breeze yang sengaja ditimpa: `routes/web.php`, `resources/js/app.js`, `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`, `app/Http/Controllers/Auth/RegisteredUserController.php` (formulir daftar tanpa "ulangi password", sesuai desain), `tests/Feature/ExampleTest.php`, `tests/Feature/Auth/RegistrationTest.php`. Selain itu: `bootstrap/app.php`, `app/Models/User.php`, `app/Providers/AppServiceProvider.php`, `database/seeders/DatabaseSeeder.php`.

## 3. Database

1. Nyalakan **Apache** dan **MySQL** di XAMPP, buka `http://localhost/phpmyadmin`, buat database kosong `smartroom` (collation `utf8mb4_general_ci`).
2. Ubah file `.env`:

```env
APP_NAME=SmartRoom
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartroom
DB_USERNAME=root
DB_PASSWORD=

MAIL_FROM_ADDRESS="emailkamu@contoh.com"
```

3. Buat tabel dan data contoh:

```bash
php artisan migrate --seed
```

Akun contoh (**ganti passwordnya atau hapus akunnya sebelum website dipublikasikan**):

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@smartroom.test` | `Admin12345` |
| Pengguna | `user@smartroom.test` | `User12345` |

## 4. Build aset (wajib)

Halaman SmartRoom memuat Alpine.js lewat Vite, jadi aset harus dibangun dulu:

```bash
npm install
npm run build
```

Ulangi `npm run build` setiap kali file di `resources/js` atau `resources/css` berubah.

> **Untuk dibuka dari HP, pakai `npm run build`, bukan `npm run dev`.** Server `npm run dev` hanya bisa dijangkau dari laptop sendiri, sehingga di HP Alpine.js tidak termuat dan dialog serta panel tidak berfungsi. Jika sebelumnya sempat menjalankan `npm run dev`, hapus file `public/hot` sebelum membuka dari HP.

## 5. Jalankan

```bash
php artisan serve --host=0.0.0.0
```

Buka `http://localhost:8000`. Dari HP (satu Wi-Fi dengan laptop): `http://IP-LAPTOP:8000`, misalnya `http://192.168.1.10:8000`. Cek IP laptop dengan `ipconfig`. Jika HP tidak bisa membuka, izinkan port 8000 di Windows Firewall.

## 6. Data sensor contoh (sebelum ada perangkat sungguhan)

Di terminal kedua:

```bash
php artisan smartroom:simulate
```

Perintah ini mengirim data suhu, kelembapan, dan cahaya tiap 10 detik. Menyalakan kipas dari aplikasi akan membuat suhu turun pelan-pelan, mematikannya membuat suhu naik lagi.

## 7. Tes otomatis

```bash
php artisan test
```

Jalankan setelah `npm run build`, karena sebagian tes bawaan Breeze merender halaman yang memakai Vite.

## Alur dan halaman

- **Daftar** (`/register`): nama, email, password minimal 8 karakter. Peran selalu `user`. Setelah daftar, pengguna diarahkan ke halaman masuk.
- **Setelah login**, `/dashboard` mengarahkan sesuai peran: pengguna ke Beranda, admin ke Dashboard Admin.
- **Pengguna**: Beranda, Ruangan, Perangkat, AI Insight, Profil, Riwayat aktivitas. Di HP berupa navigasi bawah; di laptop berupa sidebar. Di Profil ada tautan **Akun & keamanan** (halaman Breeze untuk ubah email, password, hapus akun).
- **Admin** (`/admin`): Dashboard, Pengguna (ubah peran, hapus), Ruangan (tambah, ubah, akses pengguna, kunci perangkat), Perangkat (tambah, ubah, nyalakan, hapus).
- Admin pertama: pakai akun seed, atau daftar lalu ubah lewat `php artisan tinker`:
  `App\Models\User::where('email','emailkamu@contoh.com')->update(['role'=>'admin']);`
- Lupa password bawaan Breeze baru berfungsi setelah email dikonfigurasi (`MAIL_*` di `.env`). Dengan `MAIL_MAILER=log`, isi email ditulis ke `storage/logs/laravel.log`.

## Menyambungkan perangkat sungguhan (ESP32, Wemos, dll.)

1. Login sebagai admin → **Ruangan** → **Ubah** → bagian **Sambungkan Perangkat Sungguhan**. Di sana ada **kunci ruangan** dan contoh permintaan.
2. Perangkat mengirim data sensor:

```
POST http://ALAMAT-SERVER/api/device/readings
X-Room-Key: <kunci ruangan>
Content-Type: application/json

{"temperature": 29.5, "humidity": 64, "light": 410, "occupants": 1, "air_quality": 42}
```

3. Perangkat membaca perintah dari aplikasi (nyala/mati, level kipas) dengan `GET /api/device/state` secara berkala (misalnya tiap 2–5 detik), lalu menggerakkan relay. Setelah itu boleh melaporkan keadaan sebenarnya lewat `POST /api/device/state`.
4. Alamat server harus bisa dijangkau perangkat. Di Wi-Fi yang sama pakai IP laptop; untuk jarak jauh perlu website yang dipublikasikan di hosting.

Batasan saat ini: status "Online" perangkat diset saat perangkat mengirim data atau oleh admin; belum otomatis berubah menjadi "Offline" ketika perangkat berhenti mengirim. Status sistem di Beranda sudah memakai ketentuan "tidak ada data lebih dari 10 menit = offline".

## Mempublikasikan

Laravel butuh hosting PHP 8.2+ dengan MySQL (tidak bisa di GitHub Pages/Netlify biasa). Poin penting:

- Arahkan *document root* ke folder `public`.
- Jalankan `npm run build` di laptop lalu unggah folder `public/build` (atau jalankan di server bila ada Node.js).
- Di `.env` server: `APP_ENV=production`, `APP_DEBUG=false`, isi `APP_URL`, `APP_KEY` (`php artisan key:generate`), dan data database hosting.
- Jalankan `php artisan migrate --force`. Seeder tidak membuat akun contoh di production kecuali kamu mengisi `SEED_ADMIN_PASSWORD` di `.env`.
- Pakai **HTTPS**, karena ada login dan kunci perangkat.

## Yang belum ada

- Data sensor masih tersimpan di database (bukan pembacaan langsung dari perangkat) sampai perangkat disambungkan.
- "AI Insight" memakai aturan sederhana dari data 2 jam terakhir, belum model AI sungguhan.
- Notifikasi (lonceng) hanya menandai ada hal yang perlu dicek; belum mengirim notifikasi ke HP.
- Hapus riwayat data sensor lama belum otomatis (tabel `sensor_readings` akan terus bertambah).
