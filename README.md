# CMS Blog

Aplikasi blog sederhana yang dibangun dengan framework Laravel. Aplikasi ini terdiri dari dua bagian:

- **Halaman publik** — daftar artikel, halaman kategori/tag, pencarian, detail artikel, dan komentar pengunjung.
- **Panel admin** (`/admin`) — SPA React untuk mengelola artikel, komentar, kategori, tag, media, pengguna admin, dan pengaturan situs.

Aplikasi ini di-deploy ke InfinityFree melalui GitHub Actions.

## Fitur Aplikasi

### Halaman publik

- Menampilkan semua daftar postingan yang sudah terbit
- Menampilkan daftar postingan berdasarkan kategori dan tag
- Pencarian artikel
- Detail artikel dengan daftar isi (TOC), progress bar bacaan, dan syntax highlighting
- Tiap pengunjung bisa meninggalkan komentar, termasuk balasan (reply) bertingkat
- Komentar dimoderasi sebelum tampil (opsional melalui pengaturan)
- Notifikasi email ke admin saat ada komentar baru, dan ke komentator saat balasannya dibalas
- SEO: meta title/description, Open Graph, JSON-LD, sitemap, dan RSS

### Panel admin

- Dashboard statistik (artikel, komentar, jumlah tayangan)
- CRUD artikel dengan editor HTML, preview, penjadwalan terbit, dan soft delete (Trash)
- Moderasi komentar (setujui, tandai spam, edit, hapus, balas sebagai penulis)
- Edit komentar milik penulis langsung dari halaman artikel (tombol **Edit** di samping Spam/Hapus)
- Nama admin di komentar (dengan badge **Admin**), badge "Mode Admin", dan sidebar diambil dari akun admin (`users.name`), bukan dari setting nama penulis
- CRUD kategori dan tag
- Pustaka media: upload gambar (otomatis dikonversi ke WebP dan diperkecil), thumbnail, kategori media, pencarian, serta deteksi media yang masih dipakai artikel/pengaturan
- Level admin: **Admin Utama** dan **Admin**; hanya Admin Utama yang dapat mengelola akun admin dan mengubah pengaturan situs
- Halaman **Profil** untuk setiap admin mengganti nama, email, dan password miliknya sendiri
- Pengaturan situs (khusus Admin Utama): nama situs, logo, avatar penulis, sosial media, moderasi komentar, dan lainnya

## Tech dan Versi

Aplikasi ini dibangun dengan menggunakan:

| Komponen | Versi   |
| -------- | ------- |
| Laragon  | 6       |
| PHP      | 8.5.12  |
| Laravel  | 13.37.0 |
| Composer | 2.11.1  |
| Node.js  | 26.13.0 |
| npm      | 12.2.2  |
| Git      | 2.56    |
| VS Code  | 1.146   |
| OpenCode | 2.2.7   |

> Versi di atas adalah versi yang dipakai saat dokumentasi ini diperbarui. Versi minimum yang dibutuhkan mengikuti `composer.json` dan `package.json`.

## Instalasi di local

- Buka folder `C:\laragon\www` di aplikasi VS Code (jika Laragon di-install di drive C)
- Buka terminal di VS Code dan jalankan

```sh
git clone https://github.com/luangku/godang.git
```

- Di VS Code klik **File → Open Folder**, lalu pilih folder hasil clone
- Install modul frontend yang dibutuhkan

```sh
npm install
```

- Install dependensi PHP via Composer

```sh
composer install
```

- Generate autoload Composer (jika folder `vendor` belum lengkap)

```sh
composer dump-autoload
```

- Copy file `.env.example` lalu rename menjadi `.env`

```sh
copy .env.example .env
```

- Isi opsi `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env` sesuai kredensial database yang sudah dibuat
- Jalankan Laragon, lalu klik **Start All**
- Lakukan migrasi database dan jalankan seeder

```sh
php artisan migrate:fresh --seed
```

- Buat symbolic link agar fitur upload media bisa berfungsi

```sh
php artisan storage:link
```

- Generate application key

```sh
php artisan key:generate
```

- Build asset frontend

```sh
npm run build
```

- Buka aplikasi Laragon, klik kanan, pilih **www**, lalu pilih folder proyek. Atau langsung buka:

```sh
http://godang.test
```

## Login Admin

- URL login: `http://godang.test/admin/login`
- Email: `admin@godang.com`
- Password: `password`

> Segera ganti email dan password default setelah instalasi.

> Akun default ini adalah **Admin Utama**. Pada database lama yang sudah memiliki beberapa admin, migration `add_is_primary_to_users` otomatis mempromosikan admin dengan `id` terkecil sebagai Admin Utama.

## Mengelola Pengguna Admin

Ada dua level akun admin:

- **Admin Utama** — hanya satu akun, yaitu admin pertama yang dibuat. Akun ini dapat menambah, mengubah, dan menghapus admin lain.
- **Admin** — akun yang ditambahkan setelahnya. Admin biasa tetap bisa mengakses panel admin selain **Pengguna** dan **Pengaturan**, tetapi tidak dapat mengelola akun admin atau mengubah pengaturan situs.

