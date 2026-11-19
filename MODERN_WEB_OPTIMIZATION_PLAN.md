# Rencana Optimalisasi Modern Web — Godang

Tanggal: 2026-11-19
Ruang lingkup: `resources/` (Blade, React/JSX, CSS) — frontend publik dan admin.
Alat: `modern-web-guidance` (`legacy-scan` + `search`/`retrieve`), skill version `2026_11_10-47b4c80e`.

## 1. Ringkasan Eksekutif

| Kategori | Jumlah | Prioritas | Status verifikasi |
| --- | ---: | --- | --- |
| Scrollbar vendor pseudo-element | 3 | P1 | Lolos `keepWhen` |
| Dark mode toggle tidak sinkron dengan `color-scheme` | 6 | P0 | Lolos `keepWhen` |
| Enter-to-submit tidak IME-safe | 2 | P0 | Lolos `keepWhen` |
| Overlay modal kustom (native `<dialog>`) | 4 komponen | P1 | Peluang tambahan |
| Tabel admin responsif | 5 halaman | P2 | Peluang tambahan |
| Prioritas gambar LCP | 3 lokasi | P0 | Peluang tambahan |
| Toast accessibility | 1 komponen | P2 | Peluang tambahan |
| Validasi form & ARIA | 3+ form | P2 | Peluang tambahan |

`legacy-scan` memeriksa 38 panduan / 52 aturan deterministik (~20% dari seluruh katalog). Karena cakupannya terbatas, audit dilengkapi dengan pencarian topik lain yang memang dipakai proyek ini. Semua 11 temuan deterministik diverifikasi manual terhadap `keepWhen`/`dropWhen`; **tidak ada yang dibatalkan** sebagai false positive.

---

## 2. Temuan Terverifikasi (dari `legacy-scan`)

### 2.1 P0 — Enter-to-submit belum IME-safe (`ime-safe-enter-submit`)

| File | Baris | Kode |
| --- | ---: | --- |
| `resources/js/admin/pages/PostForm.jsx` | 553 | `if (e.key === 'Enter') { ... addTagChip(tagInput); }` |
| `resources/views/public/blog/index.blade.php` | 77 | `if (e.key === 'Enter') { ... form.submit(); }` |

**Mengapa lolos verifikasi:** keduanya adalah listener `keydown` pada `<input>` teks, memakai `e.key === 'Enter'`, dan tidak ada pemeriksaan `isComposing` / `keyCode === 229` di seluruh `resources/`. Bukan kasus `keyup`/`keypress`, bukan modifier-only, dan bukan submit native.

**Rencana:**
1. Tambahkan guard setelah `preventDefault()`: `if (e.isComposing || e.keyCode === 229) return;` (pada React gunakan `e.nativeEvent.isComposing`, karena synthetic event tidak selalu meneruskan properti ini).
2. Untuk pencarian blog, **utamakan menghapus handler kustom** dan biarkan submit implisit/form native menangani Enter — browser sudah menekan submit saat IME mengonsumsi Enter. Pastikan tombol `type="submit"` tetap ada.
3. Untuk input tag, tetap pertahankan handler karena ada logika `Tab`/`Backspace`, tetapi tambahkan guard IME.
4. Sertakan `<label>` yang terasosiasi (`id`/`htmlFor`) untuk kedua input.

**Acceptance:** uji dengan IME Jepang/Cina/Korea — menekan Enter untuk konfirmasi kandidat karakter tidak boleh menambah tag / mengirim form.

---

### 2.2 P0 — Toggle tema tidak sinkron dengan `color-scheme` (`dark-mode-toggle`, `dark-mode`)

| File | Baris | Pola |
| --- | ---: | --- |
| `resources/js/admin/components/Shell.jsx` | 42 | `classList.toggle('dark')` + `localStorage('theme')` |
| `resources/js/admin/pages/Login.jsx` | 66 | `classList.toggle('dark')` + `localStorage('theme')` |
| `resources/views/admin.blade.php` | 39 | inline FOUC script, default dark |
| `resources/views/errors/404.blade.php` | 17 | inline FOUC script, default dark |
| `resources/views/layouts/public.blade.php` | 59 | inline FOUC script, default dark |
| `resources/views/partials/navbar.blade.php` | 111 | `toggleTheme()` + `localStorage('theme')` |

