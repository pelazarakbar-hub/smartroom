# Persiapan publikasi SmartRoom

Jalankan `php artisan smartroom:cek-publikasi` kapan saja. Perintah itu memeriksa poin 1 sampai 3 di bawah dan menyebutkan apa yang masih perlu diperbaiki.

## 1. Ganti atau hapus akun contoh
Akun `admin@smartroom.test` (Admin12345) dan `user@smartroom.test` (User12345) hanya untuk uji coba lokal. Cara termudah di server: isi `SEED_ADMIN_EMAIL` dan `SEED_ADMIN_PASSWORD` di `.env`, lalu `php artisan db:seed`. Akun contoh tidak dibuat di production.

Bila akun contoh sudah ada di database, ganti passwordnya lewat tinker:

```
php artisan tinker
>>> App\Models\User::where('email','admin@smartroom.test')->update(['email'=>'emailkamu@contoh.com','password'=>Hash::make('PasswordBaruYangKuat')]);
>>> App\Models\User::where('email','user@smartroom.test')->delete();
```

## 2. Pengaturan `.env` di server
Lihat `.env.production.example`. Yang paling penting: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` memakai `https://`, dan password database yang kuat. Jangan pernah meng-commit `.env`.

Setelah `.env` benar:
```
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 3. Pembersihan data sensor otomatis
Data sensor lebih dari 30 hari dihapus setiap hari pukul 03.00. Agar jalan, server perlu menjalankan scheduler. Tambahkan satu baris cron:

```
* * * * * cd /path/ke/smartroom && php artisan schedule:run >> /dev/null 2>&1
```

Uji manual: `php artisan smartroom:prune-readings --days=30`.

## Tambahan yang disarankan
- Pakai HTTPS. Alat ESP32 mengirim kunci ruangan (`X-Room-Key`), jadi jangan kirim lewat HTTP biasa.
- Aktifkan email sungguhan (`MAIL_*` di `.env`) agar fitur lupa password berfungsi. Tanpa itu, tautan reset hanya tertulis di log.
- Backup database secara berkala.
