# Product Requirements Document (PRD)
## NIRA — Niaga Indonesia Retail Assistant

| | |
|---|---|
| **Dokumen** | Product Requirements Document |
| **Produk** | NIRA POS (Point of Sale) |
| **Versi Dokumen** | 1.0 |
| **Status** | Draft — MVP Planning |
| **Tipe Produk** | SaaS multi-tenant, dijual ke bisnis UMKM |

---

## 1. Latar Belakang & Tujuan Produk

### 1.1 Latar Belakang
NIRA adalah sistem Point of Sale (POS) berbasis web yang dirancang untuk melayani berbagai jenis UMKM (retail, fashion, souvenir, dan pengembangan ke laundry, coffee shop, gym) di wilayah Nusa Dua/Jimbaran, Bali. Sistem dibangun sebagai produk SaaS multi-tenant sehingga satu basis kode dapat dijual dan digunakan oleh banyak bisnis berbeda secara independen.

### 1.2 Tujuan Produk
- Menyediakan sistem kasir digital yang mudah dipakai UMKM tanpa perlu tenaga IT internal.
- Memungkinkan pemilik bisnis mengatur hak akses stafnya sendiri secara fleksibel (dinamis).
- Menyediakan data penjualan & stok yang akurat, real-time, dan mudah dilaporkan.
- Menjadi produk yang bisa diakses dari perangkat apa pun tanpa instalasi (PWA).

### 1.3 Tujuan Bisnis
- Mempercepat proses transaksi di kasir sehingga antrian pelanggan lebih singkat.
- Mengurangi kesalahan pencatatan stok manual.
- Membuka peluang pendapatan berulang (recurring revenue) dari model langganan SaaS.

---

## 2. Target Pengguna

| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| **Owner Bisnis** | Pemilik UMKM (retail/fashion/souvenir), non-teknis | Laporan penjualan, kontrol penuh, atur hak akses staf |
| **Manajer Outlet** | Mengelola operasional 1 cabang | Kelola stok & produk, lihat laporan cabang |
| **Kasir** | Staf lapangan, transaksi harian | Proses transaksi cepat, antarmuka simpel |
| **Super Admin (Platform)** | Tim internal NIRA | Kelola tenant, monitoring langganan |

---

## 3. Ruang Lingkup Produk

### 3.1 Dalam Lingkup (In Scope) — MVP
- Autentikasi & RBAC dinamis (role & permission bisa diatur tenant)
- Manajemen produk, kategori, varian
- Manajemen stok per outlet
- Transaksi kasir (keranjang, pembayaran cash)
- Cetak & kirim struk digital
- Laporan dasar (penjualan harian, produk terlaris)
- PWA — dapat di-install dan diakses dari browser di desktop/tablet

### 3.2 Dalam Lingkup — Fase 2
- Integrasi payment gateway (Midtrans/Xendit)
- Promo, diskon, member & loyalty point
- Dashboard visual & ekspor laporan (Excel/PDF)
- Barcode/QR scan
- Panel Super Admin untuk kelola tenant & billing

### 3.3 Di Luar Lingkup (Out of Scope)
- Mode offline (sistem bersifat online-only)
- Modul khusus per jenis bisnis (laundry status tracking, gym membership, resep F&B) — dipertimbangkan di roadmap jangka panjang, bukan bagian MVP maupun Fase 2

---

## 4. Tech Stack

| Layer | Teknologi | Catatan |
|---|---|---|
| **Frontend** | React.js + TypeScript | Type-safe, komponen reusable |
| **UI Components** | shadcn/ui + Tailwind CSS | Konsisten, accessible, mudah dikustom |
| **State/Data Fetching** | TanStack Query (React Query) | Cocok untuk server-side filtering & caching |
| **Backend/API** | Laravel (PHP) — REST API | Mengacu pada skema database yang sudah dirancang sebelumnya |
| **Database** | MySQL | Sesuai skema migration yang sudah dibuat |
| **Platform** | PWA (Progressive Web App) | Installable, diakses via browser, online-only |
| **Access Control** | RBAC dinamis (role & permission per tenant) | Sudah dirancang di level database |

> Frontend dan backend dipisah (SPA + REST API), bukan Next.js full-stack, karena backend sudah dirancang di atas Laravel (mengacu pada migration yang sudah dibuat sebelumnya).

---