Pengguna admin dikelola langsung dari panel admin, tidak perlu lagi memakai `php artisan tinker`.

1. Login sebagai **Admin Utama**.
2. Buka menu **Pengguna** di sidebar (`/admin/users`).
3. Isi form **Tambah Admin** (nama, email, password, konfirmasi password) lalu klik **Simpan**. Admin baru selalu dibuat sebagai **Admin** biasa.
4. Untuk mengubah data, klik **Edit** pada baris admin. Kosongkan kolom password bila tidak ingin menggantinya.
5. Untuk menghapus, klik **Hapus** lalu konfirmasi.

Setiap admin, termasuk Admin biasa, dapat membuka menu **Profil** (`/admin/profile`) untuk mengganti nama, email, dan password miliknya sendiri.

Pengaman yang berlaku:

- Menu dan endpoint **Pengguna** serta **Pengaturan** hanya bisa diakses Admin Utama; admin biasa mendapat 403.
- Hanya akun dengan `is_admin = true` yang muncul dan bisa dikelola di halaman ini.
- Admin tidak dapat menghapus akunnya sendiri.
- Admin Utama tidak dapat diubah atau dihapus oleh admin lain.
- Admin yang masih memiliki artikel (termasuk artikel di Trash) tidak dapat dihapus. Pindahkan atau hapus artikelnya terlebih dahulu.
- Minimal harus ada satu admin yang tersisa.

Di halaman **Pengaturan** (khusus Admin Utama), field **Nama Penulis** bersifat read-only dan selalu menampilkan nama **Admin Utama**. Nilai ini dipakai di halaman publik (About, sidebar, komentar lama yang belum punya nama) dan tidak dapat ditimpa dari form pengaturan.

Jika Anda terkunci di luar panel admin, gunakan perintah darurat berikut:

```sh
php artisan tinker --execute 'User::where("email", "admin@godang.com")->update(["password" => Hash::make("password_baru")]);'
```

## Queue Email

Notifikasi email (komentar baru dan balasan komentar) dikirim melalui queue. Konfigurasi default di `.env.example` adalah:

```sh
QUEUE_CONNECTION=database
```

Artinya email tidak dikirim saat request berjalan, tetapi masuk ke tabel `jobs` lebih dulu. Agar email benar-benar terkirim, jalankan worker di server:

```sh
php artisan queue:work --tries=3
```

Untuk produksi, jalankan worker tersebut lewat supervisor, cron, atau process manager lain yang tersedia di hosting. Jika hosting tidak mendukung worker sama sekali, ubah `QUEUE_CONNECTION=sync` agar email dikirim langsung (konsekuensinya request bisa lebih lambat bila server SMTP bermasalah).

Konfigurasi email sendiri diatur melalui variabel `MAIL_*` di `.env`.

## Testing

```sh
php artisan test
```

Test menggunakan SQLite in-memory (`phpunit.xml`), jadi tidak mengganggu database development. Untuk menjalankan sebagian test saja:

```sh
php artisan test --filter=NamaTest
```

## Deployment

Deploy otomatis berjalan melalui `.github/workflows/infinityfree.yml` setiap kali ada push ke branch `main`. Workflow ini meng-upload file ke hosting via FTP.

Beberapa hal yang sengaja dilakukan agar cocok dengan keterbatasan hosting:

- **`vendor/` ikut di-commit.** Hosting InfinityFree tidak menyediakan Composer dan tidak menjalankan `composer install` saat deploy. Karena itu dependensi PHP disimpan di repository agar aplikasi langsung bisa jalan setelah di-upload. Konsekuensinya ukuran repository lebih besar; perbarui folder ini setiap kali dependensi berubah (`composer update`).
- **`public/build/` ikut di-commit.** Hosting tidak menjalankan `npm install`/`npm run build`, sehingga asset hasil build harus sudah ada di repository. Jalankan `npm run build` sebelum commit jika ada perubahan JavaScript/CSS.
- **`STRUCTURE.md` tidak ikut di-upload.** File ini hanya dokumentasi teknis untuk developer, bukan bagian dari aplikasi, jadi sengaja dikecualikan dari proses deploy.
- **Scheduler tidak berjalan otomatis.** Hosting tidak menyediakan cron yang bisa memanggil `php artisan schedule:run`, sehingga artikel berstatus `scheduled` perlu diterbitkan manual (atau lewat cron eksternal) di hosting yang mendukung.
- **Migrasi database dijalankan manual.** Setelah file ter-upload, jalankan `php artisan migrate`. Migration `add_is_primary_to_users` menambahkan kolom `is_primary` dan mempromosikan admin paling awal sebagai Admin Utama.

## Struktur Dokumentasi

- `README.md` — panduan instalasi, penggunaan, dan deployment (file ini).
- `STRUCTURE.md` — dokumentasi teknis lengkap: arsitektur, routing, model, controller, komponen frontend, skema database, dan daftar test.

## Note

- kode di atas dijalankan di lokal karena Infinityfree tidak memiliki akses SSH/CLI.
