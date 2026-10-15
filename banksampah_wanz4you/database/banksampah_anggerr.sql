CREATE DATABASE IF NOT EXISTS banksampah_anggerr CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE banksampah_anggerr;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS tarik_saldo, transaksi, sampah, users;

CREATE TABLE users (
  id_user int(11) NOT NULL AUTO_INCREMENT,
  nama varchar(100) NOT NULL,
  username varchar(50) NOT NULL,
  password varchar(255) NOT NULL,
  saldo int(11) NOT NULL DEFAULT 0,
  role enum('admin','guru','siswa','pengepul') NOT NULL,
  PRIMARY KEY (id_user), UNIQUE KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sampah (
  id_sampah int(11) NOT NULL AUTO_INCREMENT,
  jenis_sampah varchar(50) NOT NULL,
  harga_per_kg int(11) NOT NULL,
  stok_kg decimal(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_sampah)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transaksi (
  id_transaksi int(11) NOT NULL AUTO_INCREMENT,
  id_user int(11) NOT NULL,
  id_sampah int(11) NOT NULL,
  tanggal date NOT NULL,
  tipe enum('setor','jual') NOT NULL,
  berat_kg decimal(10,2) NOT NULL,
  total_rp int(11) NOT NULL,
  status enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (id_transaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tarik_saldo (
  id_tarik int(11) NOT NULL AUTO_INCREMENT,
  id_user int(11) NOT NULL,
  nominal int(11) NOT NULL,
  tanggal date NOT NULL,
  status enum('pending','diterima','ditolak') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (id_tarik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password disimpan dengan MD5
INSERT INTO users (id_user,nama,username,password,saldo,role) VALUES
(1,'angger','wanz',MD5('wanz'),0,'admin'),
(2,'guru','guru',MD5('guru'),15000,'guru'),
(3,'siswa','siswa',MD5('siswa'),28750,'siswa'),
(4,'pengepul','pengepul',MD5('pengepul'),0,'pengepul');

INSERT INTO sampah (id_sampah,jenis_sampah,harga_per_kg,stok_kg) VALUES
(1,'plastik',3000,7.00),(2,'kaleng',2500,4.50),(5,'kertas',5000,3.00);

INSERT INTO transaksi (id_transaksi,id_user,id_sampah,tanggal,tipe,berat_kg,total_rp,status) VALUES
(1,3,1,'2026-09-17','setor',7.50,22500,'disetujui'),
(2,3,2,'2026-09-21','setor',2.50,6250,'disetujui'),
(3,2,1,'2026-09-22','setor',5.00,15000,'disetujui');

INSERT INTO tarik_saldo (id_user,nominal,tanggal,status) VALUES
(3,1000,'2026-09-17','pending'),(3,10,'2026-09-17','pending'),(3,10000,'2026-09-17','pending'),
(3,1000,'2026-09-21','pending'),(3,3000,'2026-09-22','pending');

ALTER TABLE users AUTO_INCREMENT=5; ALTER TABLE sampah AUTO_INCREMENT=6; ALTER TABLE transaksi AUTO_INCREMENT=4;
SET FOREIGN_KEY_CHECKS=1;