## 5. Kebutuhan Fungsional

### 5.1 Autentikasi & RBAC Dinamis
| ID | Requirement |
|---|---|
| FR-1.1 | Sistem menyediakan login berbasis email & password |
| FR-1.2 | Setiap user terhubung ke satu role, dan role menentukan permission yang dimiliki |
| FR-1.3 | Owner tenant dapat membuat, mengedit, dan menghapus role kustom (di luar role default: Owner/Manager/Cashier) |
| FR-1.4 | Owner tenant dapat mencentang/melepas permission spesifik untuk tiap role, tanpa perlu bantuan developer |
| FR-1.5 | UI frontend menyembunyikan/menonaktifkan elemen (tombol, menu) berdasarkan permission user yang sedang login |
| FR-1.6 | Setiap perubahan role/permission tercatat di audit log |

### 5.2 Manajemen Produk & Stok
| ID | Requirement |
|---|---|
| FR-2.1 | User dengan permission terkait dapat CRUD produk, kategori, dan varian |
| FR-2.2 | Stok tercatat per outlet dan berkurang otomatis saat transaksi selesai |
| FR-2.3 | Sistem menampilkan notifikasi/badge saat stok berada di bawah `min_stock_alert` |
| FR-2.4 | Field barcode disediakan di skema (scan aktif di Fase 2) |

### 5.3 Transaksi Kasir
| ID | Requirement |
|---|---|
| FR-3.1 | Kasir dapat menambah produk ke keranjang dan menghitung total otomatis |
| FR-3.2 | Sistem mendukung pembayaran cash (MVP), Midtrans/Xendit (Fase 2) |
| FR-3.3 | Struk dapat dicetak via printer thermal (Web Bluetooth/USB) dan dikirim secara digital |
| FR-3.4 | Kasir dengan permission `transaction.void` dapat membatalkan transaksi, dengan wajib mengisi alasan |
| FR-3.5 | Semua transaksi tercatat dengan cashier_id, outlet_id, dan timestamp |

### 5.4 Laporan
| ID | Requirement |
|---|---|
| FR-4.1 | User dengan permission `report.view` dapat melihat laporan penjualan harian/bulanan |
| FR-4.2 | Laporan menampilkan produk terlaris per outlet |
| FR-4.3 | (Fase 2) Laporan dapat diekspor ke Excel/PDF |

### 5.5 Filtering & Pencarian (Server-Side)
| ID | Requirement |
|---|---|
| FR-5.1 | Semua filter (produk, transaksi, laporan, user) diproses di server — tidak ada filtering di client-side untuk data besar |
| FR-5.2 | Input filter (search text, date range, dropdown) menerapkan **debounce ±400–500ms** sebelum memicu request ke API, agar tidak terjadi request berlebihan setiap ketikan |
| FR-5.3 | Parameter filter dikirim sebagai query string (contoh: `GET /api/products?outlet_id=..&category_id=..&search=..&page=1`) |
| FR-5.4 | Hasil filter menggunakan pagination server-side (bukan load semua data lalu filter di frontend) |
| FR-5.5 | Saat filter sedang memuat data baru, UI menampilkan loading state (skeleton) tanpa memblokir input pengguna |

### 5.6 PWA
| ID | Requirement |
|---|---|
| FR-6.1 | Aplikasi dapat di-install ke home screen (manifest.json + service worker) |
| FR-6.2 | Ukuran aset dioptimalkan untuk loading cepat di jaringan tablet/kios |
| FR-6.3 | Sistem tetap online-only — service worker digunakan untuk caching aset statis, bukan untuk transaksi offline |

---

## 6. Kebutuhan Non-Fungsional

### 6.1 Performa & Query Database
- Semua tabel dengan volume data besar (`products`, `transactions`, `stocks`) **wajib memiliki index** pada kolom yang sering difilter/disortir:
  - `products`: index komposit `(tenant_id, outlet_id)`, index `barcode`
  - `transactions`: index `outlet_id`, `created_at`, `cashier_id`
  - `stocks`: unique index `(product_variant_id, outlet_id)`
- Query filter server-side harus menggunakan `LIMIT`/`OFFSET` (atau cursor pagination) — tidak boleh menarik seluruh tabel ke memori lalu difilter di aplikasi.
- Target response time API untuk endpoint filter/list: **< 300ms** pada dataset hingga ~50.000 baris (dengan index yang sesuai).

