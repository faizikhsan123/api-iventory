# Invoice: Order manual dan deploy Docker Compose

Tampilan Invoice dikembalikan ke susunan sebelumnya, dengan kolom **Order** di antara **Division** dan **Client** sesuai gambar referensi. Form kontrak, periode pekerjaan, bulan otomatis, dan minggu tidak lagi digunakan.

## Mengatur urutan

1. Buka Tambah Invoice atau Edit Invoice.
2. Isi **Order** dengan bilangan bulat positif. Contoh: April = 10, Mei = 20, Juni = 30.
3. Simpan. Secara default angka kecil tampil lebih dahulu, termasuk ketika invoice baru dimasukkan belakangan atau berada pada halaman berbeda.
4. Untuk menyisipkan invoice di antara 10 dan 20, gunakan 15. Nomor tidak harus berurutan tanpa jarak.
5. Kosongkan Order untuk menempatkan invoice di bagian akhir. Invoice tidak disembunyikan atau dihapus.

Order berlaku pada seluruh daftar; filter divisi tetap tersedia. Angka boleh sama, tetapi tanggal layanan dan ID menjadi pembeda agar hasil tetap konsisten. Untuk urutan yang pasti berbeda, gunakan angka berbeda. Klik header Order jika ingin membalik naik/turun. Status tetap bisa diurutkan sebagai pembeda setelah Order.

Tanggal Service dan Invoice Submitted Date tetap seperti sebelumnya. Deskripsi W dan nama bulan tidak dipakai untuk menentukan Order. Admin dapat mengubah Order; office tetap hanya membaca.

## Data yang sudah ada

Migration baru `2026_11_08_000002_add_sort_order_to_invoices.php` hanya menambahkan kolom nullable `sort_order` dan index. Invoice, dokumen, riwayat, nominal, serta tanggal lama tidak diisi ulang atau dihapus.

Migration kontrak/periode sebelumnya tetap disimpan karena mungkin sudah dijalankan di server. Kolom tersebut dibiarkan di database agar nilai yang pernah diisi tetap tersimpan, tetapi tidak lagi dipakai untuk pengurutan dan form. **Tidak perlu rollback migration sebelumnya.**

Invoice lama yang belum diberi Order tampil di akhir dengan urutan tanggal layanan/ID seperti sebelumnya. Isi Order lewat Edit Invoice untuk menyusunnya sesuai kebutuhan.

## Deploy Docker Compose

Sebelum deploy:

- Backup SQL database yang sedang digunakan dan file upload backend.
- Pastikan konfigurasi Compose untuk container baru menetapkan `RUN_MIGRATE_FRESH: "false"`. Di proyek ini, nilai `true` akan menjalankan reset database dan seeder saat startup.
- Pertahankan database, nama project Compose, volume, konfigurasi koneksi DB, dan `APP_KEY` yang sama.
- Pastikan upload tersimpan pada volume/bind mount `storage/app`; dokumen Invoice ada di disk `local`, bukan hanya `storage/app/public`.

Setelah perubahan sudah di-commit dan di-push, pull kedua repository di server:

```sh
git -C /path/ke/api-iventory pull --ff-only
git -C /path/ke/fe-iventory pull --ff-only
```

Ganti kedua path sesuai checkout yang dipakai Compose. Dari folder Compose yang biasa digunakan:

```sh
docker compose config --services
docker compose up -d --build
docker compose ps
docker compose logs --tail=100 api
docker compose exec api php artisan migrate:status
```

Nama service backend pada contoh adalah `api`; sesuaikan dengan hasil `config --services`. Entrypoint backend sudah menjalankan `php artisan migrate --force` saat startup. Pastikan migration `2026_11_08_000002_add_sort_order_to_invoices` berstatus `Ran`.

Jangan menjalankan `docker compose down -v`, `migrate:fresh`, `migrate:refresh`, `db:wipe`, atau seeder ulang untuk update ini. Tidak perlu `key:generate`.

## Pemeriksaan setelah deploy

- Cocokkan jumlah invoice pada filter yang sama dengan sebelum deploy.
- Buka invoice lama dan periksa nominal, tanggal, dokumen, serta riwayatnya.
- Isi April = 10, Juni = 30, lalu Mei = 20. Pada urutan naik hasilnya April, Mei, Juni.
- Cek kolom Order pada tabel desktop, kartu mobile, serta form Tambah/Edit.

Build Docker frontend menggunakan `npx vite build`, yang sudah diuji berhasil. Perubahan database diuji pada `api-iventory_test`; pekerjaan ini tidak menjalankan migration pada database utama atau server produksi.
