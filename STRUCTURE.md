# STRUKTUR PROYEK GODANG

> Dokumen ini menjelaskan struktur, alur, dan bagian-bagian penting dari aplikasi **Godang** — sebuah blog teknis pribadi berbahasa Indonesia dengan situs publik berbasis Blade dan panel admin berbasis React SPA.
>
> Tujuan dokumen: menjadi peta belajar dan referensi cepat. Semua isi di bawah ini mengikuti kode yang ada saat ini (Laravel 13, React 19). Tidak ada perilaku aplikasi yang diubah saat dokumen ini dibuat.

---

## Daftar Isi

1. [Ringkasan Proyek](#1-ringkasan-proyek)
2. [Stack dan Versi](#2-stack-dan-versi)
3. [Peta Arsitektur](#3-peta-arsitektur)
4. [Struktur Direktori](#4-struktur-direktori)
5. [Alur Request](#5-alur-request)
6. [Routing Lengkap](#6-routing-lengkap)
7. [Peta Controller](#7-peta-controller)
8. [Model dan Relasi](#8-model-dan-relasi)
9. [Skema Database](#9-skema-database)
10. [Struktur Konten Satu Bahasa](#10-struktur-konten-satu-bahasa)
11. [Siklus Hidup Artikel](#11-siklus-hidup-artikel)
12. [Pipeline Media dan Storage](#12-pipeline-media-dan-storage)
13. [Sistem Komentar](#13-sistem-komentar)
14. [Autentikasi, Otorisasi, dan Keamanan](#14-autentikasi-otorisasi-dan-keamanan)
15. [Settings Aplikasi](#15-settings-aplikasi)
16. [Frontend Publik (Blade)](#16-frontend-publik-blade)
17. [Admin SPA (React)](#17-admin-spa-react)
18. [SEO](#18-seo)
19. [Console Command dan Scheduler](#19-console-command-dan-scheduler)
20. [Seeder dan Factory](#20-seeder-dan-factory)
21. [Testing](#21-testing)
22. [Workflow End-to-End](#22-workflow-end-to-end)
23. [Setup dan Perintah Harian](#23-setup-dan-perintah-harian)
24. [Konvensi dan Catatan Penting](#24-konvensi-dan-catatan-penting)
25. [Jalur Belajar yang Disarankan](#25-jalur-belajar-yang-disarankan)
26. [Panduan Perubahan (Change Checklist)](#26-panduan-perubahan-change-checklist)

---

## 1. Ringkasan Proyek

Godang adalah aplikasi blog dengan dua “wajah”:

| Bagian       | Teknologi                      | URL                          | Karakter                                              |
| ------------ | ------------------------------ | ---------------------------- | ----------------------------------------------------- |
| Situs publik | Laravel Blade + Tailwind CSS 4 | `/`, `/blog`, `/about`, dst. | Server-side rendering, SEO-friendly, bahasa Indonesia |
| Panel admin  | React 19 SPA di dalam Laravel  | `/admin/...`                 | Client-side routing, terautentikasi sesi              |
| API          | Laravel JSON API               | `/api/...`                   | Dipakai admin SPA, sekaligus tersedia publik          |

Fitur utama:

- Artikel dengan status `draft`, `published`, dan `scheduled` (publish otomatis lewat scheduler).
- Satu bahasa (Indonesia) dengan URL root tanpa prefix locale.
- Kategori, tag, pencarian, dan paginasi.
- Editor artikel berupa `<textarea>` HTML mentah dengan slash snippets (`/p`, `/div`, `/h2`, `/h3`, `/ul`, `/pre`, dan lainnya).
- Perpustakaan media dengan tab **Gambar** dan **File**: gambar dikonversi ke WebP + thumbnail 480 px, file non-gambar (PDF, Office, teks, ZIP) disimpan apa adanya; kategori custom, sampah (trash), dan penanda media yang masih dipakai artikel/settings.
- Komentar publik dua tingkat (root + balasan), moderasi, honeypot, dan notifikasi email. Komentar penulis selalu tampil dengan `author_name` dari Settings (bukan nama akun admin), dan admin bisa menandai spam/menghapus komentar langsung dari halaman artikel.
- Panel admin untuk artikel, sampah artikel, komentar, kategori, tag, media, sampah media, kategori media, dan settings.
- Pratinjau artikel admin (`/admin/posts/{id}/view`) memakai layout yang sama dengan halaman blog publik: header artikel, isi `prose-article`, sidebar **Daftar Isi** yang tetap sticky melewati bagian komentar, syntax highlighting, dan tombol salin kode.
- Saat login sebagai admin, navbar publik menampilkan badge **Mode Admin** menuju dashboard; shell admin menampilkan tombol **Home** ke situs publik.
- Konten situs dapat diedit tanpa kode dari **Admin → Settings**: judul/deskripsi situs, logo terang/gelap, profil & halaman About, avatar penulis, serta link sosial. Teks hero beranda dan header blog di-hardcode di view.
- SEO: meta tag, Open Graph, hreflang, JSON-LD, sitemap, robots, dan RSS.
- Mode gelap/terang dengan preferensi tersimpan di browser.

Dokumen pendukung di root:

- `AGENTS.md` / `CLAUDE.md` — panduan kerja untuk agen AI di repo ini.
- `README.md` — petunjuk instalasi singkat (masih memakai Valet); detail operasional ada di dokumen ini.

---

## 2. Stack dan Versi

### Backend (PHP)

| Paket                | Versi terpasang                       | Peran                                  |
| -------------------- | ------------------------------------- | -------------------------------------- |
| PHP                  | `^8.4.1` (runtime lingkungan: 8.5.12) | Bahasa backend                         |
| `laravel/framework`  | 13.37.0                               | Framework utama                        |
| `laravel/sanctum`    | 4.3.3                                 | Autentikasi SPA berbasis sesi          |
| `intervention/image` | 4.4.0                                 | Konversi dan resize gambar (driver GD) |
| `laravel/tinker`     | 3.0.2                                 | REPL                                   |
| `laravel/pint`       | 1.32.1                                | Formatter PHP                          |
| `phpunit/phpunit`    | 12.5.40                               | Test runner                            |
| `laravel/boost`      | 2.10.3                                | MCP/alat bantu pengembangan (dev)      |
| `laravel/pail`       | 1.2.7                                 | Log viewer (dev)                       |
| `laravel/pao`        | 1.1.5                                 | Output test ramah agen (dev)           |

> Transitive dependency: `symfony/mime` 8.1.10 (patch keamanan CVE-2026-45070). Jalankan `composer audit` setelah mengubah dependency.

### Frontend (JavaScript)

| Paket                     | Versi                             | Peran                                                                                |
| ------------------------- | --------------------------------- | ------------------------------------------------------------------------------------ |
| React / React DOM         | 19.3.1                            | UI admin                                                                             |
| Vite                      | 8.3.4                             | Bundler                                                                              |
| Tailwind CSS              | 4.3.3                             | Styling (via `@tailwindcss/vite`)                                                    |
| `@tanstack/react-query`   | 5.104.4                           | Data fetching dan cache admin                                                        |
| `react-router-dom`        | 7.18.4                            | Routing SPA                                                                          |
| `zustand`                 | 5.0.15                            | State global (auth)                                                                  |
| `axios`                   | 1.20.0                            | HTTP client admin (`follow-redirects` 1.16.2)                                        |
| `react-hook-form` + `zod` | 7.90 / **4.6.7**                  | Form dan validasi                                                                    |
| `@hookform/resolvers`     | 5.9.2                             | Resolver zod untuk react-hook-form                                                   |
| `highlight.js`            | 11.12.0                           | Syntax highlighting artikel publik dan pratinjau admin (Apache dimuat on-demand)     |
| `laravel-vite-plugin`     | 3.2.0                             | Integrasi Vite–Laravel                                                               |
| `@vitejs/plugin-react`    | 6.1.2                             | Plugin React untuk Vite                                                              |
| `fontaine`                | 0.8.2 (dev)                       | Metrik fallback font untuk `laravel-vite-plugin/fonts`                               |
| `concurrently`            | 10.0.6                            | Menjalankan proses dev di Windows                                                    |
| `sharp` / `png-to-ico`    | 0.35.5 / 3.0.2                    | Perkakas pembuatan favicon/ikon (dev)                                                |
| `@laravel/multiplex`      | 0.4.5 (opsional, belum terpasang) | Runner `artisan dev` di Linux/macOS; di Windows `artisan dev` memakai `concurrently` |

### Infrastruktur lokal (dari `.env`)

| Konfigurasi                | Nilai                                                                         |
| -------------------------- | ----------------------------------------------------------------------------- |
| `APP_URL`                  | `http://godang.test`                                                          |
| `DB_CONNECTION`            | `mysql` (database `blog`)                                                     |
| `SESSION_DRIVER`           | `database`                                                                    |
| `QUEUE_CONNECTION`         | `database`                                                                    |
| `CACHE_STORE`              | `database`                                                                    |
| `MAIL_MAILER`              | `log`                                                                         |
| `FILESYSTEM_DISK`          | `local` — **tetapi kode media selalu memakai disk `public` secara eksplisit** |
| `SANCTUM_STATEFUL_DOMAINS` | `godang.test,localhost,localhost:8000`                                        |

> `.env.example` sudah memakai `APP_URL=http://godang.test` dan database `blog`. Saat setup baru, sesuaikan `APP_URL`, `DB_DATABASE`, dan `SANCTUM_STATEFUL_DOMAINS` dengan domain lokal yang dipakai.

---

## 3. Peta Arsitektur

```
                             +-----------------------------+
                             |        Browser              |
                             |  (publik & admin)           |
                             +--------------+--------------+
                                            |
                     +----------------------+----------------------+
                     |                                             |
        GET /, /blog, /about, ...                         GET /admin/*
        (Blade, SSR + ShareSiteSettings)                 (shell Blade)
                     |                                             |
                     v                                             v
        +------------------------+                     +------------------------+
        |  PublicController      |                     |  React SPA (Vite)      |
        |  SeoController         |                     |  React Router          |
        |  StorageFileController |                     |  TanStack Query        |
        +-----------+------------+                     +-----------+------------+
                    |                                              |
                    |                                              | /api/* (axios)
                    |                                              v
                    |                                  +------------------------+
                    |                                  |  API Laravel           |
                    |                                  |  Sanctum + middleware  |
                    |                                  |  `admin`               |
                    |                                  +-----------+------------+
                    |                                              |
                    +--------------------+-------------------------+
                                         |
                                         v
                             +------------------------+
                             |  Eloquent Models       |
                             |  Post, Category, Tag,  |
                             |  Comment, Media,       |
                             |  MediaCategory,        |
                             |  Setting, User         |
                             +-----------+------------+
                                         |
                    +--------------------+--------------------+
                    |                                         |
                    v                                         v
          +-------------------+                    +----------------------+
          |  MySQL `blog`     |                    | storage/app/public   |
          |  (data + session) |                    | (gambar WebP)        |
          +-------------------+                    +----------+-----------+
                                                                |
                                                     public/storage (junction)
                                                                |
                                                     /storage/{path} fallback
```

Dua jalur render yang berbeda:

1. **Publik** — Laravel merender Blade sepenuhnya di server. JavaScript hanya untuk highlight kode, tombol salin, toggle tema, dan submit komentar via `fetch`.
2. **Admin** — Laravel hanya mengirim satu halaman shell (`resources/views/admin.blade.php`). Setelah itu React mengambil alih routing dan berkomunikasi dengan `/api/*`.

---

## 4. Struktur Direktori

```
blog/
├── app/
│   ├── Console/Commands/
│   │   ├── BackfillMediaPaths.php      # normalisasi path media lama
│   │   ├── GenerateMediaThumbnails.php # buat thumbnail media lama
│   │   └── PublishScheduledPosts.php   # publish artikel terjadwal
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── PublicController.php    # landing, blog, about, category, tag
│   │   │   ├── SeoController.php       # sitemap, robots, RSS
│   │   │   ├── StorageFileController.php # fallback /storage/{path}
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── SiteController.php  # pengaturan publik untuk branding (login/admin)
│   │   │       ├── PostController.php
│   │   │       ├── CommentController.php
│   │   │       ├── CategoryController.php
│   │   │       ├── TagController.php
│   │   │       └── Admin/
│   │   │           ├── DashboardController.php
│   │   │           ├── PostController.php
│   │   │           ├── CommentController.php
│   │   │           ├── CategoryController.php
│   │   │           ├── TagController.php
│   │   │           ├── MediaController.php
│   │   │           ├── MediaCategoryController.php
│   │   │           └── SettingController.php
│   │   └── Middleware/
│   │       ├── EnsureUserIsAdmin.php   # alias middleware: admin
│   │       └── ShareSiteSettings.php   # bagikan $site ke view publik & shell admin
│   ├── Models/
│   │   ├── User.php                    # CanResetPassword + HasApiTokens
│   │   ├── Post.php
│   │   ├── Category.php
│   │   ├── Tag.php
│   │   ├── Comment.php
│   │   ├── Media.php                   # soft delete, thumbnail, kategori
│   │   ├── MediaCategory.php
│   │   └── Setting.php
│   ├── Notifications/
│   │   ├── NewCommentNotification.php
│   │   └── ReplyToCommentNotification.php
│   ├── Providers/
│   │   └── AppServiceProvider.php      # rate limiter "login"
│   └── Rules/
│       └── Turnstile.php               # verifikasi token Cloudflare Turnstile
├── bootstrap/
│   └── app.php                         # routing, middleware, exception config
├── config/                             # konfigurasi Laravel
├── database/
│   ├── factories/                      # Category, Comment, Media, MediaCategory, Post, Tag, User
│   ├── migrations/                     # 11 migrasi (media_categories dibuat bersama tabel media; soft delete/thumb, tipe & nama asli media)
│   └── seeders/                        # data demo + admin
├── public/
│   ├── build/                          # hasil `npm run build`; di-commit agar ikut deploy FTP
│   ├── storage/                        # junction ke storage/app/public
│   └── index.php
├── resources/
│   ├── css/
│   │   ├── app.css                     # Tailwind 4 + design token + komponen
│   │   └── hljs-dark.css               # tema highlight kode mode gelap
│   ├── js/
│   │   ├── app.js                      # JS situs publik
│   │   └── admin/
│   │       ├── main.jsx                # entry React
│   │       ├── App.jsx                 # router + AuthGate
│   │       ├── api.js                  # axios instance + endpoints
│   │       ├── media.js                # helper URL/path media (admin)
│   │       ├── site.js                 # branding & Turnstile key dari meta tag
│   │       ├── components/             # Shell, Toast, Modal, MediaPicker, MediaEditModal, MediaCategoryManager, PostPreview, dll.
│   │       ├── pages/                  # 13 halaman admin
│   │       └── stores/auth.js          # state auth (zustand)
│   ├── lang/
│   │   └── id/                         # teks antarmuka publik (auth, pagination, passwords, validation)
│   └── views/
│       ├── admin.blade.php             # shell SPA + meta branding
│       ├── layouts/public.blade.php    # layout situs publik
│       ├── partials/                   # navbar, footer, card, pagination, komentar
│       ├── public/                     # landing, about, blog, category, tag
│       ├── seo/                        # sitemap.blade.php, feed.blade.php
│       └── errors/404.blade.php
├── routes/
│   ├── web.php                         # publik + shell admin + SEO + storage
│   ├── api.php                         # API publik, auth, admin
│   └── console.php                     # scheduler
├── storage/
│   └── app/public/
│       ├── uploads/                    # gambar isi artikel
│       ├── cover/                      # cover & OG image
│       ├── avatar/                     # avatar penulis (logo situs biasanya di uploads)
│       └── files/                      # file non-gambar (PDF, Office, teks, ZIP)
├── tests/
│   ├── Feature/                        # 21 file test fitur
│   ├── Unit/ExampleTest.php
│   └── TestCase.php
├── .env / .env.example
├── .github/
│   └── workflows/
│       └── infinityfree.yml            # checkout + deploy FTP ke InfinityFree
├── composer.json
├── package.json
├── vite.config.js
├── phpunit.xml
├── AGENTS.md / CLAUDE.md
└── STRUCTURE.md                        # dokumen ini
```

---

## 5. Alur Request

### 5.1 Halaman publik (`/blog/{slug}`)

```
Browser
  -> Route web: GET /blog/{slug}
  -> Middleware ShareSiteSettings
       -> Setting::allValues()
       -> view()->share('site', $site)
  -> PublicController@blogShow($slug)
       -> Post::published()->where('slug', ...)->firstOrFail()
       -> increment views_count
       -> ambil komentar approved (root + replies)
       -> ambil 2 artikel terkait (utamakan kategori sama)
  -> resources/views/public/blog/show.blade.php
  -> layouts/public.blade.php (meta, OG, hreflang, JSON-LD, navbar, footer)
  -> HTML + aset Vite (app.css, app.js)
```

Interaksi setelah halaman tampil:

- Toggle tema (inline script + `localStorage`).
- Highlight kode dan tombol salin (`resources/js/app.js`).
- Form komentar mengirim `fetch` ke `/api/posts/{id}/comments`.
- Admin yang login melihat badge **Mode Admin**, field nama/email disembunyikan, dan tombol **Spam**/**Hapus** pada setiap komentar; komentar baru langsung `approved` dengan nama author.

### 5.2 Admin SPA (`/admin/posts/5/edit`)

```
Browser
  -> GET /admin/posts/5/edit
  -> Route web: GET /admin/{path?} (middleware: auth, admin, ShareSiteSettings)
       - Belum login  -> redirect /admin/login
       - Bukan admin  -> 403
       - ShareSiteSettings menyiapkan $site untuk logo/nama situs di shell
  -> resources/views/admin.blade.php (shell + @vite)
  -> React memuat, BrowserRouter membaca /admin/posts/5/edit
  -> AuthGate memanggil GET /api/auth/me
  -> Halaman PostForm memanggil:
       GET /api/admin/posts/5
       GET /api/admin/categories
       GET /api/admin/tags
       GET /api/admin/media
       GET /api/admin/media-categories
```

Semua request API memakai cookie sesi yang sama (Sanctum stateful), bukan token bearer.

### 5.3 API publik (`/api/posts`)

```
GET /api/posts?q=laravel&category=laravel&tag=eloquent&per_page=12
  -> Api\PostController@index
  -> Post::published() + filter + paginate (maks. 50)
  -> JSON paginator Laravel
```

### 5.4 Konten situs dari Settings

```
Admin
  -> /admin/settings (React, 3 tab: Umum, Profil & About, Komentar)
  -> PUT /api/admin/settings
  -> SettingController@update (whitelist + validasi per key)
  -> tabel settings (key-value)

Pembaca
  -> GET /... (halaman publik)
  -> Middleware ShareSiteSettings
       -> Setting::DEFAULTS + nilai DB = $settings
       -> Ambil key setting langsung (tanpa suffix locale)
       -> view()->share('site', $site)
  -> Blade membaca $site[...] (judul, deskripsi, logo, About, sosial, avatar)

Shell admin
  -> GET /admin/login atau /admin/{path?}
  -> Middleware ShareSiteSettings yang sama
  -> admin.blade.php memakai $site untuk logo/nama situs sebelum React mount
```

Perubahan settings langsung berlaku pada request publik berikutnya; tidak ada cache tambahan. Logo situs (`site_logo`, `site_logo_dark`) dan avatar penulis memakai file dari Media — tidak ada penyalinan file saat settings disimpan.

---

## 6. Routing Lengkap

Total route aplikasi (tanpa vendor): **67 route**.

### 6.1 Route web publik dan shell

| Method | URI                       | Name             | Handler                      | Keterangan                                                               |
| ------ | ------------------------- | ---------------- | ---------------------------- | ------------------------------------------------------------------------ |
| GET    | `/`                       | `home`           | `PublicController@landing`   | Beranda                                                                  |
| GET    | `/blog`                   | `blog.index`     | `PublicController@blogIndex` | Daftar artikel + filter                                                  |
| GET    | `/blog/{slug}`            | `blog.show`      | `PublicController@blogShow`  | Detail artikel                                                           |
| GET    | `/about`                  | `about`          | `PublicController@about`     | Halaman statis                                                           |
| GET    | `/category/{slug}`        | `category.show`  | `PublicController@category`  | Arsip kategori                                                           |
| GET    | `/tag/{slug}`             | `tag.show`       | `PublicController@tag`       | Arsip tag                                                                |
| GET    | `/feed.xml`               | `seo.feed`       | `SeoController@feed`         | RSS 20 artikel terbaru; judul & deskripsi dari settings                  |
| GET    | `/admin/login`            | `admin.login`    | Closure                      | Shell SPA, publik; middleware `ShareSiteSettings`                        |
| GET    | `/admin/reset-password`   | `password.reset` | Closure                      | Shell SPA halaman reset password, publik; middleware `ShareSiteSettings` |
| GET    | `/admin/{path?}`          | `admin`          | Closure                      | Shell SPA, middleware `auth` + `admin` + `ShareSiteSettings`             |
| GET    | `/storage/{path}`         | `storage.file`   | `StorageFileController`      | Fallback penyajian file media                                            |
| GET    | `/sitemap.xml`            | `seo.sitemap`    | `SeoController@sitemap`      | Sitemap XML                                                              |
| GET    | `/robots.txt`             | `seo.robots`     | `SeoController@robots`       | Instruksi crawler                                                        |
| GET    | `/{legacyLocale}/{path?}` | `legacy.locale`  | Closure                      | Redirect 301 `/id/*` dan `/en/*` ke URL root; locale lain `404`          |

Halaman publik dan `feed.xml` memakai middleware `ShareSiteSettings` yang membagikan `$site` ke view; `/sitemap.xml`, `/robots.txt`, dan `/storage/{path}` tidak memerlukannya. Route redirect legacy didaftarkan paling akhir agar tidak menabrak route nyata.

### 6.2 Route admin SPA (React Router)

| Path                    | Komponen        | Fungsi                                                                  |
| ----------------------- | --------------- | ----------------------------------------------------------------------- |
| `/admin/login`          | `Login`         | Form login                                                              |
| `/admin/reset-password` | `ResetPassword` | Form reset password dengan token dari email                             |
| `/admin`                | `Dashboard`     | Statistik dan aktivitas terbaru                                         |
| `/admin/posts`          | `Posts`         | Daftar artikel, filter, bulk action                                     |
| `/admin/posts/trash`    | `Trash`         | Artikel terhapus, restore / hapus permanen                              |
| `/admin/posts/create`   | `PostForm`      | Buat artikel                                                            |
| `/admin/posts/:id/edit` | `PostForm`      | Edit artikel                                                            |
| `/admin/posts/:id/view` | `PostView`      | Pratinjau artikel bergaya blog (Daftar Isi + moderasi komentar)         |
| `/admin/comments`       | `Comments`      | Moderasi komentar                                                       |
| `/admin/categories`     | `Categories`    | CRUD kategori                                                           |
| `/admin/tags`           | `Tags`          | CRUD tag                                                                |
| `/admin/media`          | `Media`         | Unggah, cari, filter folder/kategori, kategori kustom, salin URL, trash |
| `/admin/media/trash`    | `MediaTrash`    | Media terhapus, restore / hapus permanen massal                         |
| `/admin/settings`       | `Settings`      | Pengaturan situs (tab Umum, Profil & About, Komentar)                   |
| `*`                     | —               | Redirect ke `/admin`                                                    |

### 6.3 API publik

| Method | URI                          | Name                   | Keterangan                                                                                          |
| ------ | ---------------------------- | ---------------------- | --------------------------------------------------------------------------------------------------- |
| GET    | `/api/posts`                 | `api.posts.index`      | Artikel published; filter `q`, `category`, `tag`, `per_page`                                        |
| GET    | `/api/posts/{slug}`          | `api.posts.show`       | Detail artikel published berdasarkan slug                                                           |
| GET    | `/api/posts/{post}/comments` | `api.comments.index`   | Komentar approved (root + replies)                                                                  |
| POST   | `/api/posts/{post}/comments` | `api.comments.store`   | Kirim komentar; throttle `6,1`                                                                      |
| GET    | `/api/categories`            | `api.categories.index` | Semua kategori + jumlah artikel published                                                           |
| GET    | `/api/tags`                  | `api.tags.index`       | Semua tag + jumlah artikel published                                                                |
| GET    | `/api/site/settings`         | `api.site.settings`    | Pengaturan publik untuk branding login/admin (site_title, site_logo, site_logo_dark, site_logo_alt) |

Catatan: `{post}` pada komentar di-resolve sebagai **ID** (route model binding), sedangkan `{slug}` pada artikel adalah slug.

Slug artikel unik secara global karena hanya ada satu bahasa.

### 6.4 API autentikasi

| Method | URI                         | Name                       | Middleware       | Keterangan                                                               |
| ------ | --------------------------- | -------------------------- | ---------------- | ------------------------------------------------------------------------ |
| POST   | `/api/auth/login`           | `api.auth.login`           | `throttle:login` | Login admin (email + password, wajib token Turnstile bila dikonfigurasi) |
| POST   | `/api/auth/forgot-password` | `api.auth.forgot-password` | —                | Kirim link reset password (Password Broker)                              |
| POST   | `/api/auth/reset-password`  | `api.auth.reset-password`  | —                | Reset password dengan token                                              |
| POST   | `/api/auth/logout`          | `api.auth.logout`          | `auth:sanctum`   | Logout                                                                   |
| GET    | `/api/auth/me`              | `api.auth.me`              | `auth:sanctum`   | User aktif                                                               |

Rate limiter `login`: **5 percobaan/menit**, key = `email + IP`.

### 6.5 API admin

Semua route di bawah middleware `auth:sanctum` + `admin`, prefix `/api/admin`, name prefix `api.admin.`.

| Method         | URI                                 | Aksi                                                                                                                                                                                                                                                                                                                        |
| -------------- | ----------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET            | `/dashboard`                        | Statistik dashboard                                                                                                                                                                                                                                                                                                         |
| GET            | `/posts`                            | Daftar semua artikel (termasuk draft; `trashed=1` untuk trash)                                                                                                                                                                                                                                                              |
| POST           | `/posts`                            | Buat artikel                                                                                                                                                                                                                                                                                                                |
| GET            | `/posts/{post}`                     | Detail artikel (termasuk yang soft-deleted)                                                                                                                                                                                                                                                                                 |
| PUT/PATCH      | `/posts/{post}`                     | Update artikel                                                                                                                                                                                                                                                                                                              |
| DELETE         | `/posts/{post}`                     | Soft delete (`?force=1` untuk permanen)                                                                                                                                                                                                                                                                                     |
| POST           | `/posts/bulk-delete`                | Hapus massal (`force` opsional)                                                                                                                                                                                                                                                                                             |
| POST           | `/posts/{post}/restore`             | Restore artikel dari trash                                                                                                                                                                                                                                                                                                  |
| GET/POST       | `/categories`                       | List / buat kategori                                                                                                                                                                                                                                                                                                        |
| GET/PUT/DELETE | `/categories/{category}`            | Detail / update / hapus kategori                                                                                                                                                                                                                                                                                            |
| GET/POST       | `/tags`                             | List / buat tag                                                                                                                                                                                                                                                                                                             |
| GET/PUT/DELETE | `/tags/{tag}`                       | Detail / update / hapus tag                                                                                                                                                                                                                                                                                                 |
| GET            | `/comments`                         | List komentar (default hanya root)                                                                                                                                                                                                                                                                                          |
| POST           | `/comments`                         | Komentar baru sebagai admin                                                                                                                                                                                                                                                                                                 |
| PATCH          | `/comments/{comment}`               | Approve                                                                                                                                                                                                                                                                                                                     |
| PUT            | `/comments/{comment}`               | Edit isi komentar                                                                                                                                                                                                                                                                                                           |
| PATCH          | `/comments/{comment}/spam`          | Tandai spam (beserta balasannya)                                                                                                                                                                                                                                                                                            |
| POST           | `/comments/{comment}/reply`         | Balas sebagai admin                                                                                                                                                                                                                                                                                                         |
| DELETE         | `/comments/{comment}`               | Hapus komentar + balasannya                                                                                                                                                                                                                                                                                                 |
| POST           | `/comments/bulk-delete`             | Hapus massal                                                                                                                                                                                                                                                                                                                |
| GET            | `/media`                            | List media dengan filter `type` (`image` default / `file`), `trashed`, `unused`, `folder`/`other`, `category`/`none`, `q`, `sort`, `per_page` 1–50; respons menyertakan `usage_count`, `usage_labels`, `folders`, `categories`, `uncategorized_count`, `unused_count`, `type` (data gambar dan file tidak pernah tercampur) |
| POST           | `/media`                            | Upload media: `type=image` (default) → maks. 5 MB, WebP asli ≤1600 px + thumbnail ≤480 px; `type=file` → maks. 10 MB, ekstensi/MIME dari allowlist, disimpan apa adanya di `files/`                                                                                                                                         |
| PATCH          | `/media/{media}`                    | Ubah `alt_text` dan/atau kategori media                                                                                                                                                                                                                                                                                     |
| DELETE         | `/media/{media}`                    | Pindahkan media ke sampah (soft delete)                                                                                                                                                                                                                                                                                     |
| POST           | `/media/{media}/restore`            | Pulihkan media dari sampah                                                                                                                                                                                                                                                                                                  |
| DELETE         | `/media/{media}/force`              | Hapus permanen; **409** + `usage` bila masih direferensikan                                                                                                                                                                                                                                                                 |
| POST           | `/media/bulk-delete`                | Pindahkan banyak media ke sampah                                                                                                                                                                                                                                                                                            |
| POST           | `/media/bulk-restore`               | Pulihkan banyak media                                                                                                                                                                                                                                                                                                       |
| POST           | `/media/bulk-force-delete`          | Hapus permanen massal; **409** + `blocked` bila ada yang masih dipakai                                                                                                                                                                                                                                                      |
| GET/POST       | `/media-categories`                 | List / buat kategori media                                                                                                                                                                                                                                                                                                  |
| PATCH/DELETE   | `/media-categories/{mediaCategory}` | Ubah / hapus kategori media                                                                                                                                                                                                                                                                                                 |
| GET            | `/settings`                         | Ambil semua settings                                                                                                                                                                                                                                                                                                        |
| PUT            | `/settings`                         | Update batch settings                                                                                                                                                                                                                                                                                                       |

Catatan komentar admin: `POST /comments` dan `POST /comments/{comment}/reply` menyimpan `name = Setting::authorName()` (nama author dari Settings) dengan `is_author = true`, dan komentar langsung `approved`. `PUT /comments/{comment}` hanya mengubah `body`.

---

## 7. Peta Controller

| Controller                          | Tanggung jawab                                                                                                                                                            | Model utama                                 |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| `PublicController`                  | Render 6 halaman publik + filter/paginasi                                                                                                                                 | `Post`, `Category`, `Tag`                   |
| `SeoController`                     | Sitemap, robots, RSS                                                                                                                                                      | `Post`, `Category`, `Tag`                   |
| `StorageFileController`             | Sajikan file `uploads/`, `cover/`, `avatar/`, dan `files/` bila symlink tidak tersedia                                                                                    | `Storage`                                   |
| `Api\AuthController`                | Login/logout/me, forgot/reset password berbasis sesi                                                                                                                      | `User`                                      |
| `Api\SiteController`                | Pengaturan publik untuk branding login/admin                                                                                                                              | `Setting`                                   |
| `Api\PostController`                | API artikel publik                                                                                                                                                        | `Post`                                      |
| `Api\CommentController`             | List & kirim komentar publik, notifikasi                                                                                                                                  | `Comment`, `Setting`, `User`                |
| `Api\CategoryController`            | List kategori publik                                                                                                                                                      | `Category`                                  |
| `Api\TagController`                 | List tag publik                                                                                                                                                           | `Tag`                                       |
| `Api\Admin\DashboardController`     | Agregasi statistik                                                                                                                                                        | `Post`, `Comment`                           |
| `Api\Admin\PostController`          | CRUD, restore, force delete, bulk delete                                                                                                                                  | `Post`, `Tag`                               |
| `Api\Admin\CommentController`       | Moderasi dan balasan admin                                                                                                                                                | `Comment`                                   |
| `Api\Admin\CategoryController`      | CRUD kategori                                                                                                                                                             | `Category`                                  |
| `Api\Admin\TagController`           | CRUD tag                                                                                                                                                                  | `Tag`                                       |
| `Api\Admin\MediaController`         | Upload gambar (WebP + thumbnail) dan file non-gambar (allowlist, maks. 10 MB), list per `type` dengan filter/usage, update metadata, trash/restore/force-delete, kategori | `Media`, `MediaCategory`, `Post`, `Setting` |
| `Api\Admin\MediaCategoryController` | CRUD kategori media kustom                                                                                                                                                | `MediaCategory`                             |
| `Api\Admin\SettingController`       | Baca/tulis settings: whitelist key, validasi URL/boolean/panjang                                                                                                          | `Setting`                                   |

Prinsip umum controller:

- Validasi dilakukan langsung di controller dengan `$request->validate([...])`.
- Response admin berupa JSON model/paginator; tidak memakai API Resource.
- Otorisasi admin terpusat di middleware, bukan di tiap method.
- `abort_unless($post->isPublished(), 404)` dipakai untuk endpoint komentar.

---

## 8. Model dan Relasi

### 8.1 Ringkasan model

| Model           | Tabel              | Fitur penting                                                                                                                                                                                                |
| --------------- | ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `User`          | `users`            | `HasApiTokens`, `CanResetPassword`, `Notifiable`, cast `is_admin` boolean, relasi `posts`                                                                                                                    |
| `Post`          | `posts`            | `SoftDeletes`, scope `published`, accessor `cover_url`/`og_url`, auto slug + reading time                                                                                                                    |
| `Category`      | `categories`       | `name` string biasa, relasi `posts`                                                                                                                                                                          |
| `Tag`           | `tags`             | `name` string biasa, relasi many-to-many `posts`                                                                                                                                                             |
| `Comment`       | `comments`         | Relasi `post`, `parent`, `replies`; scope `approved`/`pending`/`rootComments`; accessor `name` menampilkan `Setting::authorName()` untuk komentar `is_author`; field sensitif disembunyikan                  |
| `Media`         | `media`            | `SoftDeletes`, `type` (`image`/`file`), scope `images()`/`files()`, accessor `url`/`thumb_url`/`download_name`, kategori kustom, normalisasi path statis (`diskPath`, `isExternalUrl`), `thumbnailPathFor()` |
| `MediaCategory` | `media_categories` | Kategori media kustom; relasi `media`; nama unik                                                                                                                                                             |
| `Setting`       | `settings`         | Key-value dengan `getValue`/`setValue`, `authorName()`, `DEFAULTS` (23 key), dan `allValues()`                                                                                                               |

### 8.2 Diagram relasi

```
User 1 ───────< Post >─────── 1 Category
                 │
                 ├──────< Comment
                 │           └──< Comment   (parent_id, balasan)
                 │
                 └──< post_tag >── Tag

MediaCategory 1 ───────< Media

Post.cover_image / og_image ──(path/URL)──> file di disk "public"
                                  (tidak ada FK ke tabel media)
Setting.site_logo / author_avatar ──(path)──> file yang sama di disk "public"
```

Cara membaca:

- `Post` adalah pusat domain: dimiliki satu `User`, boleh punya satu `Category`, banyak `Comment`, dan banyak `Tag`.
- `Comment` dapat menunjuk ke `Comment` lain lewat `parent_id` (maksimal dua tingkat di UI).
- `Media` adalah katalog file yang berdiri sendiri; artikel hanya menyimpan string path/URL.
- `MediaCategory` hanya mengelompokkan `Media`; menghapus kategori membuat `media_category_id` menjadi `null` (`nullOnDelete`), bukan menghapus file.

Detail penting:

- `Post::author()` memakai FK `user_id`.
- `Post::category()` nullable (`nullOnDelete`).
- `Comment::parent()` dan `Comment::replies()` membentuk thread dua tingkat.
- `Media` **tidak** memiliki relasi FK ke `Post`. Kolom `cover_image`/`og_image` menyimpan path relatif (mis. `cover/uuid.webp`) atau URL eksternal.
- Media memakai `SoftDeletes`; file fisik baru dihapus saat force-delete dan hanya jika tidak ada referensi di artikel, avatar, atau logo.

### 8.3 Scope dan method `Post`

| Nama                  | Jenis             | Perilaku                                                                                               |
| --------------------- | ----------------- | ------------------------------------------------------------------------------------------------------ |
| `published()`         | Scope             | `status = published` **dan** `published_at <= now()` **dan** tidak null                                |
| `coverImageUrl()`     | Method            | URL absolut cover; mendukung path relatif, legacy `storage/...`, URL `/storage/...`, dan URL eksternal |
| `ogImageUrl()`        | Method            | Sama seperti cover untuk OG image                                                                      |
| `cover_url`, `og_url` | Appended accessor | Otomatis ikut di JSON                                                                                  |
| `isPublished()`       | Method            | Cek visibilitas saat ini                                                                               |
| `booted()->saving`    | Hook              | Auto-generate slug dari judul; hitung `reading_time = ceil(kata/200)`, minimal 1                       |

### 8.4 Normalisasi media (`Media::diskPath`)

| Nilai tersimpan                             | Hasil `diskPath`   | URL akhir                          |
| ------------------------------------------- | ------------------ | ---------------------------------- |
| `uploads/a.webp`                            | `uploads/a.webp`   | `{APP_URL}/storage/uploads/a.webp` |
| `/uploads/a.webp`                           | `uploads/a.webp`   | idem                               |
| `storage/uploads/a.webp`                    | `uploads/a.webp`   | idem                               |
| `http://godang.test/storage/uploads/a.webp` | `uploads/a.webp`   | idem                               |
| `https://cdn.example.com/a.jpg`             | `null` (eksternal) | URL asli dipertahankan             |
| `null` / string kosong                      | `null`             | `null`                             |

### 8.5 Settings sebagai sumber konten publik

| Method                           | Perilaku                                                                                                             |
| -------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `Setting::DEFAULTS`              | 23 nilai default untuk seluruh key yang dikenal aplikasi                                                             |
| `Setting::allValues()`           | `array_merge(DEFAULTS, nilai DB)`; key yang belum tersimpan tetap tampil dengan default                              |
| `Setting::authorName()`          | Nama penulis dari `author_name` (di-trim, fallback `Godang`); dipakai accessor `Comment::name` untuk komentar author |
| `Media::urlFor($path)`           | Mengubah path media/URL eksternal menjadi URL absolut (dipakai avatar, logo & accessor `url`)                        |
| `Media::thumbnailPathFor($path)` | Menurunkan nama file thumbnail (`...-thumb.webp`) dari path asli                                                     |

`ShareSiteSettings` memakai method tersebut untuk membentuk `$site`: judul situs, deskripsi, tagline, meta default, logo terang/gelap, link sosial, avatar (path + URL + alt), nama & inisial penulis, serta key profil/About.

---

## 9. Skema Database

Database aktif: **MySQL**, nama `blog` (lihat `.env`; `.env.example` juga memakai `blog`).

### 9.1 `users`

| Kolom                      | Tipe                  | Catatan                    |
| -------------------------- | --------------------- | -------------------------- |
| `id`                       | bigint PK             |                            |
| `name`                     | string                |                            |
| `email`                    | string unique         |                            |
| `email_verified_at`        | timestamp nullable    |                            |
| `password`                 | string                | cast `hashed`              |
| `avatar`                   | string nullable       | ditambahkan migrasi profil |
| `bio`                      | text nullable         | ditambahkan migrasi profil |
| `is_admin`                 | boolean default false | penentu akses panel        |
| `remember_token`           | string nullable       |                            |
| `created_at`, `updated_at` | timestamp             |                            |

### 9.2 `categories`

| Kolom                      | Tipe          | Catatan                    |
| -------------------------- | ------------- | -------------------------- |
| `id`                       | bigint PK     |                            |
| `name`                     | string(255)   | nama kategori (teks biasa) |
| `slug`                     | string unique |                            |
| `description`              | text nullable | deskripsi kategori         |
| `created_at`, `updated_at` | timestamp     |                            |

### 9.3 `tags`

| Kolom                      | Tipe          | Catatan               |
| -------------------------- | ------------- | --------------------- |
| `id`                       | bigint PK     |                       |
| `name`                     | string(255)   | nama tag (teks biasa) |
| `slug`                     | string unique |                       |
| `created_at`, `updated_at` | timestamp     |                       |

### 9.4 `posts`

| Kolom                      | Tipe                        | Catatan                             |
| -------------------------- | --------------------------- | ----------------------------------- |
| `id`                       | bigint PK                   |                                     |
| `user_id`                  | FK users                    | cascade delete                      |
| `category_id`              | FK categories nullable      | null on delete                      |
| `title`                    | string                      |                                     |
| `slug`                     | string                      | unik global                         |
| `excerpt`                  | text nullable               |                                     |
| `content`                  | longtext nullable           | HTML dari editor                    |
| `cover_image`              | string nullable             | path/URL                            |
| `og_image`                 | string nullable             | path/URL                            |
| `status`                   | string default `draft`      | `draft` / `published` / `scheduled` |
| `published_at`             | timestamp nullable          |                                     |
| `meta_title`               | string nullable             |                                     |
| `meta_description`         | text nullable               |                                     |
| `reading_time`             | unsigned smallint default 0 | menit                               |
| `views_count`              | unsigned int default 0      |                                     |
| `deleted_at`               | timestamp nullable          | soft delete                         |
| `created_at`, `updated_at` | timestamp                   |                                     |

Index/constraint:

- `unique(['slug'])` — unik global, termasuk baris yang sudah soft-deleted.
- `index(['status', 'published_at'])` — mempercepat query publik.

### 9.5 `comments`

| Kolom                      | Tipe                     | Catatan                                                                                     |
| -------------------------- | ------------------------ | ------------------------------------------------------------------------------------------- |
| `id`                       | bigint PK                |                                                                                             |
| `post_id`                  | FK posts                 | cascade delete                                                                              |
| `parent_id`                | FK comments nullable     | cascade delete (self)                                                                       |
| `reply_to_name`            | string nullable          | nama yang dibalas                                                                           |
| `name`                     | string                   | nama tampilan; untuk `is_author = true`, accessor mengembalikan `author_name` dari Settings |
| `email`                    | string                   | disembunyikan di JSON publik                                                                |
| `body`                     | text                     |                                                                                             |
| `status`                   | string default `pending` | `pending` / `approved` / `spam`                                                             |
| `is_author`                | boolean default false    | komentar admin/penulis                                                                      |
| `notify_replies`           | boolean default false    | opt-in notifikasi                                                                           |
| `ip_hash`                  | string nullable          | SHA-256 IP                                                                                  |
| `user_agent`               | string nullable          |                                                                                             |
| `created_at`, `updated_at` | timestamp                |                                                                                             |

Index: `(post_id, status)`.

### 9.6 `media`

| Kolom                      | Tipe                              | Catatan                                                                          |
| -------------------------- | --------------------------------- | -------------------------------------------------------------------------------- |
| `id`                       | bigint PK                         |                                                                                  |
| `path`                     | string                            | path relatif disk `public`                                                       |
| `type`                     | string(10) default `image`, index | `image` atau `file`; index gabungan `(type, deleted_at)`                         |
| `original_name`            | string nullable                   | nama file asli dari browser; dipakai `download_name`                             |
| `thumb_path`               | string nullable                   | thumbnail WebP ≤480 px untuk gambar; `null` untuk file non-gambar                |
| `folder`                   | string default `uploads`, index   | `uploads` / `cover` / `avatar` / `files`; nilai lama bisa muncul sebagai `other` |
| `media_category_id`        | FK media_categories nullable      | null on delete                                                                   |
| `alt_text`                 | string nullable                   | default nama file asli saat upload                                               |
| `mime`                     | string(50) nullable               | `image/webp` untuk gambar; MIME asli untuk file non-gambar                       |
| `size`                     | unsigned int                      | byte                                                                             |
| `width`, `height`          | unsigned smallint nullable        | dimensi gambar setelah diskalakan; `null` untuk file non-gambar                  |
| `deleted_at`               | timestamp nullable                | soft delete (trash)                                                              |
| `created_at`, `updated_at` | timestamp                         |                                                                                  |

Index: `(type, deleted_at)`, `folder`, `media_category_id`.

### 9.7 `media_categories`

| Kolom                      | Tipe          | Catatan                                    |
| -------------------------- | ------------- | ------------------------------------------ |
| `id`                       | bigint PK     |                                            |
| `name`                     | string unique | kategori media kustom (bukan multi-bahasa) |
| `created_at`, `updated_at` | timestamp     |                                            |

### 9.8 `settings`

| Kolom                      | Tipe          | Catatan |
| -------------------------- | ------------- | ------- |
| `id`                       | bigint PK     |         |
| `key`                      | string unique |         |
| `value`                    | text nullable |         |
| `created_at`, `updated_at` | timestamp     |         |

Key yang dikenal beserta default-nya didefinisikan di `Setting::DEFAULTS` (23 key). Tidak perlu migrasi baru untuk menambah key; cukup menambahkannya ke `DEFAULTS` dan whitelist admin.

### 9.9 `post_tag`

| Kolom       | Tipe                | Catatan            |
| ----------- | ------------------- | ------------------ |
| `post_id`   | FK posts            | cascade delete     |
| `tag_id`    | FK tags             | cascade delete     |
| PK komposit | `(post_id, tag_id)` | mencegah duplikasi |

### 9.10 Tabel framework

| Tabel                                | Fungsi                                                                 |
| ------------------------------------ | ---------------------------------------------------------------------- |
| `sessions`                           | Sesi web (driver `database`)                                           |
| `cache`, `cache_locks`               | Cache (store `database`)                                               |
| `jobs`, `job_batches`, `failed_jobs` | Antrean (queue `database` lokal; `.env.example` deploy memakai `sync`) |
| `password_reset_tokens`              | Reset password (digunakan untuk forgot/reset password)                 |
| `personal_access_tokens`             | Token Sanctum (tersedia, tapi SPA memakai cookie sesi)                 |

---

## 10. Struktur Konten Satu Bahasa

### 10.1 Prinsip

- Seluruh situs publik berbahasa Indonesia dan disajikan dari URL root (`/`, `/blog`, `/about`, dst.).
- `config('app.locale')` dan `config('app.fallback_locale')` bernilai `id`.
- Tidak ada kolom bahasa pada tabel konten; judul, slug, deskripsi kategori, dan nama tag disimpan sebagai teks biasa.
- URL lama `/id/*` dan `/en/*` dipertahankan sebagai redirect `301` ke URL root yang setara agar tautan lama tidak mati.
- Prefix locale yang tidak dikenal (selain `id`/`en`) menghasilkan `404`.

### 10.2 Teks antarmuka

- Label UI publik berada di `resources/lang/id/*` dan sebagian di-hardcode langsung di Blade.
- Tidak ada lagi pemilihan teks berdasarkan locale atau language switcher di navbar.
- Halaman detail artikel hanya memuat `hreflang="id"` dan `hreflang="x-default"` yang menunjuk URL yang sama.

### 10.3 Kategori dan tag

- Nama kategori dan tag disimpan sebagai string biasa.
- Slug kategori/tag unik global dan tidak diterjemahkan.
- Halaman kategori menampilkan `description` bila tersedia; jika kosong, memakai teks generik berbahasa Indonesia.

---

## 11. Siklus Hidup Artikel

### 11.1 State status

```
                 +---------------------------+
                 |          draft            |
                 +-------------+-------------+
                               |
              publish (manual / published_at)
                               |
                               v
                 +---------------------------+
                 |        published          |
                 +-------------+-------------+
                               |
                 jadwalkan (status=scheduled)
                               |
                               v
                 +---------------------------+
                 |        scheduled          |
                 +-------------+-------------+
                               |
        posts:publish-scheduled (tiap menit)
        saat published_at <= now()
                               |
                               v
                        kembali ke published
```

Visibilitas publik hanya jika:

```php
status === 'published'
&& published_at !== null
&& published_at <= now()
```

### 11.2 Pembuatan artikel

`POST /api/admin/posts`

1. Validasi: `title` wajib, `status` wajib (`draft`/`published`/`scheduled`); `slug` unik global bila diisi.
2. `user_id` diisi dari user yang login.
3. Jika `slug` kosong: `Str::slug(title)` + pengecekan unik (termasuk baris soft-deleted).
4. Jika status `published` dan `published_at` kosong: diisi `now()`.
5. Tag diproses:
    - Angka / objek `{id}` → pakai ID yang ada.
    - String / objek `{name|value|label}` → cari berdasarkan slug; jika belum ada, buat tag baru.
6. Simpan post, lalu `tags()->sync()`.

### 11.3 Update artikel

- Semua field divalidasi dengan aturan `sometimes`.
- Slug `null` diabaikan (tidak menghapus slug).
- Jika diubah menjadi `published` dan belum punya `published_at`, diisi `now()`.
- Jika `tags` dikirim sebagai array kosong, semua tag dilepas (`sync([])`).
- `reading_time` dihitung ulang otomatis oleh hook `saving`.

### 11.4 Soft delete, restore, dan hapus permanen

| Aksi             | Endpoint                                   | Perilaku                                                |
| ---------------- | ------------------------------------------ | ------------------------------------------------------- |
| Soft delete      | `DELETE /api/admin/posts/{post}`           | `deleted_at` diisi; hilang dari publik dan daftar utama |
| Hapus permanen   | `DELETE ...?force=1`                       | `forceDelete()`                                         |
| Bulk soft delete | `POST /api/admin/posts/bulk-delete`        | `whereIn(...)->delete()`                                |
| Bulk permanent   | `POST .../bulk-delete` dengan `force=true` | `forceDelete()` per baris                               |
| Restore          | `POST /api/admin/posts/{post}/restore`     | `restore()`                                             |
| Lihat trash      | `GET /api/admin/posts?trashed=1`           | `onlyTrashed()`                                         |

Catatan: slug tetap “terpakai” oleh baris soft-deleted karena unique index database dan `uniqueSlug()` memakai `withTrashed()`.

### 11.5 Views dan reading time

- `views_count` tetap bertambah setiap `blogShow` dirender (bukan unik per pengunjung) dan disimpan di database.
- Jumlah dilihat **tidak ditampilkan** di halaman artikel publik; nilainya masih muncul di dashboard admin.
- `reading_time` = `max(1, ceil(jumlah_kata / 200))`, dihitung dari `strip_tags(content)`.

---

## 12. Pipeline Media dan Storage

### 12.1 Upload

Upload bercabang berdasarkan field `type`:

```
React (Media.jsx / MediaTrash.jsx / PostForm.jsx / Settings.jsx / MediaPicker.jsx)
  -> POST /api/admin/media  (multipart: file, type?, alt_text?, folder?, category_id?)
```

**Gambar (`type=image`, default)**

```
  -> validasi: image, mimes sesuai kemampuan GD server, max 5120 KB (5 MB),
     folder in [uploads, cover, avatar, files], category_id opsional (harus ada bila diisi)
  -> Intervention Image (driver GD)
       - baca byte file (bukan path temp) agar aman di shared hosting
       - decode, scaleDown(1600)  (tidak memperbesar gambar kecil)
       - simpan sebagai WebP
       - decode ulang byte yang sama, scaleDown(480), simpan thumbnail
  -> path: {folder}/{uuid}.webp
     thumb: {folder}/{uuid}-thumb.webp
  -> Storage::disk('public') => storage/app/public/{path}
  -> Media::create(type=image, original_name=...)
  -> response 201: { message, media, url }
```

**File non-gambar (`type=file`)**

```
  -> validasi: file, max 10240 KB (10 MB),
     mimes: pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,
     mimetypes sesuai allowlist per ekstensi, category_id opsional (harus ada bila diisi)
  -> path: files/{uuid}.{ext}   (nama asli tidak dipakai sebagai nama file)
  -> Storage::disk('public') => storage/app/public/files/{uuid}.{ext}
  -> Media::create(type=file, original_name=..., mime=...)
  -> response 201: { message, media, url }
```

Detail:

- Nama file selalu UUID, sehingga tidak ada konflik dan nama asli tidak bocor; nama asli disimpan di `original_name`.
- `alt_text` default = nama file asli.
- Gambar: `mime` selalu `image/webp`, termasuk thumbnail; lebar/tinggi diambil setelah resize; `size` = ukuran file hasil resize.
- File non-gambar: `thumb_path`, `width`, dan `height` bernilai `null`; `mime` mengikuti file asli.
- Daftar format gambar yang diterima diturunkan dari `imagetypes()` GD (biasanya jpg/jpeg/png/gif/webp; avif bila didukung).
- Allowlist file non-gambar: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `txt`, `csv`, `zip`. Ekstensi berbahaya (PHP, HTML, SVG, JS, eksekutabel) ditolak meskipun MIME dari klien terlihat benar.
- Jika file tidak bisa dibaca/didecode: `422` dengan pesan berbahasa Indonesia; file sementara yang sudah terlanjur ditulis dihapus.
- Batas ukuran aplikasi (5 MB gambar / 10 MB file) tetap bergantung pada `upload_max_filesize` dan `post_max_size` PHP di server.

### 12.2 Penyajian file

Dua mekanisme:

1. **Junction/symlink** `public/storage` → `storage/app/public`.
    - Dibuat dengan `php artisan storage:link`.
    - Web server menyajikan langsung.
2. **Fallback Laravel** `GET /storage/{path}` → `StorageFileController`.
    - Dipakai bila symlink tidak tersedia.
    - Hanya mengizinkan `uploads/...`, `cover/...`, `avatar/...`, atau `files/...` dengan nama file `[A-Za-z0-9._-]+`.
    - `404` untuk path lain (termasuk subfolder tambahan dan `..`).
    - Header: `Cache-Control: public, max-age=31536000, immutable` dan `X-Content-Type-Options: nosniff`.
    - Khusus `files/...`, ditambahkan `Content-Disposition: inline; filename="{nama file di disk}"`.

URL publik dibentuk oleh `Storage::disk('public')->url($path)` → `{APP_URL}/storage/{path}`.

Karena `public/storage` dan `public/build` adalah path statis yang benar-benar ada, web server biasanya menyajikannya langsung tanpa melewati Laravel. Fallback route hanya relevan bila symlink/junction tidak tersedia atau server dikonfigurasi meneruskan semua request ke `index.php`. Langkah setup lengkap ada di [12.6](#126-cara-setting-storage).

### 12.3 Menyimpan referensi di artikel

- `cover_image` dan `og_image` menyimpan **path relatif** (disarankan) atau URL eksternal.
- `PostForm.jsx` menormalkan URL absolut `/storage/...` kembali menjadi path relatif saat menyimpan (`toStoredPath`).
- `Post` mengubah path menjadi URL absolut saat dibaca (`cover_url`, `og_url`).
- Gambar di dalam konten disimpan sebagai HTML `<img src="{url}">` (ditulis manual di textarea); URL inilah yang dipakai deteksi pemakaian media.
- File non-gambar tidak disisipkan otomatis ke artikel. Admin cukup menyalin URL dari tab **File**; tidak ada halaman listing/attachment publik untuk file. Usage tracking hanya berlaku untuk gambar (cover/OG/konten/settings).

### 12.4 Pemakaian, trash, restore, dan hapus permanen

`GET /api/admin/media` menambahkan `usage_count` dan `usage_labels` per item. Pemindaian mencakup:

- `posts.cover_image`, `posts.og_image`, dan `posts.content` (termasuk artikel soft-deleted).
- Settings `author_avatar`, `site_logo`, dan `site_logo_dark`.

File non-gambar (`type=file`) tidak dipindai karena tidak dirujuk dari konten/settings; `usage_count`-nya selalu `0` dan force-delete tetap menghapus file fisiknya.

Filter `unused=1` memakai `NOT EXISTS` ke `posts` dan `settings`; filter `trashed=1` memakai `onlyTrashed()`.

Alur hapus:

1. `DELETE /api/admin/media/{media}` → **soft delete** (`deleted_at`), file tetap di disk.
2. `POST /api/admin/media/{media}/restore` → pulihkan.
3. `DELETE /api/admin/media/{media}/force` → hapus permanen **hanya jika** `usage_count = 0`; jika masih dipakai respons **409** berisi `usage` (jumlah + label referensi).
4. Bulk: `bulk-delete`, `bulk-restore`, `bulk-force-delete`. Bulk force-delete memeriksa seluruh batch; bila ada yang masih dipakai, seluruh batch ditolak **409** dengan daftar `blocked`.
5. Saat force-delete, file asli **dan** thumbnail dihapus dari disk `public` (path `..` ditolak).

Perhatian: menghapus referensi di artikel/settings adalah langkah manual. Media yang masih dipakai tidak bisa dihapus permanen sampai referensinya dilepas.

### 12.5 Perintah media

```bash
php artisan media:backfill --force --prune-missing
php artisan media:thumbnails --force
```

`media:backfill`:

- Menormalkan path lama (`storage/...`, URL absolut) menjadi path relatif disk `public`.
- Mendaftarkan file `cover/` yang belum punya baris `media`.
- `--prune-missing`: membersihkan referensi gambar artikel yang file lokalnya sudah tidak ada.
- `--force`: menjalankan tanpa konfirmasi.

`media:thumbnails`:

- Membuat thumbnail untuk baris `media` yang `thumb_path`-nya kosong atau filenya hilang.
- Membaca file asli dari disk `public`, `scaleDown(480)`, lalu menyimpan `-thumb.webp`.
- `--force`: memproses ulang walau thumbnail sudah ada.
- Melewati file yang hilang/tidak bisa didecode dan melaporkan hasilnya di akhir.

### 12.6 Cara setting storage

Storage media **tidak memerlukan perubahan `.env`**. Disk yang dipakai selalu `public` dan didefinisikan di `config/filesystems.php`:

| Konfigurasi  | Nilai                |
| ------------ | -------------------- |
| `root`       | `storage/app/public` |
| `url`        | `{APP_URL}/storage`  |
| `visibility` | `public`             |

`FILESYSTEM_DISK` hanya menentukan disk default Laravel untuk kebutuhan lain; kode media selalu memanggil `Storage::disk('public')`. Karena itu nilai `FILESYSTEM_DISK=local` di `.env` tidak memengaruhi media.

Langkah setup:

1. **Siapkan folder** di `storage/app/public/`: `uploads/`, `cover/`, `avatar/`, dan `files/`. Folder dibuat otomatis saat upload pertama, tetapi membuatnya manual memudahkan pengecekan izin.
2. **Buat junction/symlink** dari `public/storage` ke `storage/app/public`:

    ```bash
    php artisan storage:link
    ```

    Di Windows, junction bisa rusak setelah clone ulang. Jika perintah menolak karena link sudah ada, hapus dulu `public/storage`, lalu jalankan ulang.

3. **Pastikan izin tulis** untuk web server pada `storage/` (khususnya `storage/app/public`) dan `storage/framework`.
4. **Pastikan `APP_URL` benar.** URL file dibentuk dari `APP_URL` + `/storage`, jadi `APP_URL` yang salah membuat gambar gagal dimuat meski file ada.
5. **Verifikasi**: unggah satu gambar di `/admin/media`, lalu buka URL yang disalin (format `{APP_URL}/storage/{folder}/{uuid}.webp`).

Jika `storage:link` tidak tersedia (mis. shared hosting tanpa symlink), file tetap disajikan lewat fallback route `GET /storage/{path}` (`StorageFileController`) selama path berada di `uploads/`, `cover/`, `avatar/`, atau `files/`.

Perbaikan cepat:

| Masalah                               | Tindakan                                                                  |
| ------------------------------------- | ------------------------------------------------------------------------- |
| Gambar 404 setelah clone/pindah mesin | `php artisan storage:link`                                                |
| Path media lama/absolut               | `php artisan media:backfill --force --prune-missing`                      |
| Thumbnail tidak muncul                | `php artisan media:thumbnails`                                            |
| File tidak bisa diunggah              | cek izin `storage/app/public` serta `upload_max_filesize`/`post_max_size` |

Yang tidak boleh di-commit: `public/storage` (junction) dan isi `storage/app/public/uploads|cover|avatar|files` (media pengguna).

---

## 13. Sistem Komentar

### 13.1 Komentar publik

Form ada di `resources/views/public/blog/show.blade.php` dan `partials/comment-thread.blade.php`.

Alur `POST /api/posts/{post}/comments`:

1. Pastikan artikel published (`abort_unless($post->isPublished(), 404)`).
2. **Honeypot**: field `website` harus kosong. Jika terisi → `422` "Spam terdeteksi."
3. Tentukan identitas: jika request berasal dari admin (`is_admin = true`), `name`/`email` tidak divalidasi dan diambil dari server; selain itu `name` (max 100) dan `email` wajib. Field `body` (max 2000), `parent_id` (opsional, harus ada), dan `notify_replies` (boolean) selalu divalidasi.
4. Maksimal **2 link** (`http://`/`https://`) dalam body; lebih dari itu ditolak.
5. Jika balasan:
    - Parent harus milik post yang sama.
    - `reply_to_name` diisi nama parent.
    - Jika parent adalah balasan, komentar baru di-**re-parent** ke root (`parent_id ?? id`) agar thread tetap dua tingkat.
6. Status:
    - Komentar admin/author → selalu `approved` dengan `is_author = true`.
    - `comment_moderation = true` → `pending`
    - selain itu → `approved`
7. Simpan `ip_hash = sha256(ip)` dan `user_agent`. Nama yang disimpan untuk komentar admin adalah `Setting::authorName()` (bukan nama akun).
8. Kirim notifikasi (lihat 13.3). Notifikasi ke admin hanya dipicu komentar pembaca, bukan komentar author.
9. Response `201` dengan pesan berbahasa Indonesia.

Rate limit: **6 request/menit** per IP (middleware `throttle:6,1`).

### 13.2 Menampilkan komentar

- `blogShow` mengambil semua komentar `approved`, lalu membentuk struktur root + replies di memori.
- Endpoint `GET /api/posts/{post}/comments` mengembalikan root comments dengan relasi `replies` yang juga `approved`.
- Nama komentar author dibaca lewat accessor `Comment::name`: untuk `is_author = true`, nilainya adalah `Setting::authorName()`. Baris lama yang tersimpan sebagai "Admin" otomatis tampil dengan `author_name` terbaru tanpa migrasi data.
- `email`, `ip_hash`, dan `user_agent` disembunyikan dari serialisasi (`#[Hidden]`).

### 13.3 Notifikasi

| Notifikasi                   | Penerima                     | Kapan                                                                                   | Kanal         |
| ---------------------------- | ---------------------------- | --------------------------------------------------------------------------------------- | ------------- |
| `NewCommentNotification`     | Semua user `is_admin = true` | Setiap komentar pembaca baru (bukan komentar author), jika `comment_notification` aktif | Mail (queued) |
| `ReplyToCommentNotification` | Email parent comment         | Jika parent `notify_replies = true`                                                     | Mail (queued) |

Keduanya `ShouldQueue`. Dengan `QUEUE_CONNECTION=database`, email baru benar-benar terkirim setelah queue worker berjalan. `MAIL_MAILER=log` membuat email ditulis ke log, bukan dikirim.

Kegagalan pengiriman notifikasi balasan ditangkap (`try/catch`) agar tidak menggagalkan request komentar. Komentar/balasan yang dibuat admin tidak memicu notifikasi ke admin.

### 13.4 Moderasi admin

| Aksi       | Endpoint                                   | Efek                                                                                  |
| ---------- | ------------------------------------------ | ------------------------------------------------------------------------------------- |
| List       | `GET /api/admin/comments`                  | Default hanya root; filter `status`, `post_id`, `category_id`; email di-`makeVisible` |
| Approve    | `PATCH /api/admin/comments/{comment}`      | `status = approved`                                                                   |
| Edit isi   | `PUT /api/admin/comments/{comment}`        | Update `body`                                                                         |
| Spam       | `PATCH /api/admin/comments/{comment}/spam` | Spam untuk komentar + semua balasannya                                                |
| Balas      | `POST /api/admin/comments/{comment}/reply` | Komentar baru `is_author = true`, `approved`, nama author                             |
| Hapus      | `DELETE /api/admin/comments/{comment}`     | Hapus balasan lalu komentar                                                           |
| Bulk hapus | `POST /api/admin/comments/bulk-delete`     | Hapus balasan lalu komentar terpilih                                                  |

Komentar buatan admin (`store`/`reply`) memakai `Setting::authorName()` untuk `name`, email user admin, `is_author = true`, dan langsung `approved`. Di UI, komentar author menampilkan **Edit** (bukan Spam); balasan tingkat 2 juga punya **Hapus** di `admin/comments`, sedangkan `PostComments` (pratinjau artikel) menyembunyikan Spam untuk komentar author.

---

## 14. Autentikasi, Otorisasi, dan Keamanan

### 14.1 Mekanisme login

- SPA memakai **session cookie**, bukan token bearer.
- `bootstrap/app.php` memanggil `$middleware->statefulApi()` untuk mengaktifkan mode SPA Sanctum.
- Axios di admin:

```js
axios.create({
    baseURL: "/api",
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
    withXSRFToken: true,
});
```

- Login: `POST /api/auth/login` → validasi Turnstile (bila `TURNSTILE_SECRET_KEY` diisi) → `Auth::attempt([...credentials, 'is_admin' => true])`.
- Sukses: `session()->regenerate()`.
- Logout: guard `web` logout, invalidasi sesi, regenerate token.
- Guest diarahkan ke `/admin/login` (`redirectGuestsTo`).

#### Cloudflare Turnstile

- Key dibaca dari `config('services.turnstile.*')` (`TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`).
- Frontend: site key dikirim lewat `<meta name="turnstile-site-key">` di `admin.blade.php`; `TurnstileWidget.jsx` me-render widget (tema mengikuti dark mode) dan mengirim token sebagai `cf_turnstile_response`.
- Backend: `App\Rules\Turnstile` memverifikasi token ke `https://challenges.cloudflare.com/turnstile/v0/siteverify`; `AuthController@login` hanya menambahkan aturan wajib bila secret key terisi.
- Bila secret key kosong (lokal/test), verifikasi dilewati sehingga login tetap berjalan. Cara menonaktifkan Turnstile secara lengkap ada di [14.5](#145-cara-menonaktifkan-turnstile).

### 14.2 Lapisan otorisasi

| Lapisan            | Lokasi                              | Fungsi                                             |
| ------------------ | ----------------------------------- | -------------------------------------------------- |
| Kredensial admin   | `AuthController@login`              | Hanya user `is_admin = true` yang bisa login       |
| Middleware `auth`  | route `/admin/{path?}`              | Wajib login untuk semua halaman admin selain login |
| Middleware `admin` | route admin web + seluruh API admin | `EnsureUserIsAdmin` → `403` bila bukan admin       |
| `auth:sanctum`     | API admin & auth                    | Validasi sesi                                      |

### 14.3 Proteksi lain

- CSRF aktif untuk route web dan API stateful (form publik memakai `@csrf`).
- Rate limit login: 5/menit per email+IP.
- Rate limit komentar: 6/menit per IP.
- Honeypot komentar + batas 2 link.
- Path traversal dicegah pada fallback storage (regex) dan hapus media (`..`).
- `email`, `ip_hash`, `user_agent` komentar tidak pernah keluar di API publik.
- Komentar author memakai identitas server: `name` diisi `Setting::authorName()` dan email diambil dari user login; field `name`/`email` kiriman klien diabaikan.
- Konten artikel dirender mentah (`{!! $post->content !!}`) → **hanya boleh berasal dari admin yang tepercaya**. Body komentar dirender escaped (`{{ }}`).
- `robots.txt` melarang `/api/` dan `/admin`; shell admin juga memakai `noindex, nofollow`.
- Folder `storage/` dan `public/build` mengikuti aturan `.gitignore` standar Laravel.

### 14.4 Mengubah Email dan Password Admin

Saat ini aplikasi **tidak memiliki UI** di panel admin untuk mengelola user/admin. Untuk mengganti email dan password akun admin, gunakan salah satu cara berikut:

| Opsi                        | Kapan dipakai                                       | Langkah                                                                                                                                                                          |
| --------------------------- | --------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **1. Edit seeder**          | Jika ingin reset data dengan `migrate:fresh --seed` | Ubah pembuatan user admin di `database/seeders/DatabaseSeeder.php`. Pastikan password di-hash (bisa pakai `Hash::make()` atau memanfaatkan cast `hashed` pada model `User`).     |
| **2. Gunakan Tinker**       | Cepat, tanpa reset database                         | Jalankan `php artisan tinker`, lalu perbarui user: `User::where('email', 'admin@godang.com')->update(['email' => 'email@baru.com', 'password' => Hash::make('password_baru')]);` |
| **3. Langsung di database** | Jika tidak ingin pakai Artisan                      | Update tabel `users` langsung. Pastikan kolom `password` diisi dalam bentuk hash bcrypt/argon yang valid.                                                                        |

**Catatan:**

- Akun admin default dibuat oleh `DatabaseSeeder.php` dengan email `admin@godang.com` dan password default dari `UserFactory` (`password`).
- Model `User` sudah memiliki cast `hashed` untuk kolom `password`, sehingga aman jika diisi dengan string biasa saat menggunakan factory, namun untuk update manual pastikan di-hash.
- Hanya user dengan `is_admin = true` yang bisa login ke panel admin (lihat [14.1](#141-mekanisme-login) dan [14.2](#142-lapisan-otorisasi)).

### 14.5 Cara menonaktifkan Turnstile

Turnstile bersifat opsional dan tidak perlu mengubah kode:

- Backend hanya mewajibkan token bila `TURNSTILE_SECRET_KEY` terisi (`Turnstile::isEnabled()`).
- Widget hanya dirender bila `TURNSTILE_SITE_KEY` terisi (`TurnstileWidget` mengembalikan `null` bila site key kosong).

Langkah menonaktifkan:

1. Kosongkan kedua key di `.env`:

    ```dotenv
    TURNSTILE_SITE_KEY=
    TURNSTILE_SECRET_KEY=
    ```

2. Bersihkan cache config bila pernah di-cache:

    ```bash
    php artisan config:clear
    ```

3. Muat ulang `/admin/login` (hard refresh). Widget hilang dan login cukup memakai email + password.

Catatan:

- Mengosongkan **hanya** `TURNSTILE_SECRET_KEY` mematikan validasi backend, tetapi widget tetap tampil dan tokennya diabaikan.
- Mengosongkan **hanya** `TURNSTILE_SITE_KEY` menyembunyikan widget, tetapi backend tetap mewajibkan token sehingga login akan gagal. Kosongkan keduanya.
- Untuk mengaktifkan kembali, isi kedua key dari dashboard Cloudflare lalu jalankan `php artisan config:clear`; tidak ada perubahan kode.
- Perilaku ini tercakup di `tests/Feature/AuthLoginTest.php` (kasus wajib, valid, invalid, dan dilewati).

---

## 15. Settings Aplikasi

Settings disimpan di tabel `settings` (key-value) dan menjadi sumber utama konten situs publik.

```php
Setting::getValue('comment_moderation', 'true');
Setting::setValue('comment_moderation', 'false');
Setting::allValues();                                  // DEFAULTS + nilai DB
```

### 15.1 Default dan penggabungan nilai

- `Setting::DEFAULTS` berisi **23 key** beserta nilai default-nya.
- `Setting::allValues()` mengambil seluruh baris `settings`, lalu `array_merge(DEFAULTS, $stored)`.
- Key yang belum pernah disimpan tetap memakai default. Key yang disimpan sebagai `null`/string kosong (admin mengosongkannya) **menimpa** default menjadi kosong.
- `SettingSeeder` menyemai semua key dari `DEFAULTS`, sehingga instalasi baru maupun lama menampilkan konten yang sama tanpa langkah tambahan.
- Menambah key baru tidak butuh migrasi: cukup tambahkan ke `DEFAULTS` dan whitelist `SettingController`.

### 15.2 Key yang dikenal

| Kelompok     | Key                                                                                                                                                                                         | Dipakai di                                                                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Situs        | `site_title`, `site_logo`, `site_logo_dark`, `site_logo_alt`, `site_description`, `tagline`, `default_meta_title`, `default_meta_description`                                               | `<title>`, meta/OG, navbar, footer, branding login/admin                                             |
| Sosial       | `github_url`, `twitter_url`, `linkedin_url`                                                                                                                                                 | navbar, footer, About; kosong = tidak dirender                                                       |
| Komentar     | `comment_moderation`, `comment_notification`                                                                                                                                                | status komentar baru & email admin                                                                   |
| Profil/About | `author_name`, `author_avatar`, `author_avatar_alt`, `author_role`, `about_bio`, `about_topics_heading`, `about_topics`, `about_contact_heading`, `about_contact`, `about_meta_description` | halaman About, JSON-LD Person, nama komentar author, badge **Mode Admin**, dan nama di sidebar admin |

Total: **23 key** (lihat `Setting::DEFAULTS`).

Teks hero beranda dan header halaman Blog di-hardcode di Blade dan tidak dapat diubah dari panel admin.

Format konten teks biasa:

- Bio: paragraf dipisah **baris kosong** (`\R{2,}`).
- Topik: satu item per baris.
- Semua output di-escape Blade (`{{ }}`) — settings tidak pernah dirender sebagai HTML mentah.

### 15.3 Validasi dan update

`PUT /api/admin/settings`

```json
{
    "settings": {
        "site_title": "Godang",
        "comment_moderation": "false",
        "github_url": "https://github.com/godang"
    }
}
```

- `settings` wajib berupa array; key di luar daftar `DEFAULTS` → `422` "Kunci pengaturan tidak dikenal: ...".
- `URL_KEYS` (`github_url`, `twitter_url`, `linkedin_url`) divalidasi `url`.
- `BOOLEAN_KEYS` (`comment_moderation`, `comment_notification`) hanya menerima `true`/`false`.
- `MAX_LENGTHS` membatasi panjang per key (mis. bio 5000, meta description 300, deskripsi situs 500, judul 150).
- Nilai string di-`trim`; string kosong dari form dikonversi middleware Laravel menjadi `null`.
- Update bersifat batch, hanya menulis key yang dikirim, dan idempoten (`updateOrCreate`).

### 15.4 Avatar penulis dan logo situs

- Avatar diunggah lewat `POST /api/admin/media` dengan `folder=avatar` (WebP, maks. 5 MB, sisi maks. 1600 px).
- `author_avatar` menyimpan **path relatif** (mis. `avatar/uuid.webp`); `author_avatar_alt` menyimpan alt text.
- `Media::urlFor()` mengubah path menjadi `{APP_URL}/storage/...`; URL eksternal dibiarkan apa adanya.
- Bila kosong, halaman About dan section About beranda menampilkan huruf awal `author_name` (`author_initial`).
- Logo situs punya dua key: `site_logo` (mode terang) dan `site_logo_dark` (mode gelap), plus `site_logo_alt`. Keduanya menyimpan path relatif/URL dan dipakai di navbar publik serta branding shell admin (`admin.blade.php`).
- Logo juga diunggah lewat Media (folder `avatar` atau `uploads`) dan **tidak** disalin; mengganti logo hanya menulis ulang path di settings.

### 15.5 Cara settings dibagikan ke view publik

`app/Http/Middleware/ShareSiteSettings.php` terpasang pada halaman publik, `/feed.xml`, `/admin/login`, dan `/admin/{path?}`:

1. Memuat `Setting::allValues()`.
2. Menyusun `$site`: judul, deskripsi, tagline, meta default, logo terang/gelap, link sosial, avatar (path/URL/alt), nama & inisial penulis, serta key profil/About.
3. `view()->share('site', $site)`.

View memakai `$site[...]` dengan fallback `config('app.name')`, sehingga halaman di luar cakupan middleware (mis. error 404) tetap aman. Perubahan dari panel admin langsung berlaku pada request publik (dan shell admin) berikutnya.

---

## 16. Frontend Publik (Blade)

### 16.1 Layout

`resources/views/layouts/public.blade.php`

- `<html lang="id">`.
- Meta: title, description, canonical, Open Graph (`og:site_name`), hreflang (via section), JSON-LD (di halaman detail). Judul dan deskripsi default diambil dari `$site`.
- `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- Script anti-FOUC: menambahkan class `dark` kecuali `localStorage.theme === 'light'` (default gelap).
- Navbar + `@yield('content')` + footer.
- Toggle tema dan tombol salin kode ditangani inline/JS.

Sumber konten: seluruh teks situs dibaca dari `$site` yang dibagikan middleware (lihat [15.5](#155-cara-settings-dibagikan-ke-view-publik)).

| Bagian               | Key settings                                                                                                                                                                            |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Navbar & footer      | Navbar: `site_logo`/`site_logo_dark` (+ `site_logo_alt`) dengan fallback `site_title`. Footer: `site_title`, `site_description`, `tagline`, `github_url`, `twitter_url`, `linkedin_url` |
| Judul & meta halaman | `site_title`, `default_meta_description`, meta spesifik halaman                                                                                                                         |
| Hero beranda         | Teks hero & section About di-hardcode di `public/landing.blade.php`; avatar/nama penulis dari `$site`                                                                                   |
| Header blog          | Judul, deskripsi, dan meta di-hardcode di `public/blog/index.blade.php` dan feed RSS                                                                                                    |
| Halaman About        | `author_name`, `author_avatar(_alt)`, `author_role`, `about_*`                                                                                                                          |

### 16.2 Halaman

| View                          | Route              | Data utama                                                                                                  |
| ----------------------------- | ------------------ | ----------------------------------------------------------------------------------------------------------- |
| `public/landing.blade.php`    | `/`                | Teks hero + section About hardcoded, avatar penulis dari `$site`; 6 artikel terbaru, kategori + jumlah post |
| `public/blog/index.blade.php` | `/blog`            | Header hardcoded; paginator 12, filter `q`/`category`/`tag`, daftar kategori                                |
| `public/blog/show.blade.php`  | `/blog/{slug}`     | Post, komentar, total komentar, 2 related, JSON-LD                                                          |
| `public/about.blade.php`      | `/about`           | Profil dari `$site`: avatar (fallback inisial), nama, role, bio, topik, kontak, sosial, JSON-LD Person      |
| `public/category.blade.php`   | `/category/{slug}` | Kategori + paginator 12                                                                                     |
| `public/tag.blade.php`        | `/tag/{slug}`      | Tag + paginator 12                                                                                          |
| `seo/sitemap.blade.php`       | `/sitemap.xml`     | Daftar URL                                                                                                  |
| `seo/feed.blade.php`          | `/feed.xml`        | 20 artikel terbaru; judul dari `site_title`, deskripsi hardcoded                                            |
| `errors/404.blade.php`        | error 404          | Halaman error kustom                                                                                        |

### 16.3 Partial

| Partial                             | Isi                                                                                                                                                                            |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `partials/navbar.blade.php`         | Logo (`site_logo` terang / `site_logo_dark` gelap, fallback ke `site_title`), menu Blog/About, badge **Mode Admin** (khusus admin, menuju `/admin`), theme toggle, menu mobile |
| `partials/footer.blade.php`         | Info situs dari `$site`, link sosial (kosong = disembunyikan), copyright                                                                                                       |
| `partials/post-card.blade.php`      | Kartu artikel dengan cover fallback yang benar                                                                                                                                 |
| `partials/pagination.blade.php`     | Paginasi kustom                                                                                                                                                                |
| `partials/comment-thread.blade.php` | Render balasan komentar (rekursif satu tingkat); saat mode admin menampilkan tombol **Spam**/**Hapus** dan menyembunyikan field nama/email                                     |

### 16.4 JavaScript publik

`resources/js/app.js` hanya menangani blok kode artikel:

1. Menunggu `DOMContentLoaded`.
2. Mencari `#article-content`.
3. Import dinamis `highlight.js/lib/common`.
4. Highlight setiap `pre code`.
5. Membungkus `pre` dengan `.code-block-wrapper` dan menambahkan tombol salin.
6. Label tombol salin ditulis langsung dalam bahasa Indonesia (`Salin`, `Tersalin!`, `Gagal`).

Interaksi lain ditulis inline di Blade:

- Toggle tema + persistensi `localStorage.theme`.
- Tombol hapus pencarian di halaman Blog.
- Indikator progres baca artikel.
- Form komentar (fetch + update thread) di `public/blog/show.blade.php`, termasuk submit sebagai author tanpa field nama/email dan tombol admin **Spam**/**Hapus**.
- Menu mobile.

### 16.5 CSS

`resources/css/app.css`:

- `@import 'tailwindcss'`.
- `@theme` mendefinisikan token warna: `--color-bg`, `--color-surface`, `--color-border`, `--color-text`, `--color-text-muted`, `--color-accent`, `--color-accent-bg`, `--color-on-accent`, `--color-success`, `--color-warning`, `--color-error`.
- `.dark` menimpa token yang sama.
- Gaya khusus: `.prose-article` untuk isi artikel (heading dengan `scroll-margin-top` untuk anchor Daftar Isi), `.heading-anchor`, `.code-block-wrapper`, `.copy-button`, `.comment-card`, `.toast`, animasi (`fadeInUp`, `fadeIn`, `slideDown`, `pulse-subtle`).
- Font dimuat lewat plugin `laravel-vite-plugin/fonts` (Bunny): Inter, Plus Jakarta Sans, JetBrains Mono.

---

## 17. Admin SPA (React)

### 17.1 Bootstrap SPA

```
GET /admin/*  ->  resources/views/admin.blade.php
                    -> @vite(['resources/css/app.css', 'resources/js/admin/main.jsx'])
                    -> <div id="admin-root"></div>

main.jsx -> createRoot(...).render(<StrictMode><App /></StrictMode>)
App.jsx  -> QueryClientProvider + BrowserRouter + Routes
```

**Editor HTML mentah (PostForm)**

- Editor konten adalah `<textarea>` HTML mentah, bukan rich text. Nilai `content` disimpan apa adanya (HTML). Hasil render dilihat lewat halaman terpisah `/admin/posts/{id}/view` (`PostView`), bukan pratinjau di dalam form.
- Saat mengetik spasi setelah snippet, `onKeyDown` mengganti teks pemicu dengan markup:
  `/p`, `/div`, `/h2`, `/h3`, `/h4`, `/ul`, `/ol`, `/br`, `/hr`, `/pre` (blok `language-php`), dan `/code`.
- Kursor dipindahkan ke dalam tag penutup setelah snippet disisipkan.
- Sisa kode dan dependensi TipTap sudah dihapus; halaman admin di-_code split_ lewat `React.lazy` sehingga `PostForm` dimuat sebagai chunk terpisah.

### 17.2 Struktur file

| File                                  | Peran                                                                                                                                                                                                                                         |
| ------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `main.jsx`                            | Entry point React; memuat `highlight.js` CSS untuk pratinjau admin                                                                                                                                                                            |
| `App.jsx`                             | Router, `QueryClient`, `AuthGate`, `LoginRoute`                                                                                                                                                                                               |
| `api.js`                              | Instance axios, interceptor 401, helper error, daftar endpoint                                                                                                                                                                                |
| `media.js`                            | Helper `resolveMediaUrl()` dan `toStoredPath()` yang dipakai bersama halaman admin                                                                                                                                                            |
| `stores/auth.js`                      | Zustand: `user`, `status`, `fetchMe`, `login`, `logout`, `setGuest`                                                                                                                                                                           |
| `components/Shell.jsx`                | Sidebar, header (termasuk link **Home** ke situs publik), outlet, menu mobile, logout                                                                                                                                                         |
| `components/Toast.jsx`                | Notifikasi sukses/error                                                                                                                                                                                                                       |
| `components/ConfirmModal.jsx`         | Dialog konfirmasi                                                                                                                                                                                                                             |
| `components/StatusBadge.jsx`          | Badge status artikel/komentar                                                                                                                                                                                                                 |
| `components/MediaPicker.jsx`          | Modal pilih/unggah media **gambar** (`type=image`) dengan filter kategori dan pagination (dipakai Settings dan PostForm)                                                                                                                      |
| `components/MediaEditModal.jsx`       | Ubah alt text dan kategori satu media                                                                                                                                                                                                         |
| `components/MediaCategoryManager.jsx` | CRUD kategori media dari halaman Media                                                                                                                                                                                                        |
| `components/PostPreview.jsx`          | Render artikel gaya blog dari HTML mentah: header, `prose-article`, sidebar **Daftar Isi** sticky, heading anchor, highlight kode + tombol salin; menerima `children` (komentar) agar TOC tetap sticky melewati komentar (dipakai `PostView`) |
| `components/PostComments.jsx`         | Panel moderasi komentar pada halaman pratinjau artikel; komentar author menampilkan **Edit** (tanpa Spam)                                                                                                                                     |
| `components/TurnstileWidget.jsx`      | Widget Cloudflare Turnstile untuk form login (no-op bila site key kosong)                                                                                                                                                                     |
| `pages/*.jsx`                         | 13 halaman admin                                                                                                                                                                                                                              |

### 17.3 Alur auth di React

```
App dimuat
  -> AuthGate: status = 'loading'
  -> fetchMe() -> GET /api/auth/me
       - sukses & is_admin -> status 'admin'
       - gagal             -> status 'guest'
  -> guest  -> <Navigate to="/admin/login" />
  -> admin  -> render <Shell /> + halaman
```

- Interceptor axios: response `401` memanggil `window.__adminAuth.setGuest()` agar UI langsung kembali ke login.
- `Login.jsx` memanggil `useAuth.login(email, password, turnstileToken)`.
- Setelah login, user diarahkan ke halaman asal atau `/admin`.

### 17.4 Data fetching

- Semua query dan mutation memakai TanStack Query.
- Query key yang umum:

| Key                                               | Data                                       |
| ------------------------------------------------- | ------------------------------------------ |
| `['dashboard']`                                   | Statistik                                  |
| `['posts', searchParams.toString()]`              | Daftar artikel                             |
| `['posts', 'trash', searchParams.toString()]`     | Daftar artikel di trash                    |
| `['post', id]`                                    | Detail artikel untuk form edit             |
| `['posts', 'view', id]`                           | Data artikel untuk halaman pratinjau       |
| `['comments', status, categoryId]`                | Komentar                                   |
| `['categories']`                                  | Kategori                                   |
| `['tags']`                                        | Tag                                        |
| `['media', searchParams.toString()]`              | Media (pencarian, filter, urutan, halaman) |
| `['media', 'trash', searchParams.toString()]`     | Media di trash                             |
| `['media-picker', query, folder, category, page]` | Modal pemilih media                        |
| `['media-categories']`                            | Kategori media kustom                      |
| `['settings']`                                    | Settings                                   |

- Setelah mutation, halaman terkait memanggil `invalidateQueries` untuk key yang terpengaruh.
- `errorMessage(error)` di `api.js` mengambil pesan validasi pertama dari response.

### 17.5 Halaman admin

| Halaman         | Fitur utama                                                                                                                                                                                                                                                                                                                               |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Dashboard`     | Total/published/draft/scheduled post, total views, komentar pending/total, 5 post & komentar terbaru                                                                                                                                                                                                                                      |
| `Posts`         | Tabel, filter `q`/`status`, paginasi via query string, pilih banyak, bulk delete, link ke edit/view/trash                                                                                                                                                                                                                                 |
| `Trash`         | Daftar soft-deleted, restore, hapus permanen, bulk                                                                                                                                                                                                                                                                                        |
| `PostForm`      | Form create/edit lengkap (lihat 17.6)                                                                                                                                                                                                                                                                                                     |
| `PostView`      | Pratinjau artikel bergaya blog publik: Daftar Isi sticky (bertahan sampai komentar), highlight kode + tombol salin, dan panel moderasi komentar                                                                                                                                                                                           |
| `Comments`      | Filter status/kategori, approve, spam, balas, edit isi, hapus, bulk, tampilkan email; komentar author menampilkan **Edit** (bukan Spam), balasan punya **Hapus**                                                                                                                                                                          |
| `Categories`    | CRUD dengan nama, slug, dan deskripsi (max 500 karakter)                                                                                                                                                                                                                                                                                  |
| `Tags`          | CRUD tag                                                                                                                                                                                                                                                                                                                                  |
| `Media`         | Tab **Gambar** dan **File** (data tidak pernah tercampur): grid media, filter folder (`uploads`/`cover`/`avatar`/`files`/lainnya), kategori kustom (`none` = tanpa kategori), `unused`, pencarian nama/alt/path, urutan, pagination; upload gambar atau file non-gambar, ubah alt/kategori, salin URL, trash, bulk trash; kelola kategori |
| `MediaTrash`    | Tab **Gambar** dan **File**: media soft-deleted, restore, hapus permanen, bulk restore/force-delete; item yang masih dipakai diblokir dengan daftar referensi                                                                                                                                                                             |
| `Settings`      | 3 tab (Umum, Profil & About, Komentar); tab Umum berisi teks situs, sosial, dan logo terang/gelap via MediaPicker; tab Profil & About berisi avatar via MediaPicker dan field profil/About yang ditumpuk satu kolom; penghitung karakter; indikator perubahan belum disimpan + peringatan `beforeunload` (tanpa tombol pratinjau)         |
| `Login`         | Email + password, widget Cloudflare Turnstile, lupa/reset password, branding dari pengaturan situs                                                                                                                                                                                                                                        |
| `ResetPassword` | Form password baru + konfirmasi; membaca `token` dan `email` dari query string lalu memanggil `POST /api/auth/reset-password`                                                                                                                                                                                                             |

### 17.6 `PostForm.jsx` (halaman terbesar, ±650 baris)

Bagian penting:

- **Validasi**: `react-hook-form` + `zod`.
    - `title` wajib, max 255.
    - `status`: `draft`/`published`/`scheduled`.
    - `excerpt` max 500, `meta_title` max 255, `meta_description` max 500.
- **Slug**: auto dari judul selama belum diedit manual (`slugAuto`) lewat `slugify`.
    - Jika slug diubah pada artikel yang sudah `published`/`scheduled`, muncul peringatan bahwa URL lama bisa mati.
- **Konten**: `<textarea>` HTML mentah dengan snippet `/p`, `/div`, `/h2`, `/h3`, `/h4`, `/ul`, `/ol`, `/br`, `/hr`, `/pre`, `/code`.
    - Tidak ada mode visual. Untuk melihat hasil render, buka `/admin/posts/{id}/view` setelah artikel disimpan.
- **Media**:
    - Cover dan OG image bisa diunggah langsung (folder `cover`) atau dipilih dari `MediaPicker` (khusus gambar, memaksa `type=image`).
    - Gambar di dalam konten ditulis manual sebagai HTML (`<img src="...">`); URL bisa disalin dari halaman Media.
    - `resolveMediaUrl()`/`toStoredPath()` menormalkan URL `/storage/...` menjadi path relatif saat disimpan.
- **Tag**:
    - Pilih tag yang ada atau buat tag baru lewat input chip.
    - Dikirim sebagai array (ID atau nama) ke API.
- **Metadata**:
    - Kategori, excerpt, meta title/description, OG image.
    - `published_at` untuk penjadwalan.
- **Navigasi dan guard**:
    - Tombol simpan dan kembali (pratinjau tersedia dari halaman daftar artikel lewat tautan **Lihat**).
    - `hasUnsavedChanges = dirty || isDirty`; `beforeunload` aktif selama ada perubahan.
    - Setelah create, user diarahkan ke halaman edit artikel baru; setelah update, state dirty direset.

---

## 18. SEO

### 18.1 Meta dan structured data

- `layouts/public.blade.php`: title, description, canonical, OG type/title/description/url/image, `og:site_name`. Judul dan deskripsi default berasal dari `$site` (`site_title`, `default_meta_description`).
- Halaman About menambahkan JSON-LD `Person` (nama, role, bio, avatar, dan link sosial dari settings).
- Halaman detail menambahkan:
    - `hreflang="id"` dan `hreflang="x-default"` yang menunjuk URL artikel.
    - JSON-LD (`application/ld+json`) untuk artikel.
- Halaman arsip dan About tidak merender `hreflang` alternatif.

### 18.2 Sitemap

`GET /sitemap.xml` menghasilkan URL root:

- `/`
- `/blog`
- `/about`
- Semua artikel published
- Semua kategori (`/category/{slug}`)
- Semua tag (`/tag/{slug}`)

`lastmod` artikel memakai `updated_at`; halaman lain memakai waktu generate.

### 18.3 Robots

Route `GET /robots.txt` menghasilkan:

```
User-agent: *
Disallow: /api/
Disallow: /admin

Sitemap: {APP_URL}/sitemap.xml
```

Catatan: file statis `public/robots.txt` sudah dihapus agar permintaan `/robots.txt` selalu ditangani route `SeoController@robots`. Jika file statis ditambahkan lagi, isinya harus memuat URL sitemap absolut karena file statis akan mengalahkan route pada server Apache.

### 18.4 RSS

`GET /feed.xml` → 20 artikel published terbaru, dirender dari `seo/feed.blade.php` dengan content-type `application/rss+xml`. Judul channel memakai `site_title`; deskripsi hardcoded.

### 18.5 Preview dan noindex

- Shell admin: `noindex, nofollow`.
- Halaman 404: `noindex, nofollow`.

---

## 19. Console Command dan Scheduler

### 19.1 `posts:publish-scheduled`

```bash
php artisan posts:publish-scheduled
```

- Mengubah semua post `status = scheduled` dengan `published_at <= now()` menjadi `published`.
- Mengembalikan jumlah post yang dipublikasikan.

Dijadwalkan di `routes/console.php`:

```php
Schedule::command('posts:publish-scheduled')->everyMinute();
```

Agar berjalan otomatis, scheduler Laravel harus aktif:

```bash
php artisan schedule:work      # lokal
# atau cron: * * * * * php /path/artisan schedule:run
```

### 19.2 `media:backfill`

```bash
php artisan media:backfill {--force} {--prune-missing}
```

- Menormalkan path media lama.
- Mendaftarkan file cover yang belum tercatat.
- `--prune-missing` membersihkan referensi file yang hilang.

### 19.3 `media:thumbnails`

```bash
php artisan media:thumbnails {--force}
```

- Membuat thumbnail 480 px untuk baris `media` (termasuk yang di trash) yang belum punya thumbnail atau file thumbnail-nya hilang.
- `--force`: regenerasi walau thumbnail sudah ada.
- File asli yang hilang atau gagal didecode dilewati/dilaporkan; exit code `FAILURE` bila ada kegagalan.

### 19.4 Command bawaan lain

- `php artisan inspire` (contoh bawaan Laravel).
- `php artisan dev` — dipanggil oleh `composer run dev` untuk menjalankan server, queue, log, dan Vite secara bersamaan.

---

## 20. Seeder dan Factory

### 20.1 Seeder

| Seeder           | Isi                                                                                                                   |
| ---------------- | --------------------------------------------------------------------------------------------------------------------- |
| `DatabaseSeeder` | Membuat admin `admin@godang.com` (`is_admin = true`, password default factory `password`), lalu memanggil seeder lain |
| `CategorySeeder` | 6 kategori: Laravel, PHP, JavaScript, DevOps, Database, Linux                                                         |
| `TagSeeder`      | 15 tag (troubleshooting, eloquent, mysql, react, deployment, dll.)                                                    |
| `PostSeeder`     | 1 artikel unggulan + 8 artikel contoh berbahasa Indonesia (total 7 published, 2 draft)                                |
| `CommentSeeder`  | 2–4 komentar root per post published (6 post pertama), sebagian dibalas admin, plus 1 komentar pending per post       |
| `SettingSeeder`  | Semua key dari `Setting::DEFAULTS` (23 key: situs/logo, sosial, komentar, profil/About)                               |
| `MediaSeeder`    | Kosong                                                                                                                |

### 20.2 Factory

| Factory                | Catatan                                                             |
| ---------------------- | ------------------------------------------------------------------- |
| `UserFactory`          | Password default `password`                                         |
| `PostFactory`          | Mendukung state status dan `indonesian()`                           |
| `CategoryFactory`      | Nama teks biasa dari kata acak                                      |
| `TagFactory`           | Nama teks biasa                                                     |
| `CommentFactory`       | State `pending`, `authorReply`, dll.                                |
| `MediaFactory`         | Data media gambar dummy; state `file()` untuk file non-gambar (PDF) |
| `MediaCategoryFactory` | Kategori media untuk filter perpustakaan                            |

Contoh membuat data demo:

```bash
php artisan migrate:fresh --seed
```

---

## 21. Testing

Struktur:

```
tests/
├── TestCase.php
├── Feature/   (21 file)
└── Unit/ExampleTest.php
```

Status saat dokumen ini diperbarui: **214 test, 796 assertion, semuanya lulus**.

Sejak commit `43a9cc0`, folder `tests/` **dilacak git** (tidak lagi masuk `.gitignore`). Ini penting agar test ikut ter-checkout di CI/deploy dan bisa dijalankan ulang di mesin lain.

### 21.1 Test fitur

| File                         | Fokus                                                                                                                                        |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `AdminAuthorizationTest`     | Akses admin vs non-admin                                                                                                                     |
| `AdminCategoryTest`          | CRUD kategori, nama, slug, deskripsi                                                                                                         |
| `AdminPostManagementTest`    | CRUD, slug, status, reading time, tag baru/reuse, soft delete, restore, force delete, bulk delete                                            |
| `AdminCommentManagementTest` | List, edit, hapus, balas, bulk                                                                                                               |
| `AdminCommentModerationTest` | Approve, spam, moderasi                                                                                                                      |
| `AdminDashboardTest`         | Statistik dashboard dan nama author pada komentar terbaru                                                                                    |
| `AdminMediaTest`             | List/filter media per `type`, kategori media, upload gambar & file non-gambar, allowlist/MIME, trash/restore/force-delete, blokir 409, usage |
| `AdminSettingsTest`          | Default, whitelist, validasi per key, logo, dan key About                                                                                    |
| `AdminSpaRoutesTest`         | Shell SPA dan redirect guest                                                                                                                 |
| `AuthLoginTest`              | Login, Turnstile (wajib/valid/invalid/dilewati), rate limit, logout, me, forgot/reset password                                               |
| `CommentNotificationTest`    | Email notifikasi komentar dan balasan; kegagalan email tidak menggagalkan request                                                            |
| `MediaUploadTest`            | Validasi, konversi WebP, thumbnail, metadata                                                                                                 |
| `PostCoverImageTest`         | URL cover/OG dan normalisasi path                                                                                                            |
| `PublicCommentTest`          | Honeypot, link limit, threading, reparent balasan, rate limit, moderasi                                                                      |
| `PublicPagesTest`            | Landing, about, category, tag                                                                                                                |
| `PublicPostsTest`            | Daftar/detail artikel, filter, dan pencarian judul/excerpt/isi                                                                               |
| `PublicSiteContentTest`      | Konten About dari settings, teks hero/blog hardcoded, avatar, sosial kosong, escaping, fallback default, feed                                |
| `PublishScheduledPostsTest`  | Command publish terjadwal                                                                                                                    |
| `SeoRoutesTest`              | Sitemap, robots, feed 20 item, canonical, redirect legacy, 404 noindex                                                                       |
| `StorageFileTest`            | Fallback `/storage/{path}` untuk `avatar` dan `files`, path traversal                                                                        |
| `ExampleTest`                | Sanity check, redirect legacy `/id`/`/en`, dan 404 untuk prefix tak dikenal                                                                  |

### 21.2 Menjalankan test

```bash
php artisan test --compact
php artisan test --compact tests/Feature/PublicCommentTest.php
php artisan test --compact --filter=testName
vendor/bin/phpunit
```

---

## 22. Workflow End-to-End

### 22.1 Menulis dan menerbitkan artikel

```
/admin/posts/create
  -> isi judul (slug otomatis), kategori, tag, excerpt
  -> tulis konten HTML mentah di textarea (snippet /h2, /p, /pre, dst.)
  -> upload cover / OG (opsional) atau pilih dari Media
  -> pilih status:
       draft      -> simpan tanpa tampil publik
       published  -> published_at = sekarang
       scheduled  -> isi published_at di masa depan
  -> POST /api/admin/posts
  -> scheduler tiap menit mengubah scheduled -> published saat waktunya
```

### 22.2 Menyisipkan gambar ke dalam artikel

```
Media -> unggah / pilih gambar -> salin URL
  -> tempel <img src="{url}" alt="..."> di textarea konten
  -> saat artikel disimpan, URL /storage/... pada cover/OG dinormalkan menjadi path relatif
  -> Media menampilkan usage_count/usage_labels dan menolak hapus permanen selama masih dipakai
```

File non-gambar tidak disisipkan ke konten: buka tab **File**, unggah, lalu salin URL-nya. Tidak ada halaman attachment publik; URL langsung disajikan dengan `Content-Disposition: inline`.

### 22.3 Komentar masuk

```
Pembaca kirim form
  -> POST /api/posts/{id}/comments
  -> honeypot + validasi + limit link
  -> comment_moderation? pending : approved
  -> email ke admin (queued)
  -> admin buka /admin/comments
  -> approve / spam / balas / hapus
  -> komentar approved tampil di halaman artikel
```

### 22.4 Menghapus dan memulihkan artikel

```
/admin/posts -> hapus -> soft delete (masuk /admin/posts/trash)
  -> restore -> POST /api/admin/posts/{id}/restore
  -> hapus permanen -> DELETE /api/admin/posts/{id}?force=1
```

### 22.5 Mengubah konten situs, logo, dan avatar

```
/admin/settings
  -> pilih tab: Umum / Profil & About / Komentar
  -> tab Umum: judul, deskripsi, tagline, meta default, sosial,
     logo terang/gelap + alt (Upload ke folder avatar/uploads atau Pilih dari Media, atau Hapus)
  -> tab Profil & About: nama, role, bio, topik, kontak, avatar + alt
  -> isi teks (bio: paragraf dipisah baris kosong; topik: satu per baris)
  -> Simpan -> PUT /api/admin/settings
  -> validasi whitelist + URL/boolean/panjang
  -> request publik/shell admin berikutnya: ShareSiteSettings membagikan $site terbaru
  -> cek hasil dengan membuka halaman publik terkait (mis. /, /about, /blog)
```

---

## 23. Setup dan Perintah Harian

### 23.1 Instalasi awal

```bash
composer install
copy .env.example .env          # Windows
php artisan key:generate

# sesuaikan DB_* di .env (MySQL, database blog)
php artisan migrate --seed
php artisan storage:link

npm install
npm run build
```

Atau memakai script bawaan:

```bash
composer run setup
```

Script ini menjalankan `composer install`, menyalin `.env` bila belum ada, `key:generate`, `migrate --force`, `npm install --ignore-scripts`, dan `npm run build`; seeder **tidak** dijalankan otomatis.

### 23.2 Menjalankan pengembangan

```bash
composer run dev
```

Perintah ini menjalankan `php artisan dev` (server, queue, log, Vite). Alternatif manual:

```bash
php artisan serve
npm run dev
php artisan queue:work        # agar notifikasi email benar-benar diproses
php artisan schedule:work     # agar artikel terjadwal otomatis terbit
```

### 23.3 Perintah yang sering dipakai

```bash
php artisan route:list --except-vendor
php artisan migrate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan posts:publish-scheduled
php artisan media:backfill --force --prune-missing
php artisan media:thumbnails
php artisan test --compact
vendor/bin/pint --format agent <file>
npm run build
```

### 23.4 Frontend

- Aset publik dan admin dibangun oleh Vite.
- **`public/build/` dilacak git** karena workflow deploy tidak menjalankan build. Setiap perubahan `resources/js` atau `resources/css` wajib diakhiri dengan:

    ```bash
    npm run build
    git add public/build
    git commit -m "build: perbarui aset frontend"
    ```

- Jangan commit `public/hot` (penanda mode dev) atau `public/storage` (junction).
- Jika di server muncul error **“Unable to locate file in Vite manifest”** atau **“Vite manifest not found at: /htdocs/public/build/manifest.json”**, artinya `public/build` belum ikut ter-upload. Jalankan `npm run build`, commit `public/build`, lalu push.
- Entry Vite: `resources/css/app.css`, `resources/js/app.js`, `resources/js/admin/main.jsx`.

### 23.5 Deploy ke InfinityFree

Workflow aktif: `.github/workflows/infinityfree.yml`.

| Aspek                        | Kondisi saat ini                                                                                                                                                                                                                 |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Trigger                      | Push ke `main` atau `workflow_dispatch`                                                                                                                                                                                          |
| Langkah workflow             | `actions/checkout` → FTP ke `/htdocs/`                                                                                                                                                                                           |
| Build di CI                  | **Tidak ada** (`composer install`, `npm run build`, dan test tidak dijalankan)                                                                                                                                                   |
| `public/build`               | Di-commit ke repo, ikut ter-upload                                                                                                                                                                                               |
| `vendor/`                    | Baris `/vendor` di `.gitignore` dikomentari, jadi folder ini tidak diabaikan dan **dapat ikut ter-commit**; saat ini belum di-track, sehingga `git add .` akan memasukkannya. Workflow FTP tidak menjalankan `composer install`. |
| `.env` server                | Tidak tertimpa (tidak ada di repo); pakai `QUEUE_CONNECTION=sync` untuk InfinityFree                                                                                                                                             |
| Media (`storage/app/public`) | Tidak di-commit; file di server dipertahankan, termasuk `files/` untuk non-gambar                                                                                                                                                |
| Migrasi                      | Dijalankan manual; workflow tidak menjalankannya                                                                                                                                                                                 |
| `exclude`                    | `**/.git*`, `**/.git*/**`, `**/node_modules/**`, `STRUCTURE.md` — dokumen internal tidak di-upload dan tidak dihapus dari server                                                                                                 |

Karena itu:

1. Setelah mengubah frontend, **selalu build + commit `public/build`**.
2. Setelah mengubah dependency PHP, commit `vendor/` (tidak di-ignore) atau upload ulang folder itu ke server (atau jalankan `composer install` di server bila tersedia).
3. File root `.htaccess` mengarahkan semua request ke `public/`, jadi document root tetap `/htdocs/`.
4. Setelah deploy, verifikasi `/htdocs/public/build/manifest.json` ada dan halaman utama terbuka tanpa error manifest.

---

## 24. Konvensi dan Catatan Penting

### 24.1 Konvensi kode

- PHP memakai constructor property promotion, type hint, dan return type eksplisit.
- Model memakai atribut PHP (`#[Fillable]`, `#[Hidden]`, `#[Scope]`) khas Laravel 13.
- Scope ditulis sebagai method `protected` dengan atribut `#[Scope]`; dipanggil sebagai `Post::published()`.
- Validasi di controller; tidak ada Form Request.
- Response API memakai model/paginator langsung, tanpa API Resource.
- Nama route API memakai prefix `api.` dan `api.admin.`.
- Komentar kode dan PHPDoc ditulis dalam bahasa Inggris; UI admin sebagian besar berbahasa Indonesia.

### 24.2 Hal yang mudah menjebak

1. **`FILESYSTEM_DISK=local` tidak berlaku untuk media.** Kode selalu memakai `Storage::disk('public')`. Jangan mengubah asumsi ini tanpa menyesuaikan controller dan model.
2. **`public/storage` adalah junction.** Di Windows, junction bisa rusak setelah clone ulang; jalankan `php artisan storage:link`.
3. **Aset Vite harus di-build dan di-commit.** Mengedit JSX/CSS tidak langsung terlihat di browser tanpa `npm run build` (atau `npm run dev`). Untuk deploy FTP, `public/build` juga harus di-commit.
4. **Slug unik global, termasuk data terhapus.** Menghapus artikel tidak membebaskan slug-nya. Gunakan slug berbeda atau hapus permanen.
5. **Konten artikel adalah HTML tepercaya.** `{!! $post->content !!}` tidak disanitasi. Hanya admin yang boleh menulis konten.
6. **Notifikasi memakai queue.** Tanpa worker, email tetap di tabel `jobs`. Dengan `MAIL_MAILER=log`, email masuk ke log.
7. **Settings adalah sumber konten publik.** Judul situs, meta, About, avatar, dan link sosial dibaca dari `$site` (middleware `ShareSiteSettings`); teks hero beranda dan header blog di-hardcode di view. Key yang dikosongkan akan **menimpa** default menjadi kosong; acuannya adalah `Setting::DEFAULTS`.
8. **Database SQLite tidak dipakai.** Tidak ada `database/database.sqlite`; koneksi aktif MySQL dan file `*.sqlite*` diabaikan oleh `database/.gitignore`.
9. **Proyek ini adalah repo Git.** `pint --dirty` tersedia; tetap jalankan `vendor/bin/pint --dirty --format agent` sebelum commit.
10. **Fallback storage membatasi path.** Hanya `uploads/`, `cover/`, `avatar/`, dan `files/` dengan satu level nama file; path lain akan 404 meskipun file ada.
11. **Views dihitung per render**, bukan per pengunjung unik, sehingga bisa meningkat saat refresh. Angkanya tetap disimpan tetapi tidak ditampilkan di halaman artikel publik.
12. **Komentar dibatasi dua tingkat.** Balasan atas balasan otomatis dipindahkan ke bawah komentar root.
13. **`$site` tersedia di halaman publik dan shell admin.** `ShareSiteSettings` dipasang pada halaman publik, `/feed.xml`, `/admin/login`, dan `/admin/{path?}`. View di luar cakupan itu (mis. error 404) harus memakai fallback `config('app.name')`.
14. **Media bersifat trash-first.** `DELETE /media/{media}` hanya mengisi `deleted_at`; file baru hilang saat force-delete. Force-delete ditolak (`409`) selama media masih direferensikan artikel/avatar/logo — lepaskan referensinya dulu.
15. **Thumbnail dipakai untuk grid admin.** Upload baru selalu menghasilkan `-thumb.webp`; data lama perlu `php artisan media:thumbnails`. `thumb_url` fallback ke file asli bila thumbnail tidak ada.
16. **Deploy memakai FTP, bukan build di CI.** Workflow `.github/workflows/infinityfree.yml` hanya checkout + FTP ke `/htdocs/`. Tidak ada `composer install`, `npm run build`, atau test. Lihat [23.5](#235-deploy-ke-infinityfree) dan [26](#26-panduan-perubahan-change-checklist).
17. **Nama komentar author bukan dari kolom `name` mentah.** Accessor `Comment::name` mengembalikan `Setting::authorName()` saat `is_author = true`. Jangan menambahkan `select('name')` yang memotong `is_author` pada query komentar (mis. `recent_comments` di dashboard) karena nama akan kembali menjadi nilai kolom lama seperti "Admin".

### 24.3 Keamanan produksi yang perlu diperhatikan

- Set `APP_DEBUG=false` dan `APP_ENV=production`.
- Gunakan HTTPS agar cookie sesi `Secure`.
- Batasi `SANCTUM_STATEFUL_DOMAINS` ke domain produksi.
- Jalankan queue worker dan scheduler sebagai service; di shared hosting tanpa worker, set `QUEUE_CONNECTION=sync`.
- Sesuaikan `upload_max_filesize`/`post_max_size` PHP minimal di atas batas file 10 MB jika ingin upload non-gambar berjalan di server.
- Pastikan `storage/` dan `public/build` memiliki izin yang benar.
- Pertimbangkan sanitasi HTML editor jika nantinya ada peran non-admin.

---

## 25. Jalur Belajar yang Disarankan

Urutan membaca kode dari yang paling mudah ke paling kompleks:

1. **Routing** — `routes/web.php`, lalu `routes/api.php`. Pahami dulu URL apa yang memanggil controller mana.
2. **Situs publik** — `PublicController` → `resources/views/public/*` → `layouts/public.blade.php` → partial. Fokus pada bagaimana data artikel diambil dan dirender.
3. **Model `Post`** — scope `published`, accessor cover, hook slug/reading time, dan soft delete. Ini inti domain aplikasi.
4. **Database** — baca migrasi berurutan untuk memahami evolusi skema (terutama `posts`, `comments`, `media`).
5. **API publik** — `Api\PostController`, `Api\CommentController`. Perhatikan validasi, throttle, honeypot, dan threading komentar.
6. **Autentikasi** — `bootstrap/app.php`, `AuthController`, `EnsureUserIsAdmin`, lalu `resources/js/admin/stores/auth.js` dan `api.js`.
7. **Admin API** — `Api\Admin\PostController` (CRUD + bulk), lalu controller kategori/tag/komentar/settings.
8. **Media** — `MediaController` (upload + thumbnail, filter/usage, trash/restore/force-delete), `MediaCategoryController`, model `Media` (`diskPath`, `thumb_url`), `StorageFileController`, command `BackfillMediaPaths`/`GenerateMediaThumbnails`, lalu halaman `Media.jsx`/`MediaTrash.jsx`.
9. **Admin React** — mulai dari `main.jsx` → `App.jsx` → `Shell.jsx` → satu halaman sederhana (`Categories.jsx`) → `media.js` + `MediaPicker.jsx` → `Settings.jsx` (konten situs) → `PostForm.jsx` sebagai studi kasus terbesar.
10. **SEO dan operasional** — `SeoController`, `routes/console.php`, `PublishScheduledPosts`, seeder, dan test.
11. **Test** — baca `tests/Feature` sebagai dokumentasi perilaku yang diharapkan; jalankan test setiap kali mengubah kode.

Pertanyaan pemandu saat membaca kode:

- Data apa yang dibutuhkan halaman ini, dan dari mana asalnya?
- Siapa yang boleh mengakses route ini? Di lapisan mana pengecekannya?
- Apa yang terjadi pada data lama saat skema berubah (mis. path media)?
- Apakah perubahan ini memengaruhi soft delete, media, atau cache/build frontend?
- Test mana yang akan gagal jika perilaku ini diubah?

---

## 26. Panduan Perubahan (Change Checklist)

Bagian ini adalah daftar kerja singkat setiap kali mengubah proyek, termasuk hal yang wajib di-commit agar deploy FTP tidak rusak.

### 26.1 Alur wajib sebelum commit

```bash
# 1. Format PHP (hanya file yang berubah)
vendor/bin/pint --dirty --format agent

# 2. Test (wajib lulus)
php artisan test --compact

# 3. Build frontend (WAJIB jika ada perubahan JS/CSS)
npm run build

# 4. Periksa perubahan
git status
git diff

# 5. Commit + push
git add .
git commit -m "pesan perubahan"
git push origin main
```

### 26.2 Matriks perubahan

| Jenis perubahan                                     | Perintah wajib                                                          | File yang harus ikut commit                                                          | Catatan deploy                                   |
| --------------------------------------------------- | ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------ |
| PHP (controller, model, middleware, route, command) | `vendor/bin/pint --dirty --format agent` + `php artisan test --compact` | File PHP terkait + test                                                              | Langsung ter-upload via FTP                      |
| Migrasi database                                    | `php artisan migrate` + test                                            | `database/migrations/*.php`                                                          | Jalankan migrasi **manual** di server            |
| Seeder / factory                                    | `php artisan migrate:fresh --seed` (lokal) + test                       | `database/seeders`, `database/factories`                                             | Tidak otomatis dijalankan                        |
| Frontend JS/CSS                                     | `npm run build` + test                                                  | `resources/js`, `resources/css`, **`public/build/`**                                 | Tanpa `public/build`, muncul error Vite manifest |
| Blade saja (tanpa aset baru)                        | `php artisan test --compact`                                            | File Blade                                                                           | Tidak perlu build                                |
| Dependency PHP                                      | `composer update` + `composer audit` + test                             | `composer.json`, `composer.lock`, **`vendor/`** (tidak di-ignore)                    | Commit `vendor/` atau upload ulang ke server     |
| Dependency JS                                       | `npm install` + `npm audit` + `npm run build`                           | `package.json`, `package-lock.json`, **`public/build/`**                             | Sama seperti perubahan frontend                  |
| Key settings baru                                   | test                                                                    | `Setting.php`, `SettingController.php`, `ShareSiteSettings.php`, `SettingSeeder.php` | Tidak perlu migrasi                              |
| Route / API baru                                    | test + update dokumen ini                                               | `routes/*.php`, controller, test                                                     | Cek `php artisan route:list`                     |
| Test baru                                           | `php artisan test --compact`                                            | `tests/**`                                                                           | Folder `tests` sudah dilacak git                 |

### 26.3 Perubahan frontend (paling sering)

```bash
# Setelah mengedit resources/js atau resources/css
npm run build
git add public/build
git add resources
git commit -m "feat: ..."
git push origin main
```

Yang **tidak boleh** di-commit:

- `public/hot` (penanda Vite dev server)
- `public/storage` (junction/symlink)
- `node_modules/`
- `storage/app/public/uploads|cover|avatar|files` (media pengguna)

Verifikasi hasil build:

```bash
# manifest harus ada dan valid
Test-Path public/build/manifest.json
Get-Content public/build/manifest.json | ConvertFrom-Json
```

### 26.4 Perubahan backend

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

Jika mengubah route atau controller, jalankan juga:

```bash
php artisan route:list --except-vendor
```

Jika mengubah perilaku yang sudah dites, perbarui test terkait. Jangan hanya mengubah kode agar test lama lulus.

### 26.5 Menambah migrasi

```bash
php artisan make:migration nama_perubahan --table=nama_tabel
# edit file migrasi
php artisan migrate
php artisan test --compact
git add database/migrations
```

Pastikan file migrasi benar-benar masuk git. Folder `database/` sudah dilacak; `database/*.sqlite` tetap diabaikan oleh `database/.gitignore`.

### 26.6 Mengubah dependency

**PHP**

```bash
composer require vendor/package
composer audit
php artisan test --compact
git add composer.json composer.lock
```

Setelah deploy, `vendor/` di server harus diperbarui. Karena `/vendor` tidak lagi di-ignore, commit folder tersebut atau upload ulang secara manual; workflow FTP tidak menjalankan `composer install`.

**JavaScript**

```bash
npm install package@versi
npm audit
npm run build
git add package.json package-lock.json public/build
```

### 26.7 Menambah key settings

Tidak perlu migrasi. Tambahkan key ke:

1. `app/Models/Setting.php` → `DEFAULTS`
2. `app/Http/Controllers/Api/Admin/SettingController.php` → whitelist/validasi
3. `app/Http/Middleware/ShareSiteSettings.php` bila key perlu ikut dibagikan ke `$site`
4. `database/seeders/SettingSeeder.php` (otomatis dari `DEFAULTS`)
5. Test di `tests/Feature/AdminSettingsTest.php`

### 26.8 Checklist deploy

- [ ] `vendor/bin/pint --dirty --format agent` bersih
- [ ] `php artisan test --compact` lulus
- [ ] `npm run build` dijalankan (jika frontend berubah)
- [ ] `public/build/` ikut di-commit
- [ ] `vendor/` ikut ter-commit/ter-upload bila dependency PHP berubah
- [ ] `git push origin main`
- [ ] Workflow `.github/workflows/infinityfree.yml` sukses
- [ ] `/htdocs/public/build/manifest.json` ada di server
- [ ] Halaman utama, `/admin/login`, dan satu halaman artikel terbuka normal
- [ ] Migrasi baru dijalankan manual di server (termasuk kolom `media.type`/`media.original_name`)
- [ ] Folder `storage/app/public/files` ada dan writable bila memakai upload non-gambar
- [ ] `QUEUE_CONNECTION=sync` pada `.env` server bila tidak ada queue worker
- [ ] Jika tampilan masih lama, hapus cache view/config di server (`storage/framework/views/*`, `bootstrap/cache/*.php`) melalui FTP

### 26.9 Rollback cepat

```bash
git revert <commit>
git push origin main
```

Jika rollback menyentuh frontend, jalankan `npm run build` dan commit ulang `public/build` agar aset di server kembali sesuai kode.

---

_Dokumen ini dibuat berdasarkan kondisi kode saat ini. Terakhir diperbarui: 13 November 2026. Jika struktur berubah, perbarui bagian yang relevan agar tetap menjadi peta yang akurat._