**Mengapa lolos verifikasi:** tidak ada satu pun `<meta name="color-scheme">`, properti CSS `color-scheme`, atau `light-dark()` di `resources/` (grep konfirmasi). Tema saat ini "dark unless saved light" — **disepakati dipertahankan sebagai default brand** (lihat Bagian 6). `@custom-variant dark (&:where(.dark, .dark *))` di `app.css:6` mengikat seluruh styling ke class JS.

**Rencana:**
1. **Model 3 state dengan default brand**: tanpa nilai tersimpan → **gelap** (bukan preferensi OS); nilai tersimpan `light` atau `dark` → pin sesuai pilihan. Saat pengguna memilih "kembali ke default", **hapus** entri `localStorage` (bukan menyimpan `dark`) agar status "belum memilih" tetap terbedakan.
2. Tambahkan `<meta name="color-scheme" content="light dark">` di `<head>` ke-3 layout di atas, diikuti inline script FOUC (bukan `type=module`, bukan `defer`) yang menulis `localStorage.getItem('theme') ?? 'dark'` ke `meta.content`. Deklarasi CSS dasar tetap `color-scheme: light dark`; pemetaan `:has(...)` yang membuatnya menjadi `dark` saat tidak ada pilihan tersimpan.
3. Petakan balik meta ke CSS di `app.css`:
   ```css
   :root {
       color-scheme: light dark;
       &:has(> head > meta[name="color-scheme"][content="dark"]) { color-scheme: dark; }
       &:has(> head > meta[name="color-scheme"][content="light"]) { color-scheme: light; }
   }
   ```
   Default harus tetap `light dark`; jangan hardcode `light`/`dark`.
4. Sentralisasi logika toggle (mis. `resources/js/theme.js`) yang dipakai `Shell.jsx`, `Login.jsx`, dan `navbar.blade.php`. Sinkronkan juga event `storage` antar-tab. Jangan memakai `matchMedia().addEventListener('change')` untuk menghapus nilai tersimpan.
5. Konversi token warna ke `light-dark()`. Fallback untuk browser tanpa `light-dark()` harus mengikuti hasil pemetaan meta (selector `:root:has(> head > meta[name="color-scheme"][content="dark"])` / `[content="light"]`), **bukan hanya** `@media (prefers-color-scheme: dark)` — jika tidak, pilihan `light` tidak akan berlaku di browser tersebut. Class `.dark` boleh dipertahankan sementara untuk kompatibilitas `@custom-variant`, tetapi bukan satu-satunya sumber kebenaran (branching non-warna seperti logo navbar juga memakai selector `:root:has(... meta ...)`).
6. Sinkronkan `<meta name="theme-color">` (`public.blade.php:50`) dengan skema aktif agar chrome browser tidak kontras.
7. **Keputusan terkunci:** default tetap gelap. Sesuai kebijakan produk, status "tanpa pilihan tersimpan" dipetakan ke `dark`, bukan ke `prefers-color-scheme`. Ini menyimpang dari rekomendasi panduan untuk mengikuti OS, jadi dicatat sebagai pengecualian yang disengaja (Bagian 6).

**Acceptance:** tidak ada FOUC saat reload dan halaman pertama tetap gelap; form control/scrollbar native mengikuti tema; toggle dapat kembali ke default gelap dengan menghapus preferensi; pergantian tema di tab lain tersinkron.

---

### 2.3 P1 — Scrollbar memakai `::-webkit-scrollbar` (`customize-scrollbar-color-and-thickness`)

| File | Baris | Kode |
| --- | ---: | --- |
| `resources/css/app.css` | 410 | `::-webkit-scrollbar { width: 6px; height: 6px; }` |
| `resources/css/app.css` | 419 | `::-webkit-scrollbar-thumb { background: var(--color-border); ... }` |
| `resources/css/app.css` | 424 | `::-webkit-scrollbar-thumb:hover { background: var(--color-text-muted); }` |

**Mengapa lolos verifikasi:** tidak ada `scrollbar-width`/`scrollbar-color` di stylesheet (grep konfirmasi), bukan reset stylesheet, bukan `@supports not (...)` fallback, dan bukan widget scrollbar sintetis. Ini adalah kustomisasi warna + ketebalan murni.

