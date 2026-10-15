
<?php

/* 
 * Halaman persetujuan transaksi
 * Digunakan oleh setoran.php dan penjualan.php
 */

require_once __DIR__ . '/../config.php';

need('admin');

$self = basename($_SERVER['SCRIPT_NAME']);

$pageTitle = $tipe === 'setor'
    ? 'Persetujuan Setoran Sampah'
    : 'Persetujuan Penjualan ke Pengepul';


/* ========================================
   PROSES PERSETUJUAN TRANSAKSI
======================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) ($_POST['id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    try {

        $pdo->beginTransaction();

        // Ambil transaksi yang masih menunggu persetujuan
        $stmt = $pdo->prepare("
            SELECT *
            FROM transaksi
            WHERE id_transaksi = ?
              AND tipe = ?
              AND status = 'pending'
            FOR UPDATE
        ");

        $stmt->execute([$id, $tipe]);
        $t = $stmt->fetch();

        if (!$t) {
            throw new Exception(
                'Transaksi tidak ditemukan atau sudah diproses.'
            );
        }

        /* ========================================
           JIKA TRANSAKSI DISETUJUI
        ======================================== */

        if ($aksi === 'setuju') {

            // PROSES SETOR SAMPAH
            if ($tipe === 'setor') {

                // Tambahkan stok sampah
                $stmt = $pdo->prepare("
                    UPDATE sampah
                    SET stok_kg = stok_kg + ?
                    WHERE id_sampah = ?
                ");

                $stmt->execute([
                    $t['berat_kg'],
                    $t['id_sampah']
                ]);

                // Tambahkan saldo penyetor
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET saldo = saldo + ?
                    WHERE id_user = ?
                ");

                $stmt->execute([
                    $t['total_rp'],
                    $t['id_user']
                ]);

            } else {

                // PROSES PEMBELIAN OLEH PENGEPUL

                // Periksa stok sampah
                $stmt = $pdo->prepare("
                    SELECT stok_kg
                    FROM sampah
                    WHERE id_sampah = ?
                    FOR UPDATE
                ");

                $stmt->execute([$t['id_sampah']]);

                if (
                    (float) $stmt->fetchColumn()
                    < (float) $t['berat_kg']
                ) {
                    throw new Exception(
                        'Stok sampah tidak mencukupi untuk disetujui.'
                    );
                }

                // Periksa saldo pengepul
                $stmt = $pdo->prepare("
                    SELECT saldo
                    FROM users
                    WHERE id_user = ?
                    FOR UPDATE
                ");

                $stmt->execute([$t['id_user']]);

                if (
                    (int) $stmt->fetchColumn()
                    < (int) $t['total_rp']
                ) {
                    throw new Exception(
                        'Saldo pengepul tidak mencukupi. '
                        . 'Tambah saldo di Data Pengguna lebih dulu.'
                    );
                }

                // Kurangi stok sampah
                $stmt = $pdo->prepare("
                    UPDATE sampah
                    SET stok_kg = stok_kg - ?
                    WHERE id_sampah = ?
                ");

                $stmt->execute([
                    $t['berat_kg'],
                    $t['id_sampah']
                ]);

                // Kurangi saldo pengepul
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET saldo = saldo - ?
                    WHERE id_user = ?
                ");

                $stmt->execute([
                    $t['total_rp'],
                    $t['id_user']
                ]);
            }

            $new = 'disetujui';

        /* ========================================
           JIKA TRANSAKSI DITOLAK
        ======================================== */

        } elseif ($aksi === 'tolak') {

            $new = 'ditolak';

        } else {

            throw new Exception('Aksi tidak valid.');
        }

        // Perbarui status transaksi
        $stmt = $pdo->prepare("
            UPDATE transaksi
            SET status = ?
            WHERE id_transaksi = ?
        ");

        $stmt->execute([$new, $id]);

        // Simpan perubahan
        $pdo->commit();

        if ($new === 'disetujui') {

            flash(
                'ok',
                'Transaksi disetujui. Saldo dan stok sudah diperbarui.'
            );

        } else {

            flash('ok', 'Transaksi ditolak.');
        }

    } catch (Exception $ex) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash('err', $ex->getMessage());
    }

    header('Location: ' . $self);
    exit;
}


/* ========================================
   AMBIL DATA TRANSAKSI PENDING
======================================== */

$sql = "
    SELECT
        t.*,
        u.nama,
        u.role,
        s.jenis_sampah
    FROM transaksi t
    JOIN users u ON u.id_user = t.id_user
    JOIN sampah s ON s.id_sampah = t.id_sampah
    WHERE t.tipe = ?
      AND t.status = ?
    ORDER BY t.id_transaksi DESC
";

$pending = $pdo->prepare($sql);
$pending->execute([$tipe, 'pending']);
$pending = $pending->fetchAll();


/* ========================================
   AMBIL RIWAYAT TRANSAKSI
======================================== */

$sqlHist = str_replace(
    't.status = ?',
    't.status <> ?',
    $sql
) . ' LIMIT 30';

$hist = $pdo->prepare($sqlHist);
$hist->execute([$tipe, 'pending']);
$hist = $hist->fetchAll();


/* ========================================
   TAMPILKAN HEADER
======================================== */

require __DIR__ . '/../includes/header.php';

?>

<!-- ========================================
     DAFTAR PENGAJUAN MENUNGGU PERSETUJUAN
======================================== -->

<section class="card">

    <h2>
        Menunggu persetujuan
        <span class="pill"><?= count($pending) ?></span>
    </h2>

    <p class="muted">
        <?= $tipe === 'setor'
            ? 'Saat disetujui, stok sampah bertambah dan saldo penyetor bertambah.'
            : 'Saat disetujui, stok sampah berkurang dan saldo pengepul dipotong.'
        ?>
    </p>

    <?php if (!$pending): ?>

        <div class="empty">
            Tidak ada pengajuan yang menunggu.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <tr>
                    <th>Tanggal</th>
                    <th>
                        <?= $tipe === 'setor'
                            ? 'Penyetor'
                            : 'Pengepul'
                        ?>
                    </th>
                    <th>Sampah</th>
                    <th class="num">Berat</th>
                    <th class="num">Total</th>
                    <th></th>
                </tr>

                <?php foreach ($pending as $r): ?>

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
                            <?= e($r['jenis_sampah']) ?>
                        </td>

                        <td class="num">
                            <?= kg($r['berat_kg']) ?>
                        </td>

                        <td class="num">
                            <?= rp($r['total_rp']) ?>
                        </td>

                        <td class="act">

                            <form method="post">

                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $r['id_transaksi'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="aksi"
                                    value="setuju"
                                    class="btn btn-red btn-sm"
                                >
                                    Setujui
                                </button>

                                <button
                                    type="submit"
                                    name="aksi"
                                    value="tolak"
                                    class="btn btn-ghost btn-sm"
                                    onclick="return confirm('Tolak pengajuan ini?')"
                                >
                                    Tolak
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    <?php endif; ?>

</section>


<!-- ========================================
     RIWAYAT TRANSAKSI YANG SUDAH DIPROSES
======================================== -->

<section class="card">

    <h2>Sudah diproses</h2>

    <?php if (!$hist): ?>

        <div class="empty">
            Belum ada riwayat.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <tr>
                    <th>Tanggal</th>
                    <th>Pengguna</th>
                    <th>Sampah</th>
                    <th class="num">Berat</th>
                    <th class="num">Total</th>
                    <th>Status</th>
                </tr>

                <?php foreach ($hist as $r): ?>

                    <tr>

                        <td>
                            <?= tgl($r['tanggal']) ?>
                        </td>

                        <td>
                            <?= e($r['nama']) ?>
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