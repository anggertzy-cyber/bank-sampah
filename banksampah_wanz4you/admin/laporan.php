
<?php

require_once __DIR__ . '/../config.php';

need('admin');

$pageTitle = 'Laporan Transaksi';


/* ========================================
   FILTER LAPORAN
======================================== */

$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$tipe   = $_GET['tipe'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$p = [];

// Filter tanggal mulai
if ($dari !== '') {
    $where[] = 't.tanggal >= ?';
    $p[] = $dari;
}

// Filter tanggal akhir
if ($sampai !== '') {
    $where[] = 't.tanggal <= ?';
    $p[] = $sampai;
}

// Filter jenis transaksi
if (in_array($tipe, ['setor', 'jual'], true)) {
    $where[] = 't.tipe = ?';
    $p[] = $tipe;
}

// Filter status transaksi
if (
    in_array(
        $status,
        ['pending', 'disetujui', 'ditolak'],
        true
    )
) {
    $where[] = 't.status = ?';
    $p[] = $status;
}


/* ========================================
   AMBIL DATA TRANSAKSI
======================================== */

$sql = "
    SELECT
        t.*,
        u.nama,
        u.role,
        s.jenis_sampah
    FROM transaksi t
    JOIN users u
        ON u.id_user = t.id_user
    JOIN sampah s
        ON s.id_sampah = t.id_sampah
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= "
    ORDER BY
        t.tanggal DESC,
        t.id_transaksi DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($p);

$rows = $stmt->fetchAll();


/* ========================================
   HITUNG TOTAL TRANSAKSI DISETUJUI
======================================== */

$tot = [
    'setor' => [0, 0],
    'jual'  => [0, 0],
];

foreach ($rows as $r) {

    if ($r['status'] === 'disetujui') {

        $tot[$r['tipe']][0] += (float) $r['berat_kg'];
        $tot[$r['tipe']][1] += (float) $r['total_rp'];
    }
}


/* ========================================
   TAMPILKAN HEADER
======================================== */

require __DIR__ . '/../includes/header.php';

?>


<!-- ========================================
     FORM FILTER LAPORAN
======================================== -->

<section class="card no-print">

    <form method="get" class="filters">

        <label>
            Dari

            <input
                type="date"
                name="dari"
                value="<?= e($dari) ?>"
            >
        </label>

        <label>
            Sampai

            <input
                type="date"
                name="sampai"
                value="<?= e($sampai) ?>"
            >
        </label>

        <label>
            Jenis

            <select name="tipe">

                <option value="">
                    Semua
                </option>

                <option
                    value="setor"
                    <?= $tipe === 'setor' ? 'selected' : '' ?>
                >
                    Setoran
                </option>

                <option
                    value="jual"
                    <?= $tipe === 'jual' ? 'selected' : '' ?>
                >
                    Penjualan
                </option>

            </select>
        </label>

        <label>
            Status

            <select name="status">

                <option value="">
                    Semua
                </option>

                <?php
                $statusOptions = [
                    'pending'   => 'Menunggu',
                    'disetujui' => 'Disetujui',
                    'ditolak'   => 'Ditolak',
                ];
                ?>

                <?php foreach ($statusOptions as $k => $v): ?>

                    <option
                        value="<?= e($k) ?>"
                        <?= $status === $k ? 'selected' : '' ?>
                    >
                        <?= e($v) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </label>

        <button type="submit" class="btn btn-red">
            Terapkan
        </button>

        <a href="laporan.php" class="btn btn-ghost">
            Atur ulang
        </a>

        <button
            type="button"
            class="btn btn-ghost"
            onclick="window.print()"
        >
            Cetak
        </button>

    </form>

</section>


<!-- ========================================
     RINGKASAN LAPORAN
======================================== -->

<div class="stats">

    <div class="stat">

        <span>Setoran disetujui</span>

        <strong>
            <?= kg($tot['setor'][0]) ?>
        </strong>

        <small>
            <?= rp($tot['setor'][1]) ?> masuk ke saldo
        </small>

    </div>

    <div class="stat">

        <span>Penjualan disetujui</span>

        <strong>
            <?= kg($tot['jual'][0]) ?>
        </strong>

        <small>
            <?= rp($tot['jual'][1]) ?> dari pengepul
        </small>

    </div>

    <div class="stat">

        <span>Jumlah transaksi</span>

        <strong>
            <?= count($rows) ?>
        </strong>

        <small>
            sesuai filter
        </small>

    </div>

</div>


<!-- ========================================
     TABEL RINCIAN TRANSAKSI
======================================== -->

<section class="card">

    <h2>Rincian</h2>

    <?php if (!$rows): ?>

        <div class="empty">
            Tidak ada transaksi pada filter ini.
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

                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <?= tgl($r['tanggal']) ?>
                        </td>

                        <td>
                            <?= e($r['nama']) ?>

                            <small class="muted">
                                (<?= roleName($r['role']) ?>)
                            </small>
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