**Rencana:**
1. Tambahkan variabel `--scrollbar-thumb` / `--scrollbar-track` dan terapkan `scrollbar-color` + `scrollbar-width: thin` pada `:root` (atau kontainer scroll yang relevan).
2. Pindahkan selector `::-webkit-scrollbar*` ke dalam `@supports not (scrollbar-color: auto)`, dengan `scrollbar-width: auto` di dalam blok tersebut agar pseudo-element tidak dimatikan di browser yang mendukung `scrollbar-width` tetapi bukan `scrollbar-color`.
3. **Perbaiki kontras:** thumb saat ini `var(--color-border)` (#E5E7EB di light) terhadap track transparan/#FAFAFA hanya ~1,2:1 — gagal WCAG 1.4.11 (min. 3:1). Gunakan `var(--color-text-muted)` (#6B7280 / #9CA3AF) sebagai thumb.
4. Jangan transisikan `scrollbar-color` (bug flicker WebKit).

**Acceptance:** tampilan scrollbar tetap konsisten di Chrome/Firefox/Safari; thumb terlihat jelas di light dan dark; tidak ada efek ganda di browser yang mendukung kedua API.

---

## 3. Peluang Tambahan (panduan non-deterministik)

### 3.1 P1 — Modal kustom → native `<dialog>` (`dialog`)

Empat overlay dibangun manual: `ConfirmModal.jsx:27` (`role="dialog" aria-modal="true"` + overlay `onClick`), `MediaEditModal.jsx:34`, `MediaPicker.jsx:84`, `MediaCategoryManager.jsx:106`. `Shell.jsx:67` adalah drawer navigasi (kasus terpisah).

**Rencana:**
- Gunakan `<dialog>` + `showModal()` (modal) atau `<button command="show-modal" commandfor="...">` jika ingin deklaratif.
- Hapus `role="dialog"`, `aria-modal`, focus trap manual, dan overlay `<div>`; gunakan `::backdrop` untuk warna gelap. Tambahkan `aria-labelledby` ke judul dan `autofocus` pada tombol **Cancel** untuk konfirmasi destruktif.
- Petakan klik-di-luar ke `closedby="any"`; berikan fallback feature-detect `'closedBy' in HTMLDialogElement.prototype` dengan pengecekan koordinat pointer (`pointerdown` + `pointerup` di luar bounding box) untuk Safari.
- Tambahkan aturan wajib `dialog:modal { overscroll-behavior: contain; }` dan `dialog::backdrop { overflow: hidden; overscroll-behavior: contain; }`. **Jangan** mengunci scroll dengan `overflow: hidden`/`position: fixed` pada `html`/`body`.
- Jangan set `display` pada `dialog` secara unconditional (batasi ke `dialog[open]`). Biarkan browser mengembalikan fokus ke invoker.
- Invoke Commands / Popover: muat polyfill **hanya** jika feature-detect gagal (`'commandForElement' in HTMLButtonElement.prototype`, `Object.hasOwn(HTMLElement.prototype, 'popover')`).

### 3.2 P2 — Tabel admin responsif (`responsive-table`)

Tabel ditemukan di `Categories.jsx:143`, `Posts.jsx:218`, `Tags.jsx:131`, `Trash.jsx:199`, `Users.jsx:175` — semuanya `<table className="w-full text-sm">`.

**Rencana:** wrapper `overflow: auto` + `container-type: inline-size` + `tabindex="0"` + `role="region"` + `aria-labelledby` caption; `position: sticky` pada `thead` dan `tbody th` dengan background solid; layout tumpuk via `@container (width < 600px)` dengan label kolom disuntikkan lewat `::before` dan `aria-label` untuk konten non-teks. Gunakan `@container`, bukan `@media`, agar adaptif terhadap widget sempit.

### 3.3 P0 — Prioritas gambar / LCP (`optimize-image-priority`)

| Lokasi | Kondisi | Tindakan |
| --- | --- | --- |
| `resources/views/public/blog/show.blade.php:136` | Cover artikel 1200×630, kandidat LCP utama | `fetchpriority="high"`, **tanpa** `loading="lazy"` |
| `resources/views/public/blog/index.blade.php` → `partials/post-card.blade.php:26` | Semua kartu memakai `loading="lazy"`, termasuk kartu pertama above-the-fold | Kartu pertama: hapus `lazy`, beri `fetchpriority="high"`; sisanya biarkan `lazy` tanpa `fetchpriority="low"` |
| `resources/views/partials/navbar.blade.php:25-28` | Dua logo dirender, satu disembunyikan via `dark:hidden` / `hidden dark:block` | Logo yang tersembunyi: `fetchpriority="low"` + `loading="lazy"`; logo aktif biarkan prioritas normal |

Batasi `fetchpriority="high"` maksimal 1–2 gambar per halaman. Jangan gunakan `fetchpriority="auto"`, `importance`, atau `loading="auto"`.

### 3.4 P2 — Toast (`persistent-toast-notifications`)

`Toast.jsx` saat ini: satu toast, `role="status"`, auto-dismiss 3,5 detik, tanpa tombol tutup.

**Rencana:** gunakan `popover="manual"` (tanpa light-dismiss), container `role="region" aria-label="Notifications"` yang selalu ada di DOM, dan `ariaNotify()` dengan fallback `<div aria-live="polite">` yang sudah terpasang saat load. Tambahkan tombol Close yang keyboard-focusable, jeda auto-dismiss saat fokus ada di dalam toast, dan jangan tampilkan toast selagi modal dialog terbuka. Transisi top layer memakai `transition-behavior: allow-discrete` sebagai properti terpisah + varian `prefers-reduced-motion`.

### 3.5 P2 — Validasi form & ARIA (`validate-input-after-interaction`, `accessible-error-announcement`)

Error saat ini hanya `role="alert"` pada `Login.jsx:123,200` dan `ResetPassword.jsx:76`; tidak ada `aria-invalid`, `aria-describedby`, atau `:user-invalid`.

**Rencana:** pakai atribut constraint native (`required`, `type="email"`, `pattern`, `autocomplete`) + styling `:user-invalid` (fallback `:is(:user-invalid, .user-invalid-fallback)`). Inisialisasi `aria-invalid="false"`, sinkronkan ke `true` hanya saat `:user-invalid` cocok melalui event `blur`/`change`/`input`/`invalid`. Daftarkan id pesan error di `aria-describedby` **hanya saat error tampil** (referensi permanen membuat pesan tersembunyi tetap dibacakan). Gunakan satu live region `aria-live="polite"` untuk halaman, dan arahkan fokus ke field pertama yang invalid setelah submit gagal.

---

## 4. Urutan Implementasi

| Fase | Item | Alasan |
| --- | --- | --- |
| **P0 — segera** | IME guard (2 file), `fetchpriority` LCP (3 lokasi), fondasi `color-scheme` + meta di 3 layout | Dampak langsung ke pengguna IME, LCP, dan native UI; perubahan kecil |
| **P1** | Sentralisasi `theme.js` + migrasi token `light-dark()`, scrollbar standar + fallback, migrasi 4 modal ke `<dialog>` | Perubahan menyentuh banyak file; perlu regression test visual |
| **P2** | Tabel responsif, toast accessibility, form ARIA | Perlu desain/UX review dan uji screen reader |

Catatan: tidak ada perubahan dependency yang diusulkan. Polyfill (`invokers-polyfill`, `@oddbird/popover-polyfill`) **hanya** jika kebijakan browser support memerlukannya dan di-load secara feature-detected; default mengikuti rekomendasi tiap panduan (Baseline Widely → aman tanpa fallback; Newly/Limited → pakai fallback yang tertera).

## 5. Verifikasi

1. `npm run build` — memastikan Vite/Tailwind tidak error.
2. `vendor/bin/pint --dirty --format agent` + `php artisan test --compact` — regresi Blade/Laravel.
3. Manual: toggling tema (default gelap → light → dark → kembali ke default) + reload tanpa FOUC; IME composition Enter; scrollbar light/dark; buka/tutup modal dengan Esc, klik luar, dan pengembalian fokus; tabel di viewport 375px; toast dengan screen reader.
4. Ulangi `npx modern-web-guidance legacy-scan resources --json` setelah perubahan untuk mengonfirmasi 11 temuan deterministik hilang. Temuan yang sengaja dipertahankan sebagai fallback tidak perlu dihilangkan dengan memaksa.

## 6. Risiko & Keputusan Terbuka

- **Default gelap (keputusan terkunci)**: produk memilih mempertahankan dark by default. Ini pengecualian yang disengaja terhadap rekomendasi `dark-mode-toggle` (ikut OS). Konsekuensinya: status "tanpa preferensi" berarti gelap dan opsi "system" tidak ditampilkan di UI. Deklarasi CSS `:root` tetap `color-scheme: light dark` — warna gelap diterapkan lewat pemetaan meta, bukan dengan mengubah default CSS.
- **Class `.dark`**: `@custom-variant` Tailwind dan banyak utility `dark:` bergantung padanya. Migrasi ke `light-dark()` sebaiknya bertahap agar tidak merusak styling.
- **`closedby` tidak didukung Safari**: light-dismiss akan kehilangan fitur tanpa fallback JS; modal tetap menutup dengan Esc/back gesture.
- **`overscroll-behavior` dan `ariaNotify()`** bukan Baseline Widely → perlakukan sebagai progressive enhancement sesuai panduan.
