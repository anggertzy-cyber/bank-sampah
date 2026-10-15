BANK SAMPAH - cara pasang (XAMPP)

1. Ekstrak zip ini, salin folder "banksampah" ke C:\xampp\htdocs\
2. Jalankan Apache dan MySQL di XAMPP.
3. Buka phpMyAdmin > Impor > pilih database/banksampah_anggerr.sql
   (database lama dengan nama sama akan diganti).
   Jika ingin mempertahankan data lama, jalankan database/migrasi_dari_database_lama.sql lewat tab SQL.
4. Buka http://localhost/banksampah/

Akun contoh (password MD5):
  admin     wanz / wanz
  guru      guru / guru
  siswa     siswa / siswa
  pengepul  pengepul / pengepul

Pengaturan koneksi database ada di config.php (default: root tanpa password).

Alur: setoran / pembelian / penarikan masuk berstatus "Menunggu".
Saldo dan stok sampah baru berubah setelah admin menyetujui.
