-- Pakai file ini HANYA jika database banksampah_anggerr sudah Anda import sebelumnya
-- dan tidak ingin menghapus datanya. (Kalau tidak keberatan data di-reset, import
-- banksampah_anggerr.sql saja.)
USE banksampah_anggerr;
ALTER TABLE transaksi ADD COLUMN status enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending' AFTER total_rp;
UPDATE transaksi SET status='disetujui';
UPDATE users SET password=MD5(password) WHERE CHAR_LENGTH(password)<>32;
