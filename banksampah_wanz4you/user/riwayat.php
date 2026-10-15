
<?php

require_once __DIR__ . '/../config.php';

// Batasi akses untuk guru, siswa, dan pengepul
need(['guru', 'siswa', 'pengepul']);

$u = user();

// Tentukan role pengguna
$jual = $u['role'] === 'pengepul';

$pageTitle = 'Riwayat';


/* =========================================
   AMBIL RIWAYAT TRANSAKSI SAMPAH
========================================= */

$stmt = $pdo->prepare(
    "SELECT
        t.*,
        s.jenis_sampah
    FROM transaksi t
    JOIN sampah s
        ON s.id_sampah = t.id_sampah
    WHERE t.id_user = ?
    ORDER BY t.id_transaksi DESC"
);

$stmt->execute([$u['id_user']]);

$transaksi = $stmt->fetchAll();


/* =========================================
   AMBIL RIWAYAT PENARIKAN SALDO
========================================= */

$stmt = $pdo->prepare(
    "SELECT *
    FROM tarik_saldo
    WHERE id_user = ?
    ORDER BY id_tarik DESC"
);

$stmt->execute([$u['id_user']]);

$penarikan = $stmt->fetchAll();


/* =========================================
   TAMPILKAN HEADER
========================================= */

require __DIR__ . '/../includes/header.php';

?>


<!-- =====================================
     RIWAYAT SETORAN / PEMBELIAN SAMPAH
====================================== -->

<section class="card">

    <h2>
        <?= $jual ? 'Pembelian sampah' : 'Setoran sampah' ?>
    </h2>

    <?php if (!$transaksi): ?>

        <div class="empty">
            Belum ada transaksi.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Sampah</th>
                        <th class="num">Berat</th>
                        <th class="num">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($transaksi as $r): ?>

                        <tr>

                            <!-- Tanggal transaksi -->
                            <td>
                                <?= tgl($r['tanggal']) ?>
                            </td>

                            <!-- Jenis sampah -->
                            <td>
                                <?= e($r['jenis_sampah']) ?>
                            </td>

                            <!-- Berat sampah -->
                            <td class="num">
                                <?= kg($r['berat_kg']) ?>
                            </td>

                            <!-- Total transaksi -->
                            <td class="num">
                                <?= rp($r['total_rp']) ?>
                            </td>

                            <!-- Status transaksi -->
                            <td>
                                <?= badge($r['status']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


<!-- =====================================
     RIWAYAT PENARIKAN SALDO
====================================== -->

<section class="card">

    <h2>Penarikan saldo</h2>

    <?php if (!$penarikan): ?>

        <div class="empty">
            Belum ada penarikan.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="num">Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($penarikan as $r): ?>

                        <tr>

                            <!-- Tanggal penarikan -->
                            <td>
                                <?= tgl($r['tanggal']) ?>
                            </td>

                            <!-- Nominal penarikan -->
                            <td class="num">
                                <?= rp($r['nominal']) ?>
                            </td>

                            <!-- Status penarikan -->
                            <td>
                                <?= badge($r['status']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


<?php

/* =========================================
   TAMPILKAN FOOTER
========================================= */

require __DIR__ . '/../includes/footer.php';

?>