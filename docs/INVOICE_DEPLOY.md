# Deploy periode Invoice melalui Docker Compose

Perubahan ini hanya untuk Invoice: nomor kontrak/PO, periode mulai dan selesai, nomor minggu, filter kontrak, dan urutan periode di dalam kontrak. Tanggal submit tetap disimpan terpisah.

## Data yang sudah ada

Migration `2026_11_08_000001_add_work_period_to_invoices.php` hanya menambahkan empat kolom nullable dan satu index pada tabel `invoices`. Tidak ada penghapusan tabel, invoice, dokumen, riwayat, atau pengisian ulang data.

Invoice lama tetap tampil. Data yang belum memiliki kontrak tampil sebagai `Unassigned`, setelah kontrak yang sudah diisi. Periode kosong menggunakan `service_date` yang sudah ada untuk pengurutan. Lengkapi kontrak dan periode melalui Edit Invoice agar ikut kelompok yang diinginkan. Sistem tidak menebak tanggal pekerjaan dari deskripsi W atau tanggal submit.

## Sebelum rebuild container

Jalankan dari folder Compose di server. Nama service backend dalam contoh adalah `api`; sesuaikan dengan hasil perintah ini:

```sh
docker compose config --services
docker compose ps
docker compose exec api printenv RUN_MIGRATE_FRESH
```

Pastikan konfigurasi Compose yang akan dipakai container baru menetapkan `RUN_MIGRATE_FRESH: "false"`. Nilai pada container lama saja tidak cukup: periksa juga bagian `environment` atau `env_file` pada file Compose. Di proyek ini, `RUN_MIGRATE_FRESH=true` menjalankan `migrate:fresh --seed` saat startup dan menghapus data lama.

Sebelum melanjutkan:

1. Export backup SQL database yang sedang digunakan melalui alat backup server/phpMyAdmin. Simpan di luar container dan pastikan file berisi data, bukan pesan error.
2. Backup file upload pada storage backend. Pastikan `storage/app` dipertahankan melalui volume/bind mount; dokumen invoice disimpan pada disk `local`, bukan hanya `storage/app/public`. Rebuild bisa menghilangkan upload yang hanya tersimpan dalam filesystem container lama.
3. Pertahankan database, nama project Compose, volume, konfigurasi koneksi DB, dan `APP_KEY` yang sama. Jangan membuat database/volume pengganti saat update.

Jangan menjalankan `docker compose down -v`, `migrate:fresh`, `migrate:refresh`, `db:wipe`, atau seeder ulang untuk update ini. Tidak perlu menjalankan `key:generate`.

## Setelah perubahan sudah di-commit dan di-push

Di folder masing-masing repository:

```sh
git -C /path/ke/api-iventory pull --ff-only
git -C /path/ke/fe-iventory pull --ff-only
```

Ganti kedua path dengan lokasi checkout server yang dipakai build Compose. Lalu, dari folder Compose yang biasa dipakai untuk aplikasi ini:

```sh
docker compose up -d --build
docker compose ps
docker compose logs --tail=100 api
docker compose exec api php artisan migrate:status
```

Entrypoint backend yang ada sudah menjalankan `php artisan migrate --force` saat startup, lalu membuat cache Laravel. Migration penambahan periode Invoice seharusnya berstatus `Ran`. Tidak perlu menjalankan migration dua kali. Jangan lanjut jika log menunjukkan reset database atau kegagalan migration.

Dockerfile frontend yang ada memakai `npx vite build` dan menyalin hasil `dist` ke Nginx secara otomatis. Tidak perlu meng-install dependency langsung di host server untuk alur Docker ini.

## Pemeriksaan setelah deploy

1. Buka Invoice dan cocokkan jumlah invoice pada filter yang sama dengan sebelum deploy.
2. Buka invoice lama: nilai, nomor invoice, tanggal submit, dokumen, dan riwayat harus tetap tersedia.
3. Edit invoice yang memang perlu diurutkan, lalu isi nomor kontrak/PO dan periode pekerjaan. Nomor minggu opsional, berupa angka 1–53.
4. Pilih kontrak yang sama dari saran input agar penamaan konsisten. Masukkan periode Juni, April, Januari secara acak: pada urutan naik hasilnya Januari, April, Juni. Nomor minggu dipakai sebagai pembeda saat tanggal mulai sama.
5. Pastikan kontrak lain tetap berkelompok terpisah. Jika memilih pengurutan status, status menjadi prioritas di dalam kontrak; kosongkan urutan status untuk urutan periode murni.

Untuk kembali ke versi aplikasi sebelumnya, gunakan image/commit aplikasi sebelumnya dengan database dan volume yang sama. Kolom tambahan nullable dapat tetap berada di database; tidak perlu rollback migration yang akan menghapus nilai periode baru. Pemulihan backup database dilakukan hanya jika memang diperlukan.

## Validasi lokal

- `php artisan test --filter=Invoice`: 36 tes lulus, 228 assertion, termasuk penjagaan data lama saat migrasi, pengurutan lintas halaman/tahun, minggu numerik, izin akses, dan dokumen.
- `node --test tests/invoicePeriod.test.mjs`: 2 tes lulus.
- ESLint pada file Invoice yang diubah lulus.
- `npx vite build`: lulus, sesuai proses build Docker frontend.
- `npm run build` masih terhalang error TypeScript yang sudah ada pada Dashboard, form Barang, dan helper tanggal. File di luar Invoice tidak diubah untuk memperbaiki error tersebut.

Validasi migration dilakukan pada `api-iventory_test`; migration belum diterapkan oleh pekerjaan ini ke database utama atau server produksi.
