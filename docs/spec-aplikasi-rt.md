# Spec — RT Digital (Aplikasi Pengelolaan RT)

Status: draft untuk ditinjau Iwan · Tanggal: 6 Okt 2026
Hasil grilling Iwan (ronde 1 & 2) → dokumen ini.

## 1. Goal

Memberi ketua/pengurus RT satu tempat untuk mengelola **iuran & kas, data warga, dan surat-menyurat**,
sehingga: pembukuan tidak lagi di buku tulis/Excel, tunggakan kelihatan otomatis, dan warga bisa
mengecek sendiri uangnya ke mana tanpa harus menunggu laporan rapat.

Dibangun **multi-RT sejak awal** (satu instalasi, banyak RT, data terpisah), tapi dipakai pertama untuk
**satu RT sebagai studi kasus**. Kalau nanti dijual ke RT lain, tidak perlu bongkar arsitektur.

## 2. Scope

### MVP (fase 1) — wajib ada

**M1 — Data Warga**
- Kartu Keluarga (KK) + anggota keluarga: kepala keluarga, hubungan, NIK, tanggal lahir, pekerjaan, no HP
- Status hunian: milik / sewa / kontrak / kos; alamat + nomor rumah
- Mutasi: pindah masuk, pindah keluar, meninggal — dengan tanggal dan riwayat
- Pencarian & filter (per blok/RT, per status hunian), export Excel

**M2 — Iuran & Kas**
- Jenis iuran + tarif (mis. Iuran Keamanan & Kebersihan Rp 100.000/bulan), bisa beda tarif per rumah
  (pemilik vs penghuni vs usaha), periode bulanan/tahunan, tanggal berlaku
- **Tagihan otomatis** per periode untuk semua keluarga aktif (command + scheduler, tanggal 1 tiap bulan)
- Catat pembayaran: tunai / transfer, tanggal, jumlah (boleh sebagian), upload bukti transfer
- Status tagihan: belum / sebagian / lunas / dibebaskan (dengan alasan)
- Daftar tunggakan per warga + total tunggakan per RT
- Kas masuk/keluar non-iuran (sumbangan, biaya perbaikan, konsumsi rapat) dengan kategori
- **Invarian: saldo = saldo awal + Σ masuk − Σ keluar**, dihitung per pos (**tunai** dan **bank** terpisah),
  aritmetika hanya di satu service — jangan hitung di view
- Kwitansi PDF bernomor per pembayaran, laporan bulanan PDF + export Excel
- **Halaman transparansi kas publik per RT**: rekap bulanan (masuk, keluar, kategori, saldo) bisa dibuka
  warga lewat link tanpa login — ini yang membangun kepercayaan

**M3 — Surat Pengantar**
- Jenis surat + template (domisili, SKCK, usaha, nikah, keterangan tidak mampu, dll)
- Alur: warga/pengurus input pengajuan → pengurus setujui → PDF terbit
- **Nomor surat otomatis** per jenis per tahun (format `001/RT.05/RW.03/GSI/2026`)
- Arsip surat + log (siapa buat, kapan, keperluan apa)
- **QR verifikasi di tiap PDF**: memindai QR → halaman verifikasi publik menampilkan keaslian surat
  (nomor, jenis, nama, tanggal terbit). Nomor + QR = surat palsu buatan sendiri langsung ketahuan

**M4 — Dashboard & Peran**
- Dashboard: jumlah KK & jiwa, tunggakan bulan ini, saldo kas, surat bulan ini, grafik iuran 6 bulan
- Peran: `superadmin` (kamu/Dea — kelola RT & akun), `ketua_rt`, `sekretaris` (warga & surat),
  `bendahara` (iuran & kas), `pengurus` (lihat saja)

### Fase 2 — sengaja ditunda
- Akun/login warga penuh + pengajuan surat mandiri dari HP
- Pengumuman + broadcast notifikasi (channel bisa ditukar; WA menunggu gateway)
- Pengaduan fasilitas (kategori, foto, status baru/diproses/selesai)
- Jadwal ronda + buku ronda, buku tamu / warga menginap
- Inventaris aset RT (pola sama seperti modul inventaris PKK)
- Agenda kegiatan & rapat, absensi
- QRIS / virtual account untuk bayar iuran online (butuh payment gateway + fee + rekonsiliasi)

### Di luar scope
- Absensi QR, CCTV, panic button, WA blast massal marketing
- Sinkronisasi ke sistem kelurahan/dukcapil (tidak ada API resmi)

## 3. Tech decisions

