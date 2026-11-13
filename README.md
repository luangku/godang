# CMS Blog Godang

Aplikasi Blog Sederhana yang dibuat menggunakan framework Laravel dan di deploy ke ifinityfree

## Fitur Aplikasi

- Menampilkan semua daftar postingan
- Menampilkan daftar berdasarkan kategori
- Tiap pengunjung bisa meninggalkan komentar di postingan

## Tech dan Versi

Aplikasi ini dibangun dengan menggunakan :

- Laragon 6
- Laravel 13
- PHP 8.4.1
- VS Code 1.146
- Node Js 26.13
- Git 2.56
- Composer 2.11.2
- Opencode 2.2.7

Lebih lengkapnya silahkan lihat STRUCTURE.md

## Instalasi

- Cloning repository git ke sebuah folder di local

```sh
git clone https://github.com/luangku/simple-blog.git
```

- Install modul yang dibutuhkan

```sh
npm install
```

- Install depedensi via composer

```sh
composer install
```

- Generate autoload dengan composer (jika belum ada)

```sh
composer dump-autoload
```

- Lakukan migrasi database dan jalankan seeder

```sh
php artisan migrate:fresh --seed
```

- Copy file .env.example dan rename menjadi .env

```sh
.env
```

- Pada file `.env` isikan opsi `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai dengan kredensial database yang sudah di buat

- Buat simbolik link agar fitur upload bisa berfungsi

```sh
php artisan storage:link
```

- Generate key

```sh
php artisan key:generate
```

- Lalu jalankan

```sh
npm run build
```

- Jalankan aplikasi Laragon, lalu klik start all

- Buka aplikasi blog di browser

```sh
http://simple-blog.test
```
