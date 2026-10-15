
<?php

require_once __DIR__ . '/../config.php';

need('admin');

$pageTitle = 'Dashboard Admin';


/* ========================================
   DATA PENGGUNA DAN STOK SAMPAH
======================================== */

// Menghitung jumlah pengguna selain admin
$nUser = (int) $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role <> 'admin'
")->fetchColumn();

// Menghitung total stok sampah
$stok = (float) $pdo->query("
    SELECT COALESCE(SUM(stok_kg), 0)
    FROM sampah
")->fetchColumn();

// Menghitung total saldo pengguna selain admin
$beredar = (int) $pdo->query("
    SELECT COALESCE(SUM(saldo), 0)
    FROM users
    WHERE role <> 'admin'
")->fetchColumn();


/* ========================================
   DATA PERSETUJUAN
======================================== */

// Setoran yang menunggu persetujuan
$pS = (int) $pdo->query("
    SELECT COUNT(*)
    FROM transaksi
    WHERE tipe = 'setor'
      AND status = 'pending'
")->fetchColumn();

// Penjualan yang menunggu persetujuan
$pJ = (int) $pdo->query("
    SELECT COUNT(*)
    FROM transaksi
    WHERE tipe = 'jual'
      AND status = 'pending'
")->fetchColumn();

// Penarikan saldo yang menunggu persetujuan
$pT = (int) $pdo->query("
    SELECT COUNT(*)
    FROM tarik_saldo
    WHERE status = 'pending'
")->fetchColumn();


/* ========================================
   DATA STOK SAMPAH
======================================== */

$sampah = $pdo->query("
    SELECT *
    FROM sampah
    ORDER BY stok_kg DESC
")->fetchAll();

// Menentukan stok terbesar untuk diagram batang
$max = max(
    array_merge(
        [1],
        array_map(
            fn($r) => (float) $r['stok_kg'],
            $sampah
        )
    )
);


/* ========================================
   TRANSAKSI TERBARU
======================================== */

$last = $pdo->query("
    SELECT
        t.*,
        u.nama,
        s.jenis_sampah
    FROM transaksi t
    JOIN users u
        ON u.id_user = t.id_user
    JOIN sampah s
        ON s.id_sampah = t.id_sampah
    ORDER BY t.id_transaksi DESC
    LIMIT 6
")->fetchAll();


/* ========================================
   TAMPILKAN HEADER
======================================== */

require __DIR__ . '/../includes/header.php';

?>


<!-- ========================================
     STATISTIK DASHBOARD
======================================== -->

<div class="stats">

    <div class="stat">
        <span>Pengguna aktif</span>
        <strong><?= $nUser ?></strong>
        <small>guru, siswa, pengepul</small>
    </div>

    <div class="stat">
        <span>Stok sampah</span>
        <strong><?= kg($stok) ?></strong>
        <small><?= count($sampah) ?> jenis</small>
    </div>

    <div class="stat">
        <span>Saldo beredar</span>
        <strong><?= rp($beredar) ?></strong>
        <small>total saldo pengguna</small>
    </div>

</div>


<!-- ========================================
     PERSETUJUAN DAN STOK SAMPAH
======================================== -->

<div class="split">

    <!-- Daftar pengajuan -->
    <section class="card">

        <h2>Perlu persetujuan</h2>

        <ul class="todo">

            <li>
                <a href="setoran.php">
                    <span>Setoran sampah</span>

                    <b class="<?= $pS ? 'hot' : '' ?>">
                        <?= $pS ?>
                    </b>
                </a>
            </li>

            <li>
                <a href="penjualan.php">
                    <span>Penjualan ke pengepul</span>

                    <b class="<?= $pJ ? 'hot' : '' ?>">
                        <?= $pJ ?>
                    </b>
                </a>
            </li>

            <li>
                <a href="tarik.php">
                    <span>Penarikan saldo</span>

                    <b class="<?= $pT ? 'hot' : '' ?>">
                        <?= $pT ?>
                    </b>
                </a>
            </li>

        </ul>

    </section>


    <!-- Diagram stok sampah -->
    <section class="card">

        <h2>Stok per jenis</h2>

        <?php if (!$sampah): ?>

            <div class="empty">
                Belum ada data sampah.
            </div>

        <?php endif; ?>

        <div class="bars">

            <?php foreach ($sampah as $r): ?>

                <div class="bar">

                    <div class="bar-top">

                        <span>
                            <?= e($r['jenis_sampah']) ?>
                        </span>

                        <b>
                            <?= kg($r['stok_kg']) ?>
                        </b>

                    </div>

                    <div class="bar-track">

                        <i
                            style="width: <?= round(
                                (float) $r['stok_kg'] / $max * 100
                            ) ?>%"
                        ></i>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

</div>


<!-- ========================================
     TABEL TRANSAKSI TERBARU
======================================== -->

<section class="card">

    <h2>Transaksi terbaru</h2>

    <?php if (!$last): ?>

        <div class="empty">
            Belum ada transaksi.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <tr>
                    <th>Tanggal</th>
                    <th>Pengguna</th>
                    <th>Jenis</th>
                    <th>Sampah</th>
                    <th class="num">Berat</th>
                    <th class="num">Total</th>
                    <th>Status</th>
                </tr>

                <?php foreach ($last as $r): ?>

                    <tr>

                        <td>
                            <?= tgl($r['tanggal']) ?>
                        </td>

                        <td>
                            <?= e($r['nama']) ?>
                        </td>

                        <td>
                            <?= $r['tipe'] === 'setor'
                                ? 'Setoran'
                                : 'Penjualan'
                            ?>
                        </td>

                        <td>
                            <?= e($r['jenis_sampah']) ?>
                        </td>

                        <td class="num">
                            <?= kg($r['berat_kg']) ?>
                        </td>

                        <td class="num">
                            <?= rp($r['total_rp']) ?>
                        </td>

                        <td>
                            <?= badge($r['status']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    <?php endif; ?>

</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>