| Perkara | Keputusan | Alasan |
|---|---|---|
| Framework | **Laravel 13 + Blade + AdminLTE 4** (aset lokal, tanpa CDN) | sama seperti PKK Digital; Dea sudah punya pola & template, termasuk jebakan yang sudah terpetakan |
| DB | **MySQL 8** — dev `rt_digital_dev` di dea-geekom; produksi DB `rt_app` di container MySQL yang sama dengan PKK (VM 2 GB terlalu ketat untuk MySQL kedua) | hemat ~400 MB RAM di VM |
| Multi-tenant | **satu DB + kolom `tenant_id`** di semua tabel + global scope, trait `BelongsToRt` | migrasi & backup sekali; bikin RT baru instan |
| Izin | `spatie/laravel-permission` + `spatie/laravel-activitylog` | sudah terbukti di PKK |
| Login | **username** (bukan email) | pengurus RT lebih mudah ingat username pendek |
| PDF | dompdf (landscape bila kolom > 6; **tidak** ada F4 — cetak A4) | pola PKK |
| OCR/import | tidak ada di fase 1 | data awal diinput manual (jumlah KK RT kecil) |
| UI | Bahasa Indonesia, tema **finance-professional teal/emerald** (bukan biru default AntD/AdminLTE), compact numbers (juta/ribu), anti-slop 38 rules | standar Iwan |
| Notifikasi | interface channel bisa ditukar; aktif Telegram/Email dulu, **WA menyusul** (gateway sedang review) | WA-Hub belum bisa dipakai |
| Deploy | Docker multi-stage (build di dea-geekom → save → scp → load), nginx host reverse proxy `127.0.0.1:8081`, certbot | pola PKK |
| Domain | **rt.pkk-digital.sbs** | dipilih Iwan |

### Konfigurasi wajib (jebakan yang sudah diketahui)
- `config/app.php` harus `'timezone' => env('APP_TIMEZONE', 'Asia/Makassar')` — Laravel 13 meng-hardcode UTC.
- nginx (host **dan** container) **tidak boleh** ada `listen [::]:80` — VM IDCloudhost tanpa IPv6.
- Dockerfile butuh `libonig-dev` (mbstring butuh oniguruma).
- Laravel di belakang proxy: `trustProxies(at: '*')` + `forceScheme('https')` di production.
- `docs/` dan `*.tar.gz` masuk `.dockerignore` (arsip deploy tidak boleh ikut ter-burn ke image).

## 4. DB changes (skema awal)

Semua tabel bertenant punya `tenant_id` (FK `rts.id`) + index gabungan dengan kolom kunci pencarian.

**Tenancy & akun**
- `rts` — id, nama (`RT 05`), rw, kelurahan, kecamatan, kota, kode_pos, nama_ketua, nama_sekretaris, nama_bendahara, alamat_sekretariat, no_hp, slug (untuk link publik), publik_aktif (bool), timestamps
- `users` — id, tenant_id (nullable untuk superadmin), username (unik), nama, email (kontak saja), password, aktif
- `permissions`, `roles`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — spatie

**Data warga**
- `keluarga` — id, tenant_id, no_kk (encrypted), no_kk_hash (HMAC, untuk cek duplikat), alamat, blok_unit, rt_lingkungan, status_hunian (`milik|sewa|kontrak|kos`), nama_pemilik (bila bukan pemilik), tanggal_masuk, tanggal_keluar, status (`aktif|pindah|nonaktif`), keterangan
- `warga` — id, tenant_id, keluarga_id, nik (encrypted), nik_hash, nik_4_terakhir, nama, hubungan (`kepala|istri|anak|familiar|lainnya`), jenis_kelamin, tanggal_lahir, pekerjaan, agama, status_perkawinan, no_hp, status (`aktif|pindah|meninggal`), catatan
- `warga_mutasi` — id, tenant_id, warga_id (nullable), keluarga_id, jenis (`masuk|keluar|lahir|meninggal|ubah_kk`), tanggal, keterangan, dicatat_oleh
- `dokumen_warga` — id, tenant_id, keluarga_id/warga_id, jenis (`ktp|kk|lainnya`), nama_asli, file_path (storage privat), uploaded_by

