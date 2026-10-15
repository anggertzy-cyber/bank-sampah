
<?php

require_once __DIR__ . '/../config.php';

// Batasi akses untuk guru, siswa, dan pengepul
need(['guru', 'siswa', 'pengepul']);

$u = user();

// Tentukan jenis transaksi berdasarkan role
$jual = $u['role'] === 'pengepul';
$tipe = $jual ? 'jual' : 'setor';

$pageTitle = 'Halo, ' . $u['nama'];


/* =========================================
   TOTAL BERAT TRANSAKSI YANG DISETUJUI
========================================= */

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(berat_kg), 0)
     FROM transaksi
     WHERE id_user = ?
       AND tipe = ?
       AND status = 'disetujui'"
);

$stmt->execute([
    $u['id_user'],
    $tipe
]);

$totalKg = (float) $stmt->fetchColumn();


/* =========================================
   JUMLAH PENGAJUAN YANG MENUNGGU
========================================= */

$stmt = $pdo->prepare(
    "SELECT
        (
            SELECT COUNT(*)
            FROM transaksi
            WHERE id_user = ?
              AND status = 'pending'
        )
        +
        (
            SELECT COUNT(*)
            FROM tarik_saldo
            WHERE id_user = ?
              AND status = 'pending'
        )"
);

$stmt->execute([
    $u['id_user'],
    $u['id_user']
]);

$pending = (int) $stmt->fetchColumn();


/* =========================================
   DAFTAR HARGA DAN STOK SAMPAH
========================================= */

$harga = $pdo->query(
    'SELECT *
     FROM sampah
     ORDER BY jenis_sampah'
)->fetchAll();


/* =========================================
   AMBIL LIMA TRANSAKSI TERAKHIR
========================================= */

$stmt = $pdo->prepare(
    "SELECT
        t.*,
        s.jenis_sampah
     FROM transaksi t
     JOIN sampah s
        ON s.id_sampah = t.id_sampah
     WHERE t.id_user = ?
     ORDER BY t.id_transaksi DESC
     LIMIT 5"
);

$stmt->execute([$u['id_user']]);

$last = $stmt->fetchAll();


/* =========================================
   TAMPILKAN HEADER
========================================= */

require __DIR__ . '/../includes/header.php';

?>


<!-- =====================================
     INFORMASI SALDO PENGGUNA
====================================== -->

<section class="saldo">

    <div>

        <span>Saldo Anda</span>

        <strong>
            <?= rp($u['saldo']) ?>
        </strong>

    </div>

    <!-- Tombol aksi -->
    <div class="saldo-act">

        <a
            class="btn btn-light"
            href="transaksi.php"
        >
            <?= $jual ? 'Beli sampah' : 'Setor sampah' ?>
        </a>

        <a
            class="btn btn-outline"
            href="tarik.php"
        >
            Tarik saldo
        </a>

    </div>

</section>


<!-- =====================================
     STATISTIK PENGGUNA
====================================== -->

<div class="stats">

    <!-- Total berat transaksi -->
    <div class="stat">

        <span>
            <?= $jual
                ? 'Total sampah dibeli'
                : 'Total sampah disetor'
            ?>
        </span>

        <strong>
            <?= kg($totalKg) ?>
        </strong>

        <small>
            yang sudah disetujui
        </small>

    </div>

    <!-- Jumlah pengajuan pending -->
    <div class="stat">

        <span>
            Menunggu persetujuan
        </span>

        <strong>
            <?= $pending ?>
        </strong>

        <small>
            transaksi dan penarikan
        </small>

    </div>

</div>


<div class="split">


    <!-- =================================
         TABEL HARGA SAMPAH
    ================================== -->

    <section class="card">

        <h2>Harga sampah</h2>

        <?php if (!$harga): ?>

            <div class="empty">
                Admin belum menambahkan jenis sampah.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Jenis</th>

                            <th class="num">
                                Harga per kg
                            </th>

                            <?php if ($jual): ?>

                                <th class="num">
                                    Stok
                                </th>

                            <?php endif; ?>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($harga as $r): ?>

                            <tr>

                                <!-- Nama jenis sampah -->
                                <td>
                                    <?= e($r['jenis_sampah']) ?>
                                </td>

                                <!-- Harga per kilogram -->
                                <td class="num">
                                    <?= rp($r['harga_per_kg']) ?>
                                </td>

                                <!-- Stok untuk pengepul -->
                                <?php if ($jual): ?>

                                    <td class="num">
                                        <?= kg($r['stok_kg']) ?>
                                    </td>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>


    <!-- =================================
         TRANSAKSI TERAKHIR
    ================================== -->

    <section class="card">

        <h2>Transaksi terakhir</h2>

        <?php if (!$last): ?>

            <div class="empty">

                Belum ada transaksi.
                Mulai dari menu
                <?= $jual ? 'Beli Sampah' : 'Setor Sampah' ?>.

            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Tanggal</th>

                            <th>Sampah</th>

                            <th class="num">
                                Total
                            </th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($last as $r): ?>

                            <tr>

                                <!-- Tanggal transaksi -->
                                <td>
                                    <?= tgl($r['tanggal']) ?>
                                </td>

                                <!-- Nama dan berat sampah -->
                                <td>

                                    <?= e($r['jenis_sampah']) ?>

                                    <small class="muted">
                                        <?= kg($r['berat_kg']) ?>
                                    </small>

                                </td>

                                <!-- Total harga -->
                                <td class="num">
                                    <?= rp($r['total_rp']) ?>
                                </td>

                                <!-- Status pengajuan -->
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

</div>


<?php

/* =========================================
   TAMPILKAN FOOTER
========================================= */

require __DIR__ . '/../includes/footer.php';

?>