### 6.2 Keamanan
- Setiap request API memvalidasi permission user di sisi server (bukan hanya menyembunyikan UI) — mencegah bypass lewat manipulasi frontend.
- Data antar tenant terisolasi penuh (setiap query wajib scoped `tenant_id`), agar satu bisnis tidak bisa melihat data bisnis lain.
- Password di-hash (bcrypt/argon2), token API menggunakan mekanisme standar (misal Laravel Sanctum).

### 6.3 Skalabilitas
- Arsitektur multi-tenant memungkinkan penambahan tenant baru tanpa perubahan skema.
- Backend dan frontend dipisah agar bisa di-scale independen.

### 6.4 Usability
- UI konsisten menggunakan shadcn/ui — accessible (kontras warna, keyboard navigation) dan responsif untuk tablet & desktop.
- Alur transaksi kasir dirancang seminim mungkin klik (idealnya ≤3 langkah dari pilih produk sampai bayar).

### 6.5 Reliabilitas
- Karena sistem online-only, tampilkan pesan jelas saat koneksi terputus (bukan gagal diam-diam).
- Transaksi yang gagal terkirim ke server tidak boleh mengurangi stok (harus atomic/transactional di level database).

---

## 7. Alur Pengguna Utama (User Flow)

### 7.1 Alur Transaksi Kasir
```
Login → Pilih Outlet (jika multi-outlet) → Layar Kasir
 → Cari/pilih produk (search server-side + debounce)
 → Tambah ke keranjang → Pilih metode bayar
 → Konfirmasi bayar → Stok berkurang otomatis
 → Cetak/kirim struk → Transaksi selesai
```

### 7.2 Alur Owner Mengatur Role
```
Login sebagai Owner → Menu "Manajemen Role"
 → Pilih role (atau buat baru) → Centang permission yang diizinkan
 → Simpan → Assign role ke user terkait
```

### 7.3 Alur Filter Data (contoh: daftar transaksi)
```
User mengetik di search box / pilih date range / pilih outlet
 → Debounce 400-500ms menunggu input berhenti
 → Request dikirim ke API dengan query params
 → Backend query dengan index yang sesuai + pagination
 → Frontend menampilkan hasil + skeleton loading saat proses
```

---

## 8. Metrik Keberhasilan (Success Metrics)

| Metrik | Target |
|---|---|
| Waktu rata-rata satu transaksi kasir | < 30 detik dari buka keranjang sampai struk tercetak |
| Response time endpoint filter/list | < 300ms (P95) pada dataset produksi |
| Uptime sistem | ≥ 99% (di luar downtime terjadwal) |
| Tenant aktif setelah 3 bulan onboarding | Target ditentukan tim bisnis (belum ada baseline) |

---

## 9. Asumsi & Risiko

| Kategori | Deskripsi |
|---|---|
| **Asumsi** | Semua outlet memiliki koneksi internet stabil (karena sistem online-only) |
| **Asumsi** | Pengguna (kasir) menggunakan tablet/komputer dengan browser modern yang mendukung PWA |
| **Risiko** | Tanpa mode offline, transaksi tidak bisa diproses saat internet mati — perlu dikomunikasikan jelas ke tenant saat onboarding |
| **Risiko** | RBAC dinamis menambah kompleksitas — perlu testing menyeluruh agar permission tidak salah konfigurasi dan membocorkan akses antar outlet/tenant |
| **Risiko** | Karena dikerjakan solo developer, timeline MVP fleksibel — prioritas fitur perlu dijaga ketat agar tidak scope creep |

---

## 10. Roadmap Ringkas

| Fase | Fokus |
|---|---|
| **MVP** | 1 tenant, 1 outlet — transaksi, produk, stok, laporan dasar, RBAC dinamis, PWA dasar |
| **Fase 2** | Multi-outlet aktif, payment gateway, promo/member, dashboard visual, ekspor laporan, barcode scan |
| **Fase 3** | Modul khusus per jenis bisnis (laundry, gym, F&B), integrasi hardware lanjutan |

---

*Dokumen ini adalah acuan awal produk. Kebutuhan teknis mendetail (skema database & migration) sudah tersedia di dokumen terpisah, dan dapat menjadi rujukan saat menyusun kontrak API antara frontend dan backend.*