**Iuran & kas**
- `iuran_jenis` — id, tenant_id, nama, nominal_default, periode (`bulanan|tahunan|sekali`), berlaku_dari, berlaku_sampai, aktif
- `iuran_tarif` — id, tenant_id, iuran_jenis_id, keluarga_id (nullable = tarif umum), nominal, keterangan — untuk tarif berbeda per rumah
- `iuran_tagihan` — id, tenant_id, keluarga_id, iuran_jenis_id, periode (`2026-10`), nominal, jatuh_tempo, status (`belum|sebagian|lunas|bebas`), alasan_bebas, timestamps; **unique (tenant_id, keluarga_id, iuran_jenis_id, periode)**
- `iuran_pembayaran` — id, tenant_id, iuran_tagihan_id, tanggal, jumlah, metode (`tunai|transfer|lainnya`), no_referensi, bukti_path (privat), dicatat_oleh, timestamps
- `kwitansi` — id, tenant_id, iuran_pembayaran_id, nomor (urut per tahun), tanggal, pdf_path
- `kas_kategori` — id, tenant_id, nama, jenis (`masuk|keluar`)
- `kas_saldo_awal` — id, tenant_id, tahun, pos (`tunai|bank`), jumlah
- `kas_transaksi` — id, tenant_id, tanggal, jenis (`masuk|keluar`), pos (`tunai|bank`), kas_kategori_id, uraian, jumlah, no_bukti, iuran_pembayaran_id (nullable — pembayaran iuran otomatis jadi kas masuk), created_by

**Surat**
- `surat_jenis` — id, tenant_id, kode (`SKD`), nama, kode_nomor (untuk format nomor), template_body (Blade/placeholder), butuh_data_warga (bool), aktif
- `surat` — id, tenant_id, surat_jenis_id, keluarga_id, warga_id (nullable), nomor_urut, tahun, nomor_lengkap, keperluan, data_tambahan (json), tanggal_ajuan, tanggal_terbit, status (`draft|diajukan|disetujui|ditolak|terbit|batal`), alasan_tolak, dibuat_oleh, disetujui_oleh, disetujui_at, file_path, kode_verifikasi (unik, dipakai QR), timestamps
  - **unique (tenant_id, surat_jenis_id, nomor_urut, tahun)** — nomor urut harus punya kolom tahun sendiri — jebakan yang sudah menggigit di PKK
- `surat_catatan` — id, surat_id, catatan, oleh, timestamps

**Dukungan**
- `pengaturan` — id, tenant_id, key, value (nama penandatangan, kop surat, logo, format nomor surat)
- `notifikasi_log` — id, tenant_id, channel, tujuan, template, payload, status, error, timestamps
- `activity_log` — spatie, dengan `tenant_id` di properties

**Aturan penyimpanan data pribadi**
- NIK & no KK: `encrypted` cast (AES-256-CBC / APP_KEY). Karena hasil enkripsi acak, pencarian & cek duplikat
  pakai `nik_hash` = HMAC-SHA256(nik, APP_KEY) — bukan hash polos.
- Tampilan hanya NIK tersamar: `3271••••••••1234` (pakai `nik_4_terakhir`).
- Scan KTP/KK di disk **privat**, disajikan lewat rute terautentikasi + izin `lihat_dokumen_warga`, tidak pernah di `public/`.
- Akses dokumen dicatat ke activity log.

## 5. UI/UX

**Peta halaman (MVP)**
- `/login` — username + password, tanpa self-signup
- `/dashboard` — kartu: total KK, total jiwa, tunggakan bulan ini (Rp + jumlah KK), saldo kas (tunai/bank),
  surat terbit bulan ini; grafik batang iuran 6 bulan terakhir
- `/warga` — daftar keluarga (kartu/list), filter blok + status hunian + status aktif, tombol tambah
  → `/warga/{keluarga}` detail: anggota, mutasi, dokumen, tunggakan keluarga itu
- `/warga/{keluarga}/edit`, `/warga/mutasi` (form pindah masuk/keluar)
- `/iuran` — grid bulanan: baris keluarga × kolom bulan, sel berwarna (lunas/sebagian/belum) — inilah
  tampilan yang paling sering dipakai bendahara; klik sel → catat pembayaran
- `/iuran/tagihan` — buat/perbarui tagihan per periode, daftar tunggakan, tombol bebas tagihan
- `/iuran/pembayaran/{id}/kwitansi` — PDF
- `/kas` — buku kas per pos (tunai/bank) dengan saldo berjalan, filter bulan, saldo awal, kategori
- `/kas/laporan` — laporan bulanan + PDF + Excel
- `/surat` — daftar surat + filter status; `/surat/baru` (pilih jenis → form menyesuaikan);
  `/surat/{id}` detail + tombol setujui/cetak
- `/verifikasi/{kode}` — **publik**, menampilkan keaslian surat (nomor, jenis, nama, tanggal terbit); tanpa data sensitif
- `/t/{slug}/kas` — **publik**, transparansi rekap kas bulanan (angka saja, tanpa nama warga)
- `/pengguna`, `/peran` — superadmin; `/rt` — superadmin kelola RT
- `/ubah-sandi` — semua peran

