# Struktur Proyek Godang

Dokumen ini menjelaskan kondisi **project saat ini** (per November 2026): arsitektur, routing, model, alur media dan komentar, frontend, pengujian, serta cara deploy. Isinya diambil langsung dari kode, konfigurasi, dan hasil verifikasi di repository ini.

> Dokumen ini bukan tutorial Laravel. Tujuannya menjadi peta cepat bagi siapa pun yang akan mengubah project.

## Daftar Isi

1. [Ringkasan Proyek](#1-ringkasan-proyek)
2. [Stack dan Versi](#2-stack-dan-versi)
3. [Peta Direktori](#3-peta-direktori)
4. [Routing](#4-routing)
5. [Controller dan Tanggung Jawabnya](#5-controller-dan-tanggung-jawabnya)
6. [Model dan Skema Database](#6-model-dan-skema-database)
7. [Pipeline Media](#7-pipeline-media)
8. [Komentar dan Notifikasi](#8-komentar-dan-notifikasi)
9. [Autentikasi, Otorisasi, dan Keamanan](#9-autentikasi-otorisasi-dan-keamanan)
10. [Pengaturan Situs](#10-pengaturan-situs)
11. [Frontend Publik](#11-frontend-publik)
12. [Admin SPA](#12-admin-spa)
13. [SEO dan Feed](#13-seo-dan-feed)
14. [Artisan Command dan Scheduler](#14-artisan-command-dan-scheduler)
15. [Seeder dan Factory](#15-seeder-dan-factory)
16. [Testing](#16-testing)
17. [Environment dan Deployment](#17-environment-dan-deployment)
18. [Konvensi Pengembangan](#18-konvensi-pengembangan)
19. [Checklist Perubahan](#19-checklist-perubahan)
20. [Ide dan Pertanyaan Terbuka](#20-ide-dan-pertanyaan-terbuka)
21. [Lampiran: Hasil Verifikasi](#21-lampiran-hasil-verifikasi)

---

## 1. Ringkasan Proyek

Godang adalah blog teknis pribadi berbahasa Indonesia dengan dua sisi:

- **Situs publik** berbasis Blade: landing page, daftar artikel, detail artikel, kategori, tag, halaman about, komentar, RSS, sitemap, dan robots.
- **Admin SPA** berbasis React: dashboard, CRUD artikel, moderasi komentar, kategori, tag, media library, tempat sampah, manajemen pengguna admin, dan pengaturan situs.

Backend Laravel menyediakan halaman publik sekaligus API JSON. Autentikasi admin memakai session cookie (Sanctum stateful), bukan token yang disimpan di localStorage.

Karakteristik utama:

- Satu bahasa publik: Indonesia (`APP_LOCALE=id`).
- Artikel mendukung `draft`, `published`, dan `scheduled`.
- Artikel memakai soft delete; media juga memakai soft delete.
- Upload gambar otomatis dikonversi ke WebP dan dibuatkan thumbnail.
- Komentar mendukung balasan dua tingkat, moderasi, honeypot, dan notifikasi email.
- Tema gelap menjadi default, dengan toggle terang/gelap.
- Deploy produksi memakai FTP ke InfinityFree melalui GitHub Actions.

---

## 2. Stack dan Versi

### Backend

| Komponen | Versi terpasang / requirement |
|---|---|
| PHP | 8.5.12 (runtime), `composer.json` mensyaratkan `^8.4.1` |
| Laravel Framework | 13.37.0 (`^13.17`) |
| Laravel Sanctum | `^4.0` |
| Intervention Image | `^4.4` (driver GD) |
| Laravel Tinker | `^3.0` |
| PHPUnit | `^12.5.12` |
| Laravel Pint | `^1.27` |
| Composer | 2.11.1 |
| Database lokal | MySQL, database `blog` |
| Database test | SQLite in-memory |

### Frontend

| Komponen | Versi |
|---|---|
| React | `^19.3.1` |
| React DOM | `^19.3.1` |
| React Router DOM | `^7.18.4` |
| TanStack React Query | `^5.104.4` |
| Zustand | `^5.0.15` |
| Axios | `^1.20.0` |
| React Hook Form | `^7.90.0` |
| Zod | `^4.6.7` |
| Highlight.js | `^11.12.0` |
| Tailwind CSS | `^4.0.0` |
| Vite | `^8.0.0` |
| Node.js | 26.13.0 |
| npm | 12.2.2 |

### Catatan versi

`README.md` sudah disinkronkan dengan runtime saat ini: **PHP 8.5.12**, **Laravel 13.37.0**, **Composer 2.11.1**, **Node.js 26.13.0**, dan **npm 12.2.2**. Jika versi berubah, perbarui kedua dokumen bersamaan.

`composer.json` masih mensyaratkan PHP `^8.4.1`, sehingga project tetap bisa dijalankan di PHP 8.4 meskipun mesin pengembangan memakai 8.5.

---

## 3. Peta Direktori

```
godang/
├── app/
│   ├── Console/Commands/          # 3 command kustom
│   ├── Http/
│   │   ├── Controllers/           # controller publik + API
│   │   │   ├── Api/               # API publik dan auth
│   │   │   └── Api/Admin/         # API admin
│   │   └── Middleware/            # EnsureUserIsAdmin, ShareSiteSettings
│   ├── Models/                    # 8 model Eloquent
│   ├── Notifications/             # 2 notifikasi email
│   ├── Providers/AppServiceProvider.php
│   └── Rules/Turnstile.php        # validasi Cloudflare Turnstile
├── bootstrap/app.php              # routing, middleware, exception config
├── config/                        # 11 file konfigurasi Laravel
├── database/
│   ├── factories/                 # 7 factory
│   ├── migrations/                # 11 migration
│   └── seeders/                   # 7 seeder (1 tidak dipanggil)
├── public/
│   ├── build/                     # hasil `npm run build` (di-track git)
│   ├── storage/                   # junction ke storage/app/public
│   └── .htaccess                  # front controller rewrite
├── resources/
│   ├── css/                       # Tailwind entry + tema highlight.js
│   ├── js/
│   │   ├── app.js                 # JS situs publik
│   │   └── admin/                 # React admin SPA
│   ├── lang/                      # lokasi language file Laravel 13
│   └── views/                     # Blade publik, partial, SEO, error
├── routes/
│   ├── web.php                    # halaman publik, admin shell, SEO
│   ├── api.php                    # API publik, auth, admin
│   └── console.php                # command `inspire` + scheduler
├── storage/app/public/            # media: uploads, cover, avatar, files
├── tests/
│   ├── Feature/                   # 22 feature test
│   ├── Unit/                      # 1 unit test
│   └── TestCase.php
├── .github/workflows/infinityfree.yml
├── .htaccess                      # rewrite root ke public/
├── composer.json
├── package.json
├── phpunit.xml
├── vite.config.js
└── STRUCTURE.md
```

### Yang di-track dan yang tidak

- `vendor/` **di-track** (sekitar 9.930 file). Baris `#/vendor` di `.gitignore` memang dikomentari agar hasil `composer install` ikut terkirim lewat FTP, karena InfinityFree tidak menyediakan Composer.
- `public/build/` **di-track** (51 file) dengan alasan yang sama: server InfinityFree tidak menjalankan build, jadi asset hasil `npm run build` harus sudah ada di repository.
- `tests/` di-track (24 file).
- `.env`, `node_modules/`, `public/hot`, `public/storage`, dan log tidak di-track.
- `public/storage` ada di disk lokal sebagai junction/symlink ke `storage/app/public`, tetapi tidak masuk git.

---

## 4. Routing

Total ada **71 route aplikasi** di `routes/web.php` dan `routes/api.php` (di luar route health `/up` dan route vendor). Semua route aplikasi memakai controller atau closure yang terdaftar eksplisit.

### 4.1 Route web publik

| Method | URI | Name | Handler |
|---|---|---|---|
| GET | `/` | `home` | `PublicController@landing` |
| GET | `/blog` | `blog.index` | `PublicController@blogIndex` |
| GET | `/blog/{slug}` | `blog.show` | `PublicController@blogShow` |
| GET | `/about` | `about` | `PublicController@about` |
| GET | `/category/{slug}` | `category.show` | `PublicController@category` |
| GET | `/tag/{slug}` | `tag.show` | `PublicController@tag` |

Semua route di atas memakai middleware `ShareSiteSettings` agar data situs tersedia di view.

### 4.2 Admin shell (React)

| Method | URI | Name | Middleware |
|---|---|---|---|
| GET | `/admin/login` | `admin.login` | `ShareSiteSettings` |
| GET | `/admin/reset-password` | `password.reset` | `ShareSiteSettings` |
| GET | `/admin/{path?}` | `admin` | `auth`, `admin`, `ShareSiteSettings` |

Tiga route ini mengembalikan view `admin` yang sama. React Router yang menentukan halaman berdasarkan URL. Guest yang membuka `/admin/*` akan diarahkan ke `/admin/login` oleh konfigurasi `redirectGuestsTo`.

### 4.3 Route utilitas dan SEO

| Method | URI | Name | Keterangan |
|---|---|---|---|
| GET | `/storage/{path}` | `storage.file` | Fallback penyajian file jika symlink `public/storage` tidak tersedia |
| GET | `/sitemap.xml` | `seo.sitemap` | Sitemap dinamis |
| GET | `/robots.txt` | `seo.robots` | Robots dinamis |
| GET | `/feed.xml` | `seo.feed` | RSS 20 artikel terbaru |
| GET | `/{legacyLocale}/{path?}` | `legacy.locale` | Redirect 301 `/id/*` dan `/en/*` ke path tanpa locale |

Route legacy didaftarkan paling akhir agar tidak menangkap route nyata. Locale selain `id` dan `en` menghasilkan 404.

### 4.4 API publik

| Method | URI | Name | Keterangan |
|---|---|---|---|
| GET | `/api/posts` | `api.posts.index` | Filter `q`, `category`, `tag`, `per_page` (1–50, default 12) |
| GET | `/api/posts/{slug}` | `api.posts.show` | Detail artikel berdasarkan slug |
| GET | `/api/posts/{post}/comments` | `api.comments.index` | Komentar approved, hanya untuk artikel published |
| POST | `/api/posts/{post}/comments` | `api.comments.store` | Kirim komentar, throttle 6/menit |
| GET | `/api/categories` | `api.categories.index` | Daftar kategori |
| GET | `/api/tags` | `api.tags.index` | Daftar tag |
| GET | `/api/site/settings` | `api.site.settings` | Subset setting untuk branding login |

### 4.5 API autentikasi

| Method | URI | Name | Middleware |
|---|---|---|---|
| POST | `/api/auth/login` | `api.auth.login` | `throttle:login` (5/menit per email+IP) |
| POST | `/api/auth/forgot-password` | `api.auth.forgot-password` | `throttle:forgot-password` (3/menit per email+IP, 10/menit per IP) |
| POST | `/api/auth/reset-password` | `api.auth.reset-password` | — |
| POST | `/api/auth/logout` | `api.auth.logout` | `auth:sanctum` |
| GET | `/api/auth/me` | `api.auth.me` | `auth:sanctum` |

### 4.6 API admin

Semua route di bawah memakai prefix `/api/admin`, name prefix `api.admin.`, dan middleware `auth:sanctum` + `admin`.

| Area | Route |
|---|---|
| Dashboard | `GET /dashboard` |
| Artikel | `apiResource /posts` (index, store, show, update, destroy), `POST /posts/bulk-delete`, `POST /posts/{post}/restore` |
| Kategori | `apiResource /categories` |
| Tag | `apiResource /tags` |
| Komentar | `GET\|POST /comments`, `PATCH /comments/{comment}` (approve), `PUT /comments/{comment}` (edit), `PATCH /comments/{comment}/spam`, `POST /comments/{comment}/reply`, `DELETE /comments/{comment}`, `POST /comments/bulk-delete` |
| Media | `GET\|POST /media`, `PATCH /media/{media}`, `DELETE /media/{media}`, `POST /media/{media}/restore`, `DELETE /media/{media}/force`, `POST /media/bulk-delete`, `POST /media/bulk-restore`, `POST /media/bulk-force-delete` |
| Kategori media | `GET\|POST /media-categories`, `PATCH\|DELETE /media-categories/{mediaCategory}` |
| Pengguna | `apiResource /users` (index, store, update, destroy; tanpa `show`) |
| Pengaturan | `GET /settings`, `PUT /settings` |

Catatan urutan route:

- `posts/bulk-delete` dan `posts/{post}/restore` didaftarkan sebelum `apiResource` agar tidak dianggap sebagai `{post}`.
- Route bulk media didaftarkan sebelum route `{media}`.
- `apiResource('posts')` memakai `withTrashed()` untuk `show`, `update`, dan `destroy`, sehingga admin tetap bisa mengelola artikel di tempat sampah.
- Route `restore` dan `force` memakai `withTrashed()`.

### 4.7 Route console

`routes/console.php` mendaftarkan:

- `inspire` — command bawaan Laravel.
- `Schedule::command('posts:publish-scheduled')->everyMinute();`

Agar scheduler berjalan di server, cron harus memanggil `php artisan schedule:run` setiap menit. Ini perlu dicek pada hosting InfinityFree (lihat bagian 20.2).

### 4.8 Alur request end-to-end

**Halaman artikel publik (`/blog/{slug}`)**

```
Browser
  -> GET /blog/{slug}
  -> middleware ShareSiteSettings
       -> Setting::allValues() lalu view()->share('site', $site)
  -> PublicController@blogShow($slug)
       -> Post::published()->where('slug', ...)->firstOrFail()
       -> increment views_count
       -> komentar approved (root + replies)
       -> 2 artikel terkait (utamakan kategori sama)
  -> resources/views/public/blog/show.blade.php
  -> layouts/public.blade.php (meta, OG, hreflang, JSON-LD, navbar, footer)
  -> HTML + aset Vite (app.css, app.js)
```

Setelah halaman tampil: toggle tema memakai `localStorage`, highlight kode dan tombol salin berjalan dari `resources/js/app.js`, dan form komentar mengirim `fetch` ke `/api/posts/{id}/comments`. Admin yang login melihat badge **Mode Admin**, tidak perlu mengisi nama/email, dan mendapat tombol **Spam**/**Hapus** pada tiap komentar.

**Admin SPA (`/admin/posts/5/edit`)**

```
Browser
  -> GET /admin/posts/5/edit
  -> route web GET /admin/{path?} (middleware: auth, admin, ShareSiteSettings)
       - belum login -> redirect /admin/login
       - bukan admin -> 403
       - ShareSiteSettings menyiapkan $site untuk logo/nama situs di shell
  -> resources/views/admin.blade.php (shell + @vite)
  -> React mount, BrowserRouter membaca /admin/posts/5/edit
  -> AuthGate memanggil GET /api/auth/me
  -> PostForm memanggil:
       GET /api/admin/posts/5
       GET /api/admin/categories
       GET /api/admin/tags
       GET /api/admin/media
       GET /api/admin/media-categories
```

Semua request API memakai cookie sesi yang sama (Sanctum stateful), bukan bearer token.

**API publik (`/api/posts`)**

```
GET /api/posts?q=laravel&category=laravel&tag=eloquent&per_page=12
  -> Api\PostController@index
  -> Post::published() + filter + paginate (maks. 50)
  -> JSON paginator Laravel
```

**Konten situs dari Settings**

```
Admin
  -> /admin/settings (React, 3 tab: Umum, Profil & About, Komentar)
  -> PUT /api/admin/settings
  -> SettingController@update (whitelist + validasi per key)
  -> tabel settings (key-value)

Pembaca
  -> GET halaman publik
  -> middleware ShareSiteSettings
       -> Setting::DEFAULTS + nilai DB = $settings
       -> view()->share('site', $site)
  -> Blade membaca $site[...] (judul, deskripsi, logo, About, sosial, avatar)

Shell admin
  -> GET /admin/login atau /admin/{path?}
  -> middleware ShareSiteSettings yang sama
  -> admin.blade.php memakai $site untuk logo/nama situs sebelum React mount
```

Perubahan settings langsung berlaku pada request publik berikutnya; tidak ada cache tambahan. Logo situs dan avatar penulis hanya menyimpan path media, jadi tidak ada penyalinan file saat settings disimpan.

---

## 5. Controller dan Tanggung Jawabnya

Semua controller berada di `app/Http/Controllers`. Tidak ada service layer atau repository layer; logika bisnis berada di controller, model, dan command. Untuk ukuran project ini, pola tersebut konsisten dan mudah dilacak.

### 5.1 Controller publik

| File | Tanggung jawab |
|---|---|
| `PublicController.php` | Landing (6 artikel terbaru + hitungan artikel published per kategori), daftar blog dengan filter `q`/`category`/`tag` dan pagination 12, detail artikel (increment `views_count`, komentar approved berbentuk thread, 2 artikel terkait dari kategori yang sama), about, kategori, dan tag. |
| `SeoController.php` | `sitemap.xml` (home, blog, about, semua artikel published, kategori, tag), `robots.txt` (disallow `/api/` dan `/admin`, mencantumkan sitemap), `feed.xml` (20 artikel published terbaru). |
| `StorageFileController.php` | Fallback `/storage/{path}`. Hanya menerima path dengan pola `uploads\|cover\|avatar\|files` + nama file aman, memastikan file ada, mengirim cache immutable 1 tahun, dan `Content-Disposition: inline` untuk folder `files`. |

### 5.2 Controller API publik

| File | Tanggung jawab |
|---|---|
| `Api/PostController.php` | Daftar artikel published dengan filter dan pagination 1–50 (default 12); detail berdasarkan slug beserta kategori, tag, dan author. |
| `Api/CommentController.php` | Daftar komentar approved per artikel; simpan komentar dengan honeypot, batas 2 link, threading 2 level, moderasi, notifikasi admin, dan notifikasi balasan. |
| `Api/CategoryController.php` | Daftar kategori dengan `posts_count` hanya dari artikel published. |
| `Api/TagController.php` | Daftar tag dengan `posts_count` hanya dari artikel published. |
| `Api/SiteController.php` | Subset setting publik: judul dan logo terang/gelap untuk branding halaman login. |
| `Api/AuthController.php` | Login admin (dengan limiter `login`), logout, `me`, forgot password (dengan limiter `forgot-password`), reset password. |

### 5.3 Controller API admin

| File | Tanggung jawab |
|---|---|
| `Api/Admin/DashboardController.php` | Statistik artikel (total, published, draft, scheduled), total views, komentar pending/total, 5 artikel terakhir diperbarui, 5 komentar terbaru. |
| `Api/Admin/PostController.php` | CRUD artikel termasuk filter status/pencarian/trashed, slug unik (termasuk baris soft-deleted), auto `published_at`, sinkronisasi tag (ID, nama, atau objek), bulk delete, force delete, dan restore. |
| `Api/Admin/CategoryController.php` | CRUD kategori, slug otomatis, hitungan artikel. |
| `Api/Admin/TagController.php` | CRUD tag, slug otomatis, hitungan artikel. |
| `Api/Admin/CommentController.php` | Daftar komentar dengan filter status/post/kategori, buat komentar sebagai admin, edit body, approve, spam (beserta balasan), reply, hapus (beserta balasan), bulk delete. |
| `Api/Admin/MediaController.php` | Media library: filter, sort, pencarian, kategori, folder, status trashed/unused, upload gambar dan file, update metadata, soft delete, restore, force delete dengan proteksi usage, serta bulk operation. |
| `Api/Admin/MediaCategoryController.php` | CRUD kategori media; menghapus kategori membuat media terkait menjadi uncategorized (`nullOnDelete`). |
| `Api/Admin/UserController.php` | CRUD akun admin: daftar admin beserta jumlah artikel, tambah admin (`is_admin = true`), edit nama/email dengan password opsional, dan hapus dengan pengaman (tidak boleh menghapus diri sendiri, minimal satu admin tersisa, admin yang masih punya artikel termasuk di Trash tidak boleh dihapus). Akun non-admin menghasilkan 404. |
| `Api/Admin/SettingController.php` | Baca semua setting dan update batch dengan whitelist key, validasi URL, boolean, dan panjang maksimum. |

### 5.4 Middleware

| File | Tanggung jawab |
|---|---|
| `EnsureUserIsAdmin.php` | Alias `admin`. Menolak user yang bukan `is_admin` dengan 403 "Akses admin diperlukan." |
| `ShareSiteSettings.php` | Menggabungkan setting database dengan default, membentuk array `$site`, dan membagikannya ke semua view. Dipakai di route publik, halaman admin, dan feed. |

`bootstrap/app.php` juga mengatur:

- `statefulApi()` agar SPA memakai cookie session Sanctum.
- `redirectGuestsTo('/admin/login')`.
- Render JSON untuk request `api/*` atau yang meminta JSON.
- Route health di `/up`.

---

## 6. Model dan Skema Database

### 6.1 Daftar model

| Model | Tabel | Fitur penting |
|---|---|---|
| `User` | `users` | `HasApiTokens`, `Notifiable`, `CanResetPassword`; field `avatar`, `bio`, `is_admin`; relasi `posts`. |
| `Post` | `posts` | Soft delete; auto slug dan reading time; scope `published`; accessor `cover_url`, `og_url`; relasi author, category, tags, comments. |
| `Category` | `categories` | Relasi `posts`. |
| `Tag` | `tags` | Relasi `posts` (belongsToMany). |
| `Comment` | `comments` | Threading via `parent_id`; scope `approved`, `pending`, `rootComments`; mutator `body` menyimpan teks polos; email/IP/user-agent disembunyikan; nama author mengikuti setting. |
| `Media` | `media` | Soft delete; accessor `url`, `thumb_url`, `download_name`; normalisasi path legacy; scope `images`, `files`; relasi `category`. |
| `MediaCategory` | `media_categories` | Relasi `media`. |
| `Setting` | `settings` | 23 key default; helper `getValue`, `setValue`, `allValues`, `authorName`. |

Model memakai atribut PHP 8 milik Laravel 13: `#[Fillable([...])]`, `#[Hidden([...])]`, dan `#[Scope]`, bukan properti `$fillable`/`$hidden` atau anotasi `scopePublished`.

### 6.2 Skema tabel

#### `users`

| Kolom | Tipe / catatan |
|---|---|
| `id` | bigint primary key |
| `name` | string |
| `email` | string, unique |
| `avatar` | string, nullable |
| `bio` | text, nullable |
| `email_verified_at` | timestamp, nullable |
| `password` | string (cast `hashed`) |
| `is_admin` | boolean, default `false` |
| `remember_token` | string, nullable |
| `timestamps` | created_at, updated_at |

Tabel bawaan lain: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`.

#### `categories`

`id`, `name` (default string kosong), `slug` (unique), `description` (text, nullable), `timestamps`.

#### `tags`

`id`, `name` (default string kosong), `slug` (unique), `timestamps`.

#### `posts`

| Kolom | Tipe / catatan |
|---|---|
| `id` | bigint primary key |
| `user_id` | FK `users`, cascade on delete |
| `category_id` | FK `categories`, nullable, null on delete |
| `title` | string |
| `slug` | string, unique |
| `excerpt` | text, nullable |
| `content` | longText, nullable (HTML mentah) |
| `cover_image` | string, nullable |
| `og_image` | string, nullable |
| `status` | string, default `draft` (`draft`, `published`, `scheduled`) |
| `published_at` | timestamp, nullable |
| `meta_title` | string, nullable |
| `meta_description` | text, nullable |
| `reading_time` | unsignedSmallInteger, default 0 (menit) |
| `views_count` | unsignedInteger, default 0 |
| `deleted_at` | timestamp, nullable |
| `timestamps` | created_at, updated_at |

Index: `['status', 'published_at']`.

#### `comments`

| Kolom | Tipe / catatan |
|---|---|
| `id` | bigint primary key |
| `post_id` | FK `posts`, cascade on delete |
| `parent_id` | FK `comments`, nullable, cascade on delete |
| `reply_to_name` | string, nullable |
| `name` | string |
| `email` | string |
| `body` | text |
| `status` | string, default `pending` (`pending`, `approved`, `spam`) |
| `is_author` | boolean, default `false` |
| `notify_replies` | boolean, default `false` |
| `ip_hash` | string, nullable (SHA-256) |
| `user_agent` | string, nullable |
| `timestamps` | created_at, updated_at |

Index: `['post_id', 'status']`.

#### `media_categories`

`id`, `name` (unique), `timestamps`.

#### `media`

| Kolom | Tipe / catatan |
|---|---|
| `id` | bigint primary key |
| `path` | string, path relatif terhadap disk `public` |
| `type` | string(10), default `image` (`image`, `file`) |
| `original_name` | string, nullable |
| `thumb_path` | string, nullable |
| `folder` | string, default `uploads` (`uploads`, `cover`, `avatar`, `files`) |
| `media_category_id` | FK `media_categories`, nullable, null on delete |
| `alt_text` | string, nullable |
| `mime` | string(50), nullable |
| `size` | unsignedInteger, default 0 (byte) |
| `width` | unsignedSmallInteger, nullable |
| `height` | unsignedSmallInteger, nullable |
| `deleted_at` | timestamp, nullable |
| `timestamps` | created_at, updated_at |

Index: `folder`, dan `['type', 'deleted_at']`.

#### `settings`

`id`, `key` (unique), `value` (text, nullable), `timestamps`.

#### `post_tag`

`post_id` + `tag_id`, composite primary key, keduanya cascade on delete.

### 6.3 Perilaku model yang perlu diingat

- **Slug artikel** dibuat otomatis dari judul saat `saving` jika kosong. Controller admin tetap memakai `uniqueSlug()` agar bentrok dengan artikel soft-deleted tidak terjadi.
- **Reading time** dihitung ulang setiap kali `content` tidak kosong: `max(1, ceil(jumlah kata / 200))`.
- **`Post::published()`** berarti `status = published`, `published_at` tidak null, dan `published_at <= now()`. Artikel `scheduled` yang waktunya sudah lewat belum otomatis tampil sampai command scheduler mengubah statusnya.
- **Soft delete artikel** tidak menghapus komentar. Komentar baru ikut hilang jika artikel di-force-delete (FK cascade).
- **`Comment` name accessor** menampilkan `author_name` dari setting untuk komentar yang `is_author = true`.
- **`Comment` body mutator** membuang blok `<script>`/`<style>`, seluruh tag HTML, menormalkan baris baru, dan `trim()`. Body selalu tersimpan sebagai teks polos, termasuk komentar yang dibuat dari panel admin.
- **`Media::diskPath()`** menerima path relatif, path berawalan `storage/`, atau URL absolut yang mengandung `/storage/`, lalu mengembalikan path relatif disk `public`. URL eksternal non-storage dikembalikan apa adanya oleh `urlFor()`.
- **`Media::thumbnailPathFor()`** mengubah `folder/name.ext` menjadi `folder/name-thumb.webp`.

### 6.4 Siklus hidup artikel

| Tahap | Perilaku |
|---|---|
| **Draft** | `status = draft`, tidak tampil di publik, `published_at` boleh null. |
| **Scheduled** | `status = scheduled` dengan `published_at` di masa depan. Belum tampil sampai command `posts:publish-scheduled` mengubahnya menjadi `published`. |
| **Published** | `status = published` **dan** `published_at <= now()`. Scope `Post::published()` dipakai konsisten di halaman publik, API, sitemap, RSS, dan dashboard admin. |
| **Soft delete** | `deleted_at` diisi. Artikel hilang dari publik, tetapi masih muncul di `/admin/posts/trash` dan bisa di-restore. Komentar tidak ikut terhapus. |
| **Force delete** | Baris artikel dihapus permanen; komentar ikut terhapus karena FK `cascadeOnDelete`. Hanya tersedia dari Trash. |

Detail pembuatan dan perubahan:

- Saat `store`, `user_id` diisi dari admin yang login, slug dibuat otomatis jika kosong, dan `published_at` diisi `now()` bila status `published` tetapi tanggalnya kosong.
- Saat `update`, `published_at` hanya diisi otomatis bila artikel belum pernah punya tanggal terbit.
- `uniqueSlug()` mengecek juga artikel soft-deleted agar slug tidak bentrok saat restore.
- `views_count` naik setiap halaman artikel dibuka lewat `PublicController@blogShow`.
- `reading_time` dihitung ulang setiap `content` berubah (200 kata per menit, minimal 1).

---

## 7. Pipeline Media

Media selalu disimpan di disk **`public`** (`storage/app/public`), terlepas dari `FILESYSTEM_DISK` yang default-nya `local`. Alasannya: file harus bisa diakses browser melalui `/storage/...`.

### 7.1 Folder dan URL

| Folder | Isi |
|---|---|
| `uploads` | Gambar umum dari media library |
| `cover` | Cover artikel |
| `avatar` | Avatar penulis |
| `files` | Dokumen non-gambar |

URL file dibentuk oleh `Storage::disk('public')->url($path)`, yang menghasilkan `APP_URL/storage/{path}`. Di lokal, `public/storage` adalah junction ke `storage/app/public`. Jika symlink tidak tersedia di hosting, route fallback `/storage/{path}` tetap menyajikan file.

### 7.2 Upload gambar

Validasi dan proses di `Api/Admin/MediaController::storeImage()`:

- Maksimum **5 MB**.
- Harus berupa gambar dengan ekstensi yang didukung GD. Daftar ekstensi dihitung dari `imagetypes()`, sehingga AVIF/WebP hanya diterima jika GD server mendukungnya. Fallback: `jpg`, `jpeg`, `png`.
- File dibaca sebagai binary, lalu didecode dengan Intervention Image driver GD.
- Ukuran diperkecil hingga maksimum **1600 px** (sisi terpanjang).
- Disimpan sebagai **WebP** dengan nama UUID: `{folder}/{uuid}.webp`.
- Thumbnail dibuat terpisah dari byte asli, maksimum **480 px**, disimpan sebagai `{folder}/{uuid}-thumb.webp`.
- Metadata `mime`, `size`, `width`, `height`, dan `alt_text` disimpan ke tabel `media`. `alt_text` default ke nama file asli.
- Jika pemrosesan gagal, file yang sudah terlanjur ditulis dihapus dan API mengembalikan 422.

### 7.3 Upload file non-gambar

- Maksimum **10 MB**.
- Ekstensi yang diizinkan: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `txt`, `csv`, `zip`.
- Validasi memakai `mimes` **dan** `mimetypes`, lalu dicek ulang bahwa MIME cocok dengan ekstensi. File seperti PHP, HTML, SVG, JS, atau executable ditolak.
- Disimpan di `files/{uuid}.{ext}` tanpa thumbnail.
- Respons `/storage/files/...` memakai `Content-Disposition: inline`.

### 7.4 Media library

`GET /api/admin/media` mendukung:

- `type=image|file` (default `image`).
- `q` untuk mencari `alt_text`, `original_name`, dan `path`.
- `folder=uploads|cover|avatar|files|other`.
- `category={id}|none`.
- `sort=newest|oldest|name|name_desc|largest|smallest`.
- `trashed=1` untuk tempat sampah.
- `unused=1` untuk media yang tidak direferensikan.
- `per_page` 1–50 (default 20).

Respons juga menyertakan hitungan per folder, hitungan per kategori, jumlah uncategorized, dan jumlah unused. Setiap item mendapat `usage_count` dan `usage_labels`.

### 7.5 Deteksi pemakaian dan penghapusan

Media dianggap terpakai jika path-nya muncul di:

- `posts.cover_image`
- `posts.og_image`
- `posts.content` (termasuk artikel soft-deleted)
- `settings.author_avatar`
- `settings.site_logo`
- `settings.site_logo_dark`

Cara pencocokannya **persis (exact match)**, bukan sekadar `LIKE`:

1. `referencedUsage()` mengambil semua artikel (termasuk yang di Trash) dengan `chunkById(200)` dan mengekstrak path media dari `cover_image`, `og_image`, dan `content`.
2. Untuk `content`, URL absolut `{APP_URL}/storage/...` dan prefix legacy `storage/` dinormalkan menjadi path disk (`uploads/...`, `cover/...`, `avatar/...`, `files/...`), lalu diekstrak dengan regex berbatas agar `uploads/a.webp` tidak cocok dengan `uploads/a.webp.bak`.
3. Hasilnya berupa map `path => { count, labels }` yang di-cache per request (`$usageCache`), lalu dipakai untuk mengisi `usage_count` dan `usage_labels` di respons media.
4. Filter `unused=1` memakai `whereNotIn('path', usedPathVariants())`, dengan varian `path`, `storage/{path}`, `/storage/{path}`, dan `{APP_URL}/storage/{path}` agar baris media legacy tetap terdeteksi.

Konsekuensinya:

- Tidak ada false positive dari substring yang kebetulan mirip.
- Pencarian referensi dilakukan di PHP setelah data diambil, jadi biayanya tumbuh seiring jumlah artikel. Untuk skala blog ini masih wajar; jika artikel sudah sangat banyak, pertimbangkan tabel relasi eksplisit.

Aturan hapus:

- **Soft delete** memindahkan baris ke tempat sampah; file tetap ada di disk.
- **Force delete** ditolak dengan **409** jika media masih dipakai. Pesan menyertakan daftar referensi.
- **Bulk force delete** juga ditolak seluruhnya jika ada satu item yang masih dipakai.
- Saat force delete berhasil, file asli dan thumbnail dihapus dari disk.

### 7.6 Command media

| Command | Fungsi |
|---|---|
| `php artisan media:backfill` | Menormalkan path legacy (`storage/...` atau URL absolut) pada post dan media, mendaftarkan file cover yatim, dan opsional `--prune-missing` untuk membersihkan referensi yang filenya hilang. |
| `php artisan media:thumbnails` | Membuat thumbnail WebP untuk media lama. Opsi `--force` untuk regenerasi. |

---

## 8. Komentar dan Notifikasi

### 8.1 Alur komentar publik

Endpoint: `POST /api/posts/{post}/comments` (throttle 6/menit).

1. Artikel harus published, jika tidak → 404.
2. Honeypot: field `website` yang terisi dianggap spam → 422.
3. Validasi:
   - `body` wajib, maksimum 2000 karakter.
   - `parent_id` opsional dan harus ada.
   - `notify_replies` opsional boolean.
   - Untuk non-admin: `name` wajib (maks 100) dan `email` wajib (maks 255).
4. Body ditolak jika mengandung lebih dari **2 link** (`http://` atau `https://`).
5. **Sanitasi:** body disimpan sebagai **teks polos**. Mutator `Comment::body()` membuang blok `<script>`/`<style>` beserta isinya, lalu `strip_tags()`, menormalkan CRLF menjadi `\n`, merapatkan baris kosong berlebih, dan `trim()`. Dengan begitu tidak ada markup yang tersimpan di database.
6. Threading dibatasi **2 level**:
   - Balasan ke komentar root menjadi anak root.
   - Balasan ke balasan tetap ditempel di bawah root yang sama, dengan `reply_to_name` diisi nama yang dibalas.
7. Status:
   - Admin → langsung `approved`, `is_author = true`.
   - Non-admin + `comment_moderation=true` → `pending`.
   - Non-admin + moderasi mati → `approved`.
8. Metadata: `ip_hash` = SHA-256 IP, `user_agent` disimpan.
9. Notifikasi:
   - Admin mendapat `NewCommentNotification` jika `comment_notification=true`.
   - Komentar induk dengan `notify_replies=true` mendapat `ReplyToCommentNotification`.
   - Kegagalan email diabaikan agar komentar tetap tersimpan.

### 8.2 Tampilan komentar

- Halaman artikel hanya memuat komentar `approved`.
- Root comment diurutkan terbaru; balasan diurutkan terbaru.
- Daftar komentar **tertutup secara default** dan bisa dibuka dengan tombol.
- Pengunjung bisa mengurutkan **Terbaru / Terlama** di sisi klien.
- Form komentar dikirim via `fetch`, lalu komentar baru langsung ditambahkan ke DOM.
- Komentar baru di DOM dibangun dengan helper `escapeHtml()` untuk nama, body, dan `reply_to_name`, sehingga meskipun body disimpan sebagai teks polos, karakter seperti `<`, `>`, `&`, dan kutip tetap tidak bisa menyuntikkan markup.
- Admin yang sedang login melihat badge "Mode Admin", tidak perlu mengisi nama/email, dan mendapat tombol **Spam** serta **Hapus** langsung di halaman artikel. Khusus komentar milik penulis (`is_author = true`), tersedia tombol **Edit** yang mengubah body secara inline lewat `PUT /api/admin/comments/{comment}`.

### 8.3 Moderasi admin

| Aksi | Endpoint | Perilaku |
|---|---|---|
| Daftar | `GET /api/admin/comments` | Filter `status`, `post_id`, `category_id`; default hanya root comment; email di-`makeVisible` untuk admin. |
| Buat | `POST /api/admin/comments` | Komentar root sebagai author, langsung approved. |
| Edit | `PUT /api/admin/comments/{comment}` | Mengubah body. |
| Approve | `PATCH /api/admin/comments/{comment}` | Status menjadi approved. |
| Spam | `PATCH /api/admin/comments/{comment}/spam` | Menandai komentar dan semua balasannya sebagai spam. |
| Reply | `POST /api/admin/comments/{comment}/reply` | Balasan author, approved, ditempel di root yang sama. |
| Hapus | `DELETE /api/admin/comments/{comment}` | Menghapus komentar beserta balasannya. |
| Bulk delete | `POST /api/admin/comments/bulk-delete` | Menghapus balasan lebih dulu, lalu komentar induk. |

### 8.4 Notifikasi email

Keduanya `ShouldQueue` dan memakai channel `mail`:

- `NewCommentNotification` — subjek "Komentar baru pada ...", tautan ke `/admin/comments`.
- `ReplyToCommentNotification` — subjek "Ada balasan untuk komentar Anda ...", tautan ke `#comment-{id}`.

`.env.example` memakai `QUEUE_CONNECTION=database`, sehingga email masuk ke tabel `jobs` dan dikirim oleh queue worker. Di lokal `MAIL_MAILER=log`, jadi isi email masuk ke log. Lihat bagian 17 untuk kebutuhan worker di produksi.

---

## 9. Autentikasi, Otorisasi, dan Keamanan

### 9.1 Login admin

- Halaman: `/admin/login` (React).
- Endpoint: `POST /api/auth/login`.
- `Auth::attempt()` mencocokkan `email`, `password`, dan `is_admin = true`.
- Session di-regenerate setelah login.
- `bootstrap/app.php` memanggil `statefulApi()`, sehingga SPA memakai cookie session + CSRF, bukan bearer token.
- Axios di frontend mengirim `X-XSRF-TOKEN` otomatis melalui `withXSRFToken: true`.
- Logout memakai guard `web`, meng-invalidate session, dan regenerate token.

### 9.2 Otorisasi

- Route admin API memakai `auth:sanctum` + alias `admin`.
- Route halaman `/admin/{path?}` memakai `auth` + `admin`.
- `EnsureUserIsAdmin` mengecek `$request->user()?->is_admin`; jika gagal → 403.
- Guest diarahkan ke `/admin/login`.
- Tidak ada role lain selain admin. Tidak ada policy atau gate terpisah.

### 9.3 Rate limiting

| Limiter | Aturan |
|---|---|
| `login` | 5 percobaan/menit per kombinasi email (lowercase, transliterated) + IP |
| `forgot-password` | 3 permintaan/menit per kombinasi email (lowercase, transliterated) + IP, **dan** 10 permintaan/menit per IP |
| Komentar | `throttle:6,1` — 6 request/menit per IP |

`forgot-password` memakai dua limit sekaligus: limit pertama mencegah satu email dibanjiri email reset, limit kedua mencegah satu IP mencoba banyak alamat email. Keduanya didefinisikan di `AppServiceProvider::boot()`.

### 9.4 Cloudflare Turnstile

- Aktif otomatis jika `TURNSTILE_SECRET_KEY` terisi.
- Rule `App\Rules\Turnstile` mengirim verifikasi ke endpoint Cloudflare dengan timeout 10 detik.
- Jika verifikasi tidak bisa diproses, login ditolak dengan pesan yang aman.
- Site key dikirim ke frontend melalui meta tag `turnstile-site-key` di layout admin.
- Test memaksa kedua key menjadi string kosong agar Turnstile nonaktif.

Menonaktifkan Turnstile: kosongkan `TURNSTILE_SITE_KEY` dan `TURNSTILE_SECRET_KEY` di `.env`. Rule hanya aktif bila secret key terisi, dan widget di frontend hanya muncul bila site key tersedia.

### 9.5 Reset password

- `POST /api/auth/forgot-password` memakai `Password::sendResetLink` dan dilindungi limiter `forgot-password` (3/menit per email+IP dan 10/menit per IP).
- `POST /api/auth/reset-password` memakai `Password::reset`, password minimal 8 karakter dan harus dikonfirmasi.
- Link reset mengarah ke `/admin/reset-password` (name `password.reset`).
- Tabel `password_reset_tokens` dan konfigurasi `config/auth.php` dipakai apa adanya.

### 9.6 Perlindungan tambahan

- Root `.htaccess` menulis ulang semua request ke `public/`, mematikan directory browsing, dan menolak `.env`, `.log`, `.htaccess`.
- `public/.htaccess` meneruskan request ke `index.php` dan menangani header Authorization serta XSRF.
- Halaman admin memakai `noindex, nofollow`.
- `Comment` menyembunyikan `email`, `ip_hash`, dan `user_agent` dari serialisasi publik.
- Body komentar disimpan sebagai teks polos (lihat 8.1), sehingga markup tidak pernah masuk database.
- Upload file memakai whitelist ekstensi + MIME, bukan hanya MIME dari client.
- Route storage membatasi path ke pola `uploads|cover|avatar|files` + satu nama file dengan karakter aman (`A-Za-z0-9._-`), sehingga traversal lintas folder tidak mungkin.
- Komentar baru yang dirender lewat JavaScript di halaman artikel memakai `escapeHtml()` sebelum dimasukkan ke `innerHTML`. Dengan dua lapis ini (teks polos di server + escaping di klien), body komentar tidak bisa menjadi vektor XSS.

### 9.7 Mengubah email dan password admin

Cara normal: login lalu buka **Pengguna** (`/admin/users`). Di sana admin bisa menambah admin baru, mengubah nama/email, dan mengganti password (kosongkan kolom password bila tidak ingin mengubahnya).

Jika terkunci di luar panel, gunakan perintah darurat:

```bash
php artisan tinker --execute 'User::where("email", "admin@godang.com")->update(["password" => Hash::make("password_baru")]);'
```

Pastikan akun yang diubah tetap `is_admin = true`, karena login hanya menerima akun admin.

---

## 10. Pengaturan Situs

Setting disimpan sebagai key-value di tabel `settings` dan digabung dengan `Setting::DEFAULTS`. Nilai boolean disimpan sebagai string `"true"`/`"false"`.

### 10.1 Daftar key (23)

| Kelompok | Key |
|---|---|
| Identitas situs | `site_title`, `site_logo`, `site_logo_dark`, `site_logo_alt`, `site_description`, `tagline` |
| SEO default | `default_meta_title`, `default_meta_description` |
| Sosial | `github_url`, `twitter_url`, `linkedin_url` |
| Komentar | `comment_moderation`, `comment_notification` |
| Penulis | `author_avatar`, `author_avatar_alt`, `author_name`, `author_role` |
| About | `about_bio`, `about_topics_heading`, `about_topics`, `about_contact_heading`, `about_contact`, `about_meta_description` |

Default penting:

- `site_title` = `Godang`
- `author_name` = `Godang`
- `author_role` = `Developer & Penulis Teknis`
- `comment_moderation` = `true`
- `comment_notification` = `true`
- `github_url` = `https://github.com` (perlu diganti dengan URL asli)
- `about_topics` = daftar topik dipisah baris baru

### 10.2 Helper

| Method | Fungsi |
|---|---|
| `Setting::getValue($key, $default)` | Ambil satu nilai, fallback ke default. |
| `Setting::setValue($key, $value)` | `updateOrCreate` berdasarkan key. |
| `Setting::allValues()` | Semua default yang ditimpa nilai database. |
| `Setting::authorName($fallback)` | Nama penulis yang dipakai untuk komentar author. |

### 10.3 Alur ke tampilan

`ShareSiteSettings` membentuk array `$site` dan `view()->share('site', $site)`. Array ini berisi nilai mentah plus:

- `author_avatar_url` — URL absolut hasil `Media::urlFor()`.
- `author_initial` — huruf pertama nama penulis, uppercase.

`GET /api/site/settings` hanya mengembalikan `site_title`, `site_logo`, `site_logo_dark`, dan `site_logo_alt` untuk branding login.

### 10.4 Validasi update

`PUT /api/admin/settings` menerima `{ "settings": { ... } }`.

- Key di luar `Setting::DEFAULTS` ditolak.
- `comment_moderation` dan `comment_notification` hanya boleh `"true"` atau `"false"`.
- `github_url`, `twitter_url`, `linkedin_url` harus URL valid.
- Panjang maksimum berbeda per key (misalnya `about_bio` 5000, `site_description` 500, `default_meta_description` 300).
- String di-`trim` sebelum disimpan.

---

## 11. Frontend Publik

### 11.1 View Blade

| File | Halaman |
|---|---|
| `layouts/public.blade.php` | Layout utama: meta SEO, Open Graph, canonical, RSS, favicon, Vite, bootstrap tema. |
| `public/landing.blade.php` | Beranda: hero, 6 artikel terbaru, topik/kategori populer, ringkasan about. |
| `public/blog/index.blade.php` | Daftar artikel dengan pencarian `q`, filter kategori, dan filter tag tersembunyi; pagination 12. |
| `public/blog/show.blade.php` | Detail artikel: cover, meta, konten HTML, reading progress, komentar, form komentar, artikel terkait, JSON-LD Article. |
| `public/about.blade.php` | Profil penulis dari setting `author_*` dan `about_*`; mendukung paragraf dan daftar topik per baris. |
| `public/category.blade.php` | Arsip per kategori. |
| `public/tag.blade.php` | Arsip per tag. |
| `errors/404.blade.php` | Halaman 404 kustom. |
| `seo/feed.blade.php` | Template RSS. |
| `seo/sitemap.blade.php` | Template XML sitemap. |

Partial yang dipakai ulang:

- `partials/navbar.blade.php` — logo terang/gelap, menu Blog/About, indikator "Mode Admin", toggle tema, menu mobile.
- `partials/footer.blade.php` — deskripsi situs, tautan sosial, copyright.
- `partials/post-card.blade.php` — kartu artikel dengan cover, kategori, reading time, judul, tanggal, dan maksimal 3 tag.
- `partials/pagination.blade.php` — navigasi halaman.
- `partials/comment-thread.blade.php` — render balasan komentar.

### 11.2 Layout dan tema

- `lang="id"`, `dir="ltr"`.
- Default **dark mode**. Script kecil di `<head>` membaca `localStorage.theme`; jika bukan `light`, class `dark` ditambahkan sebelum render untuk mencegah flash.
- Toggle tema ada di navbar dan menyimpan pilihan ke `localStorage`.
- Warna dan radius memakai CSS variable di `resources/css/app.css` (`--color-bg`, `--color-surface`, `--color-accent`, dan seterusnya) dengan override untuk `.dark`.
- Font dimuat lewat plugin `bunny` di Vite: Inter, Plus Jakarta Sans, JetBrains Mono. `Source Serif 4` disebut di token tapi tidak dimuat oleh Vite.
- `@vite(['resources/css/app.css', 'resources/js/app.js'])` dipakai di layout publik.
- Highlight.js memakai tema GitHub untuk terang dan `hljs-dark.css` untuk gelap.

### 11.3 JavaScript publik

`resources/js/app.js`:

- Menginisialisasi Highlight.js untuk `pre code` di `#article-content`.
- Mendaftarkan bahasa `apache` secara manual.
- Membungkus blok kode dengan wrapper dan tombol **Salin**.
- Tombol berubah menjadi "Tersalin!" atau "Gagal" selama 2 detik.

`resources/views/public/blog/show.blade.php` memuat JavaScript inline untuk:

- Reading progress bar.
- Toggle daftar komentar (default tertutup).
- Sorting komentar Terbaru/Terlama.
- Submit komentar dan balasan via `fetch`.
- Menambahkan komentar/balasan baru ke DOM tanpa reload.
- Aksi admin inline: edit (khusus komentar penulis), spam, dan hapus.

### 11.4 Alur halaman artikel

1. `PublicController@blogShow` mencari artikel published berdasarkan slug.
2. `views_count` ditambah 1 setiap kali halaman dibuka.
3. Komentar approved diambil, lalu disusun menjadi root + balasan.
4. Artikel terkait: maksimal 2 artikel published dari kategori yang sama.
5. View menampilkan cover, kategori, tanggal, reading time, tag, konten, komentar, dan form.

### 11.5 Prinsip konten satu bahasa

- Aplikasi hanya punya satu bahasa publik: Indonesia (`APP_LOCALE=id`, `APP_FALLBACK_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`).
- Semua teks antarmuka (Blade dan React) ditulis dalam bahasa Indonesia. Komentar kode tetap bahasa Inggris.
- Slug artikel unik secara global karena tidak ada versi bahasa lain.
- Tidak ada kolom terjemahan atau suffix locale pada key settings. Route legacy `/id/*` dan `/en/*` hanya melakukan redirect 301 ke path tanpa locale.
- Nama kategori dan tag adalah data bebas dari admin, bukan enum, sehingga penamaannya mengikuti kebiasaan penulis (contoh: `Laravel`, `PHP`, `Tutorial`).

---

## 12. Admin SPA

### 12.1 Entry point

- Route Laravel mengembalikan `resources/views/admin.blade.php`.
- Layout memuat `resources/css/app.css` dan `resources/js/admin/main.jsx`.
- `admin.blade.php` menaruh branding di meta tag (`site-title`, `site-logo`, `site-logo-dark`, `site-logo-alt`, `author-name`, `turnstile-site-key`) agar UI tidak berkedip saat memuat.
- Halaman admin memakai `noindex, nofollow`.
- React di-mount ke `#admin-root` dengan `StrictMode`.

### 12.2 Routing React

`resources/js/admin/App.jsx` memakai `BrowserRouter`:

| Path | Halaman |
|---|---|
| `/admin/login` | Login |
| `/admin/reset-password` | Reset password |
| `/admin` | Dashboard |
| `/admin/posts` | Daftar artikel |
| `/admin/posts/trash` | Tempat sampah artikel |
| `/admin/posts/create` | Form artikel baru |
| `/admin/posts/:id/edit` | Edit artikel |
| `/admin/posts/:id/view` | Preview artikel |
| `/admin/comments` | Moderasi komentar |
| `/admin/categories` | Kategori |
| `/admin/tags` | Tag |
| `/admin/media` | Media library |
| `/admin/media/trash` | Tempat sampah media |
| `/admin/users` | Pengguna (manajemen admin) |
| `/admin/settings` | Pengaturan |

Semua halaman admin di-lazy-load. Path lain diarahkan ke `/admin`.

### 12.3 State dan data fetching

- `QueryClient` memakai `retry: 1` dan `refetchOnWindowFocus: false`.
- `stores/auth.js` (Zustand) menyimpan `user` dan `status` (`loading`, `guest`, `admin`).
- `AuthGate` memanggil `fetchMe()` saat mount; guest diarahkan ke login dengan menyimpan `from`.
- `api.js` membuat instance Axios `baseURL: '/api'`, `Accept: application/json`, `withXSRFToken: true`.
- Interceptor 401 memanggil `window.__adminAuth.setGuest()` agar UI langsung keluar dari mode admin.
- `errorMessage()` mengambil pesan validasi pertama dari respons.

### 12.4 Halaman dan fitur

| Halaman | Fitur utama |
|---|---|
| `Dashboard.jsx` | Kartu statistik artikel, views, komentar pending; daftar artikel dan komentar terbaru. |
| `Posts.jsx` | Filter status, pencarian, pagination, pilih banyak, bulk delete, restore/force delete dari trash. |
| `PostForm.jsx` | React Hook Form + Zod; editor HTML mentah; snippet tag saat mengetik; slug otomatis dan peringatan jika slug artikel terbit berubah; status draft/published/scheduled; `published_at`; kategori; tag; meta title/description; cover dan OG image via `MediaPicker`; upload langsung ke media library; preview. |
| `PostView.jsx` | Pratinjau artikel dari admin. |
| `Trash.jsx` | Artikel soft-deleted, restore, hapus permanen. |
| `Comments.jsx` | Filter status/post/kategori; buka thread; approve; edit; reply; spam; hapus; bulk delete. |
| `Categories.jsx` | CRUD kategori dengan slug. |
| `Tags.jsx` | CRUD tag dengan slug. |
| `Media.jsx` | Grid media, pencarian, filter folder/kategori/tipe, sort, upload gambar/file, edit alt & kategori, lihat usage, pindah ke trash. |
| `MediaTrash.jsx` | Restore dan force delete media dengan indikator usage. |
| `Users.jsx` | Daftar admin beserta jumlah artikel; form tambah/edit admin (nama, email, password opsional); hapus admin dengan konfirmasi. Tombol hapus dinonaktifkan untuk akun sendiri dan untuk admin yang masih punya artikel. |
| `Settings.jsx` | Tab **Umum**, **Profil & About**, **Komentar**; upload logo terang/gelap, avatar, alt text, URL sosial, meta default, dan toggle moderasi/notifikasi. |
| `Login.jsx` | Login email/password, Turnstile opsional, tautan lupa password. |
| `ResetPassword.jsx` | Reset password dengan token dari email. |

Komponen pendukung:

- `Shell.jsx` — sidebar desktop, menu mobile, toggle tema, logout.
- `MediaPicker.jsx` — modal pemilih media.
- `MediaEditModal.jsx`, `MediaCategoryManager.jsx` — edit metadata dan kategori media.
- `PostComments.jsx` — thread komentar di halaman edit/preview.
- `PostPreview.jsx`, `StatusBadge.jsx`, `ConfirmModal.jsx`, `Toast.jsx`, `TurnstileWidget.jsx`.

Helper media di `resources/js/admin/media.js` menormalkan URL media menjadi path tersimpan (`toStoredPath`) dan sebaliknya (`resolveMediaUrl`), sehingga konten yang ditempel dari editor tetap konsisten.

---

## 13. SEO dan Feed

### 13.1 Meta di layout publik

Layout menyediakan section yang bisa di-override tiap halaman:

- `title`
- `meta_description`
- `canonical` (default `url()->current()`)
- `og_type` (default `website`)
- `og_title`
- `og_image`
- `hreflang`
- `structured_data`

Yang selalu dirender:

- `<title>`, meta description, canonical.
- Open Graph: `og:type`, `og:site_name`, `og:title`, `og:description`, `og:url`, dan `og:image` jika ada.
- Link RSS `application/rss+xml` ke `/feed.xml`.
- Favicon SVG/ICO dan apple-touch-icon dengan query `?v=filemtime` agar cache busting.
- `theme-color` `#0b1026`.

### 13.2 Structured data

- Halaman about mengirim JSON-LD `Person` (nama, role, dan bio penulis).
- Halaman artikel mengirim JSON-LD `Article` (headline, description, datePublished, dateModified, image, author, dan publisher).
- Halaman artikel mengatur `og_type` menjadi `article` dan `og_image` dari cover/OG.

### 13.3 Sitemap

`GET /sitemap.xml` berisi:

- `/`, `/blog`, `/about`.
- Semua artikel published: `/blog/{slug}` dengan `lastmod` dari `updated_at`.
- Semua kategori: `/category/{slug}`.
- Semua tag: `/tag/{slug}`.

Kategori dan tag memakai `now()` sebagai `lastmod`.

### 13.4 Robots

`GET /robots.txt`:

```
User-agent: *
Disallow: /api/
Disallow: /admin

Sitemap: {APP_URL}/sitemap.xml
```

### 13.5 RSS

`GET /feed.xml` menampilkan 20 artikel published terbaru. Route ini memakai `ShareSiteSettings` agar judul dan deskripsi situs tersedia.

---

## 14. Artisan Command dan Scheduler

| Command | Signature | Fungsi |
|---|---|---|
| `PublishScheduledPosts` | `posts:publish-scheduled` | Mengubah artikel `scheduled` yang `published_at <= now()` menjadi `published`. |
| `BackfillMediaPaths` | `media:backfill {--force} {--prune-missing}` | Menormalkan path media legacy, mendaftarkan cover yatim, dan membersihkan referensi file hilang. |
| `GenerateMediaThumbnails` | `media:thumbnails {--force}` | Membuat thumbnail WebP untuk media yang belum punya thumbnail. |
| `inspire` | `inspire` | Command bawaan Laravel di `routes/console.php`. |

Command memakai atribut Laravel 13 `#[Signature]` dan `#[Description]`, bukan properti `$signature`/`$description`.

Scheduler di `routes/console.php`:

```php
Schedule::command('posts:publish-scheduled')->everyMinute();
```

Syarat produksi: cron server harus menjalankan `php artisan schedule:run` setiap menit. Tanpa itu, artikel scheduled tidak akan pernah berubah menjadi published.

---

## 15. Seeder dan Factory

### 15.1 Seeder

`DatabaseSeeder` membuat admin default lalu memanggil lima seeder:

| Seeder | Isi |
|---|---|
| `CategorySeeder` | 6 kategori: Laravel, PHP, JavaScript, DevOps, Database, Linux. |
| `TagSeeder` | 15 tag: Troubleshooting, Tutorial, Eloquent, MySQL, API, Nginx, Docker, Git, React, Performa, Keamanan, Testing, Deployment, Vite, Tailwind CSS. |
| `PostSeeder` | 9 artikel: 1 artikel unggulan dengan konten lengkap, 8 artikel contoh; 7 published dan 2 draft. |
| `CommentSeeder` | Komentar untuk 6 artikel published pertama: 2–4 komentar root per artikel, sebagian dibalas admin, plus 1 komentar pending per artikel. |
| `SettingSeeder` | Menulis semua 23 key dari `Setting::DEFAULTS`. |

`MediaSeeder` ada tetapi **kosong dan tidak dipanggil** oleh `DatabaseSeeder`.

Admin default:

- Email: `admin@godang.com`
- Password: `password`
- `is_admin`: `true`
- Nama: `Admin`

Seeder memakai `updateOrCreate`, jadi aman dijalankan berulang. Namun `PostSeeder` dan `CommentSeeder` menghasilkan data acak, sehingga menjalankan ulang bisa menambah komentar baru.

### 15.2 Factory

| Factory | State / kegunaan |
|---|---|
| `UserFactory` | User biasa; dipakai juga untuk admin di test tertentu. |
| `CategoryFactory` | Kategori acak. |
| `TagFactory` | Tag acak. |
| `PostFactory` | Default published; state `published()`, `draft()`, `scheduled()`, `indonesian()`. |
| `CommentFactory` | Default approved; state `approved()`, `pending()`, `spam()`, `authorReply()`. |
| `MediaFactory` | Default image; state `file()` untuk non-gambar. |
| `MediaCategoryFactory` | Kategori media acak. |

Factory adalah cara utama membuat data di test. Gunakan state yang sudah ada sebelum menulis setup manual.

---

## 16. Testing

### 16.1 Konfigurasi

- Runner: PHPUnit 12 (`phpunit.xml`).
- Database: SQLite in-memory.
- Cache: array.
- Session: array.
- Mail: array.
- Queue: sync.
- Bcrypt rounds: 4.
- Turnstile: site key dan secret key dipaksa kosong.

### 16.2 Cakupan test

Ada **22 feature test** dan **1 unit test** (ditambah `TestCase.php`):

| Kelompok | File |
|---|---|
| Halaman publik | `PublicPagesTest`, `PublicPostsTest`, `PublicSiteContentTest`, `PublicCommentTest`, `SeoRoutesTest`, `StorageFileTest` |
| Admin | `AdminAuthorizationTest`, `AdminCategoryTest`, `AdminCommentManagementTest`, `AdminCommentModerationTest`, `AdminDashboardTest`, `AdminMediaTest`, `AdminPostManagementTest`, `AdminSettingsTest`, `AdminSpaRoutesTest`, `AdminUserManagementTest` |
| Media | `MediaUploadTest`, `PostCoverImageTest` |
| Auth, notifikasi, scheduler | `AuthLoginTest`, `CommentNotificationTest`, `PublishScheduledPostsTest` |
| Contoh bawaan | `Feature/ExampleTest`, `Unit/ExampleTest` |

### 16.3 Hasil verifikasi

Saat dokumen ini diperbarui:

```
php artisan test --compact
237 tests, 867 assertions, semua lulus
```

Durasi sekitar 32 detik di mesin lokal.

### 16.4 Cara menjalankan

```bash
# Semua test
php artisan test --compact

# File tertentu
php artisan test --compact tests/Feature/AdminMediaTest.php

# Nama test tertentu
php artisan test --compact --filter=testName

# Langsung lewat PHPUnit
vendor/bin/phpunit tests/Feature/AdminMediaTest.php
```

Aturan praktis:

- Jalankan test paling sempit yang mencakup perubahan.
- Setelah mengubah test, jalankan ulang test tersebut.
- Jangan membuat script verifikasi manual atau tinker jika sudah ada test yang membuktikan perilaku tersebut.

---

## 17. Environment dan Deployment

### 17.1 Environment lokal

Nilai penting dari `.env` lokal:

| Variabel | Nilai |
|---|---|
| `APP_NAME` | `Blog Godang` |
| `APP_ENV` | `local` |
| `APP_URL` | `http://godang.test` |
| `APP_LOCALE` | `id` |
| `APP_FALLBACK_LOCALE` | `id` |
| `APP_FAKER_LOCALE` | `id_ID` |
| `DB_CONNECTION` | `mysql` |
| `DB_DATABASE` | `blog` |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` (lokal) |
| `MAIL_MAILER` | `log` |
| `FILESYSTEM_DISK` | `local` |
| `SANCTUM_STATEFUL_DOMAINS` | `godang.test,localhost,localhost:8000` |
| `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` | kosong (nonaktif) |

`.env.example` memakai `QUEUE_CONNECTION=database` dengan komentar agar `php artisan queue:work` dijalankan di server produksi. Jika hosting tidak memungkinkan worker, ubah ke `sync` dan email akan dikirim langsung saat request.

Worker produksi:

```bash
php artisan queue:work --tries=3
```

Jalankan lewat supervisor, cron, atau process manager yang tersedia di hosting. Tanpa worker, notifikasi email akan menumpuk di tabel `jobs`.

Catatan: `FILESYSTEM_DISK=local` tidak memengaruhi media karena kode media selalu memakai disk `public`. Disk `local` menunjuk ke `storage/app/private`, sedangkan disk `public` ke `storage/app/public` dengan URL `{APP_URL}/storage`.

### 17.2 Setup lokal dari nol

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
```

Login admin: `http://godang.test/admin/login`.

Jika perubahan frontend tidak muncul atau muncul error "Unable to locate file in Vite manifest", jalankan `npm run build` atau `npm run dev`.

### 17.3 Deployment ke InfinityFree

Workflow: `.github/workflows/infinityfree.yml`.

- Trigger: push ke branch `main` dan `workflow_dispatch`.
- Runner: `ubuntu-latest`.
- Langkah: checkout, lalu upload FTP memakai `SamKirkland/FTP-Deploy-Action@v4.4.0`.
- Server: `ftpupload.net`.
- Kredensial: secret `FTP_USERNAME` dan `FTP_PASSWORD`.
- Tujuan: `/htdocs/`.
- Timeout: 24 jam.
- Exclude: `.git*`, `node_modules`, dan `STRUCTURE.md`. `STRUCTURE.md` sengaja dikecualikan karena hanya dokumentasi developer dan tidak dibutuhkan aplikasi di server.

Workflow **tidak** menjalankan:

- `composer install`
- `npm run build`
- test
- migrasi database

Karena itu:

- `vendor/` di-track agar dependency PHP ikut terkirim.
- `public/build/` di-track agar asset hasil build ikut terkirim.
- `.env` produksi harus dibuat manual di server.
- Database produksi harus dimigrasi manual.
- File media di `storage/app/public` tidak di-track; unggahan production tersimpan di server dan tidak boleh tertimpa proses deploy.
- `php artisan storage:link` perlu dijalankan di server, atau route fallback `/storage/{path}` akan menangani file.

### 17.4 Struktur hosting

Root `.htaccess` meneruskan semua request ke `public/`. Artinya document root hosting boleh berada di root project, selama mod_rewrite aktif. File sensitif seperti `.env`, `.log`, dan `.htaccess` ditolak.

Struktur yang diharapkan di server:

```
/htdocs/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
└── ...
```

### 17.5 Perbedaan lokal vs produksi

| Aspek | Lokal | Produksi (contoh InfinityFree) |
|---|---|---|
| Queue | `database` | `database` + worker, atau `sync` bila worker tidak tersedia |
| Mail | `log` | SMTP atau `log`, tergantung konfigurasi |
| Debug | `true` | sebaiknya `false` |
| Scheduler | manual | butuh cron `schedule:run` |
| Storage link | junction/symlink | symlink atau fallback route |
| Asset | Vite dev server atau build | `public/build` hasil build yang di-track |

---

## 18. Konvensi Pengembangan

### 18.1 PHP dan Laravel

- Gunakan `php artisan make:*` untuk membuat file baru.
- Selalu pakai kurung kurawal pada struktur kontrol, termasuk body satu baris.
- Selalu tulis type hint parameter dan return type.
- Gunakan constructor property promotion untuk dependency injection.
- Gunakan atribut Laravel 13 (`#[Fillable]`, `#[Hidden]`, `#[Scope]`, `#[Signature]`, `#[Description]`) mengikuti kode yang ada.
- Gunakan PHPDoc untuk array shape dan tipe kompleks; hindari komentar inline kecuali logika sangat rumit.
- Gunakan named route dan `route()`/`url()` untuk membentuk URL.
- API mengembalikan model/array JSON langsung; project ini tidak memakai Eloquent API Resource.
- Validasi ditulis inline di controller, bukan Form Request.
- Tidak ada service/repository layer; jangan menambahkannya tanpa alasan kuat.
- Jangan mengubah dependency tanpa persetujuan.
- Jangan membuat folder base baru tanpa persetujuan.

### 18.2 Frontend

- Komponen React memakai function component dan hooks.
- Halaman admin di-lazy-load.
- Data fetching memakai TanStack Query; state global memakai Zustand.
- Form memakai React Hook Form + Zod.
- Styling memakai utility Tailwind v4 dan CSS variable `--color-*`, bukan warna hardcoded.
- Ikuti pola `MediaPicker`, `ConfirmModal`, dan `Toast` yang sudah ada sebelum membuat komponen baru.
- Teks UI berbahasa Indonesia; komentar kode umumnya berbahasa Inggris.

### 18.3 Format dan kualitas

- Setelah mengubah file PHP, jalankan:

```bash
vendor/bin/pint --dirty --format agent
```

- Jangan menjalankan `pint --test`; langsung jalankan formatter.
- Jalankan test yang relevan setelah perubahan.
- Dokumentasi hanya dibuat jika diminta secara eksplisit.

### 18.4 Jalur belajar yang disarankan

Untuk kontributor baru, urutan membaca yang paling efektif:

1. `routes/web.php` dan `routes/api.php` — peta semua entry point.
2. `PublicController` dan `Api/PostController` — cara artikel diambil dan ditampilkan.
3. `Post` dan `Comment` — aturan slug, reading time, status, dan threading.
4. `MediaController` + `resources/js/admin/media.js` — pipeline media dan normalisasi path.
5. `resources/js/admin/App.jsx` dan `Shell.jsx` — kerangka admin SPA.
6. `Api/Admin/PostController` dan `PostForm.jsx` — alur CRUD artikel yang paling kompleks.
7. Test di `tests/Feature` — perilaku yang dijamin oleh kode.

Gunakan `php artisan route:list --except-vendor` dan `php artisan test` sebagai alat bantu saat membaca kode.

---

## 19. Checklist Perubahan

### 19.1 Mengubah skema database atau model

- [ ] Buat migration dengan `php artisan make:migration`.
- [ ] Perbarui `#[Fillable]`, cast, relasi, dan scope di model.
- [ ] Perbarui factory dan state yang relevan.
- [ ] Perbarui seeder jika data awal perlu berubah.
- [ ] Tambah atau sesuaikan feature test.
- [ ] Jalankan `php artisan migrate` di lokal dan `php artisan migrate --force` di produksi.
- [ ] Jalankan `vendor/bin/pint --dirty --format agent`.
- [ ] Jalankan test terkait.

### 19.2 Mengubah route atau controller

- [ ] Daftarkan route di `routes/web.php` atau `routes/api.php` sesuai sisi.
- [ ] Gunakan middleware yang tepat (`auth:sanctum`, `admin`, `ShareSiteSettings`, throttle).
- [ ] Jaga urutan route: route statis sebelum route berparameter.
- [ ] Tambah validasi dan pesan error berbahasa Indonesia.
- [ ] Perbarui endpoint di `resources/js/admin/api.js` jika API admin berubah.
- [ ] Perbarui test HTTP.
- [ ] Cek dengan `php artisan route:list --except-vendor`.

### 19.3 Mengubah media

- [ ] Pastikan file tetap disimpan di disk `public`.
- [ ] Jaga whitelist folder: `uploads`, `cover`, `avatar`, `files`.
- [ ] Jaga validasi ekstensi + MIME untuk file non-gambar.
- [ ] Perbarui logika usage jika menambah kolom yang menyimpan path media.
- [ ] Pastikan force delete tetap diblokir saat media dipakai.
- [ ] Perbarui `media:backfill` atau `media:thumbnails` jika format path berubah.
- [ ] Uji upload gambar, upload file, trash, restore, dan force delete.

### 19.4 Mengubah frontend

- [ ] Ikuti struktur `resources/js/admin` yang ada.
- [ ] Gunakan komponen dan helper yang sudah ada.
- [ ] Pastikan teks UI konsisten berbahasa Indonesia.
- [ ] Jalankan `npm run build` agar `public/build` diperbarui.
- [ ] Cek halaman di mode terang dan gelap.
- [ ] Cek tampilan mobile.
- [ ] Jika mengubah halaman publik, cek meta SEO dan komentar.

### 19.5 Sebelum commit

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact`
- [ ] `php artisan route:list --except-vendor` (jika route berubah)
- [ ] `npm run build` (jika frontend berubah)
- [ ] `git status --short`
- [ ] Pastikan `.env`, file media, dan file sementara tidak ikut ter-track.

### 19.6 Sebelum deploy

- [ ] Pastikan `vendor/` dan `public/build/` sudah diperbarui dan ter-track.
- [ ] Pastikan migration baru sudah dijalankan di server.
- [ ] Pastikan `.env` produksi berisi `APP_URL`, kredensial database, mail, dan Turnstile yang benar.
- [ ] Pastikan `storage/app/public` writable dan symlink/fallback storage berfungsi.
- [ ] Pastikan cron `schedule:run` aktif jika memakai artikel scheduled.
- [ ] Bersihkan cache produksi jika perlu: `php artisan optimize:clear`.
- [ ] Cek `/sitemap.xml`, `/robots.txt`, `/feed.xml`, dan halaman artikel setelah deploy.

### 19.7 Alur kerja end-to-end

**Menulis dan menerbitkan artikel**

1. Login, buka **Artikel → Artikel Baru**.
2. Isi judul (slug terisi otomatis), konten HTML, kategori, dan tag.
3. Pilih status: `draft`, `published`, atau `scheduled`.
4. Untuk `scheduled`, isi `published_at` di masa depan; command scheduler akan menerbitkannya.
5. Simpan. Jika `published`, artikel langsung tampil di `/blog`.

**Menyisipkan gambar ke artikel**

1. Upload lewat **Media** atau langsung dari `PostForm`.
2. Salin URL media atau pilih lewat `MediaPicker` (untuk cover/OG).
3. Untuk isi artikel, tempel path/URL `/storage/...` ke dalam HTML.
4. Path akan dinormalkan (`toStoredPath`) sebelum disimpan, sehingga cocok dengan deteksi usage media.

**Komentar masuk**

1. Pengunjung mengirim komentar lewat form.
2. Server memvalidasi, menyaring honeypot dan batas link, lalu menyimpan body sebagai teks polos.
3. Jika moderasi aktif, komentar berstatus `pending` dan muncul di **Komentar**.
4. Admin menyetujui atau menandai spam. Notifikasi email masuk ke queue.

**Menghapus dan memulihkan artikel**

1. Hapus dari daftar artikel → masuk **Trash** (soft delete).
2. Restore dari Trash, atau hapus permanen (force delete).
3. Force delete juga menghapus komentar terkait.

**Mengubah konten situs, logo, dan avatar**

1. Buka **Pengaturan**, pilih tab **Umum**, **Profil & About**, atau **Komentar**.
2. Upload logo/avatar lewat pemilih media, isi teks dan URL sosial.
3. Simpan; perubahan langsung dipakai request publik berikutnya.

### 19.8 Matriks perubahan dan rollback

| Perubahan | File yang biasanya ikut berubah | Verifikasi |
|---|---|---|
| Skema/model | migration, model, factory, seeder, test | `php artisan migrate`, test terkait |
| Route/controller | `routes/*.php`, controller, `api.js`, test | `php artisan route:list --except-vendor`, test HTTP |
| Endpoint admin | controller, `api.js`, halaman React | test + cek UI |
| Frontend admin | `resources/js/admin/**`, `npm run build` | cek mode terang/gelap + mobile |
| Halaman publik | Blade, CSS, `app.js` | cek meta SEO, komentar, responsif |
| Media | `MediaController`, `media.js`, command media | upload, trash, restore, force delete |
| Settings | `Setting::DEFAULTS`, `SettingController`, `Settings.jsx` | update tiap tab, cek halaman publik |

Rollback cepat:

- **Kode**: `git revert <commit>` lalu deploy ulang.
- **Migration**: `php artisan migrate:rollback --step=1` (hati-hati kehilangan data).
- **Asset**: build ulang dari commit sebelumnya, lalu commit `public/build`.
- **Cache produksi**: `php artisan optimize:clear`.
- **Data**: pulihkan dari backup database sebelum melakukan rollback migration.

---

## 20. Ide dan Pertanyaan Terbuka

Bagian ini bukan daftar bug yang sudah pasti, melainkan hal yang layak diputuskan atau ditingkatkan. Keputusan yang sudah final dicatat lebih dulu agar tidak dibahas ulang.

### 20.1 Keputusan yang sudah final

- **`vendor/` dan `public/build/` tetap di-track.** Ini disengaja karena InfinityFree tidak menyediakan Composer maupun proses build. Alasan lengkapnya ada di `README.md` dan bagian 17.3.
- **`STRUCTURE.md` tetap dikecualikan dari deploy.** Dokumen ini hanya untuk developer, bukan bagian dari aplikasi.
- **Scheduler di InfinityFree dibiarkan apa adanya.** Rencananya aplikasi akan dipindah ke hosting lain yang mendukung cron; sampai saat itu artikel `scheduled` diterbitkan manual bila perlu.
- **Versi di `README.md` sudah disinkronkan** dengan runtime (PHP 8.5.12, Laravel 13.37.0, Composer 2.11.1, Node.js 26.13.0, npm 12.2.2).
- **UI manajemen pengguna admin sudah tersedia** di `/admin/users` dengan endpoint `apiResource /users`.
- **Komentar disimpan sebagai teks polos** dan dirender dengan escaping di klien.
- **`forgot-password` sudah dibatasi** dengan limiter per email+IP dan per IP.
- **Notifikasi email memakai queue database**, dengan catatan worker harus dijalankan di server.
- **Hitungan `published_posts` di dashboard memakai scope `published()`**, sama seperti situs publik.
- **Deteksi usage media memakai pencocokan persis**, bukan `LIKE` mentah.

### 20.2 Scheduler di hosting baru

`posts:publish-scheduled` dijadwalkan setiap menit. Pastikan hosting tujuan benar-benar bisa menjalankan cron:

```bash
php /htdocs/artisan schedule:run
```

Jika tidak bisa, artikel `scheduled` tidak akan pernah terbit otomatis. Alternatif:

- jalankan command secara manual,
- ubah `Post::published()` agar menghitung `scheduled` yang waktunya sudah lewat,
- atau gunakan layanan cron eksternal yang memanggil endpoint aman.

### 20.3 `MediaSeeder` kosong

`MediaSeeder` tidak melakukan apa pun dan tidak dipanggil. Hapus jika tidak akan dipakai, atau isi dengan media contoh jika dibutuhkan untuk demo.

### 20.4 Font `Source Serif 4`

Token `--font-serif` menyebut `Source Serif 4`, tetapi font tersebut tidak dimuat oleh konfigurasi Vite `bunny`. Akibatnya browser memakai fallback Georgia/Times. Muat fontnya atau hapus dari token agar ekspektasi desain jelas.

### 20.5 URL media bergantung pada `APP_URL`

`Storage::disk('public')->url()` memakai `APP_URL`. Jika `APP_URL` produksi salah (misalnya masih `http://godang.test`), semua URL media dan canonical bisa salah. Pastikan nilainya benar di `.env` server.

### 20.6 Belum ada API versioning

Sesuai konvensi project, API berada langsung di `/api` tanpa prefix versi. Ini konsisten dengan kode yang ada, tetapi perlu diingat jika suatu saat ada klien eksternal.

### 20.7 Pertanyaan yang perlu dijawab

- Apakah media uploads production perlu backup terjadwal?
- Apakah komentar anonim tetap diizinkan, atau perlu login?
- Apakah perlu halaman statis tambahan (privacy policy, contact) untuk SEO?
- Apakah target deploy tetap InfinityFree, atau akan pindah ke Laravel Cloud/VPS?
- Apakah perlu peran admin tambahan (misalnya editor) selain `is_admin`?

---

## 21. Lampiran: Hasil Verifikasi

Fakta yang dicek langsung saat dokumen ini dibuat:

| Item | Hasil |
|---|---|
| Versi Laravel | 13.37.0 |
| Runtime PHP | 8.5.12 |
| Composer | 2.11.1 |
| Node.js | 26.13.0 |
| npm | 12.2.2 |
| Route aplikasi | 71 route (`route:list --except-vendor`) |
| Route health | `/up` |
| Migration | 11 file |
| Model | 8 |
| Controller | 19 file (termasuk `Controller.php`) |
| Seeder | 7 file (6 dipanggil, `MediaSeeder` tidak) |
| Factory | 7 |
| Test | 237 test, 867 assertion, semua lulus |
| File test | 24 (22 feature, 1 unit, `TestCase.php`) |
| Command kustom | 3 |
| Notifikasi | 2 |
| Setting key | 23 |
| `vendor/` di git | sekitar 9.930 file |
| `public/build/` di git | 51 file |
| `tests/` di git | 24 file |
| Branch | `main` |

Perintah verifikasi yang dipakai:

```bash
php artisan test --compact
php artisan route:list --except-vendor
composer show --direct
php -v
node -v
npm -v
git ls-files
```

### Catatan kondisi working tree

Dokumen ini diperbarui setelah rangkaian perbaikan: sinkronisasi versi README, UI manajemen pengguna admin, sanitasi komentar, throttle `forgot-password`, queue email berbasis database, hitungan `published_posts`, dan pencocokan usage media yang persis. Angka pada tabel di atas diambil dari kondisi working tree setelah `vendor/bin/pint --dirty`, `php artisan test --compact`, dan `npm run build` dijalankan.

---

Dokumen ini sebaiknya diperbarui ketika ada perubahan pada:

- skema database,
- daftar route,
- struktur folder utama,
- pipeline media,
- alur autentikasi,
- workflow deploy,
- atau konfigurasi environment yang wajib.