**Aturan UI**
- Mobile-first: bendahara & ketua lebih sering buka dari HP. Tabel lebar → kartu bertumpuk di layar kecil.
- Angka ringkas: `Rp 12,5 jt`, `Rp 850 rb` (tooltip/nilai penuh di detail).
- Diagram minimal: satu grafik batang di dashboard saja.
- Warna: teal/emerald (finance-professional), **bukan** biru default AdminLTE.
- Merek AdminLTE dihilangkan lewat override view vendor (`brand-logo-xs`, `lifecycle`), menu sidebar dibangun
  dari listener `BuildingMenu` — pola PKK, bukan edit `config/adminlte.php` saja.
- Konfirmasi hapus: modal + kalimat menyebut nama data yang dihapus.
- **Angka uang tidak pernah dihitung di Blade** — semua lewat service.

## 6. API endpoints

MVP jalan penuh tanpa API publik (Blade server-side). Yang ada:

**Internal (web, dengan CSRF + izin)**
- `GET|POST /warga`, `GET|PUT /warga/{keluarga}`, `POST /warga/{keluarga}/anggota`, `POST /warga/mutasi`
- `GET /iuran?periode=2026-10`, `POST /iuran/tagihan/generate`, `POST /iuran/pembayaran`, `POST /iuran/tagihan/{id}/bebas`
- `GET /kas`, `POST /kas/transaksi`, `POST /kas/saldo-awal`, `GET /kas/laporan`
- `GET|POST /surat`, `POST /surat/{id}/setujui`, `POST /surat/{id}/tolak`, `GET /surat/{id}/pdf`
- `GET /dokumen/{dokumen}/berkas` — stream file privat
- `GET /export/{jenis}` — Excel (warga, iuran, kas, surat)

**Publik (tanpa login, rate-limited)**
- `GET /verifikasi/{kode}` — validasi surat
- `GET /t/{slug}/kas?bulan=2026-10` — rekap kas publik (angka agregat saja)
- `GET /t/{slug}/iuran` — ringkasan kolektibilitas iuran bulan berjalan (opsional, tanpa nama)

**Fase 2 (kalau warga diberi login)**
- `POST /api/v1/auth/login` (Sanctum, OTP WA/Telegram)
- `GET /api/v1/me/tunggakan`, `POST /api/v1/surat` (ajukan), `GET /api/v1/me/surat/{id}`

**Notifikasi keluar**
- `POST /api/v1/messages` ke WA-Hub (pola Pratasaba/VASIA POS) — **nonaktif sampai gateway pulih**
- Telegram Bot API / SMTP untuk fase 2

## 7. Risks

| Risiko | Dampak | Mitigasi |
|---|---|---|
| **VM pkk-digital 2 GB RAM** dipakai 2 app + 2 DB | OOM, app PKK ikut terganggu | satu container MySQL bersama (DB terpisah); pantau `free -h` + uptime-kuma; kalau RT bertambah → VM terpisah |
| Data pribadi (NIK/KK) bocor | hukum + kepercayaan hancur | enkripsi kolom, storage privat, izin ber-role, log akses, jangan pernah export NIK penuh tanpa izin khusus |
| Notifikasi WA belum bisa (gateway in review) | fitur pengingat iuran tertunda | channel pluggable, Telegram/email dulu; keputusan nomor baru milik Iwan |
| Warga tidak mau pakai ("ribet") | data tidak terisi, app mati | pengurus yang input (bukan warga); warga cukup buka link publik; input massal di awal dibantu |
| Adopsi RT lain (kalau dijual) rendah | investasi sia-sia | studi kasus RT sendiri dulu; jangan bangun billing/self-signup sebelum ada yang minta |
| Surat palsu/penyalahgunaan | nama RT tercemar | QR verifikasi + nomor urut tercatat + log penerbitan |
| Salah hitung/tagihan ganda | sengketa warga | unique tagihan per periode, aritmetika di service + unit test invarian saldo |
| Nomor urut surat bentrok tahun baru | surat gagal terbit | kolom `tahun` sendiri di unique index |

## 8. Pertanyaan terbuka

1. **Nama produk** — usulan Dea: Rukun / Warga Kita / RT Digital (untuk nama app, logo, judul halaman).
2. **Nomor WA gateway** — siapkan SIM/nomor baru atau tunggu review nomor lama.
3. **Data awal** — RT mana yang jadi studi kasus, dan apakah Dea dibantu data KK (Excel) untuk diinput?
