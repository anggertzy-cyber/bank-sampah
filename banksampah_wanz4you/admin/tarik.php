
<?php

require_once __DIR__ . '/../config.php';

need('admin');

$pageTitle = 'Penarikan Saldo';


/* ========================================
   PROSES PERSETUJUAN PENARIKAN
======================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) ($_POST['id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    try {

        // Memulai transaksi database
        $pdo->beginTransaction();

        // Mengambil pengajuan yang masih pending
        $stmt = $pdo->prepare("
            SELECT *
            FROM tarik_saldo
            WHERE id_tarik = ?
              AND status = 'pending'
            FOR UPDATE
        ");

        $stmt->execute([$id]);

        $t = $stmt->fetch();

        if (!$t) {
            throw new Exception(
                'Pengajuan tidak ditemukan atau sudah diproses.'
            );
        }


        /* ========================================
           JIKA PENARIKAN DISETUJUI
        ======================================== */

        if ($aksi === 'setuju') {

            // Mengunci dan mengambil saldo pengguna
            $stmt = $pdo->prepare("
                SELECT saldo
                FROM users
                WHERE id_user = ?
                FOR UPDATE
            ");

            $stmt->execute([$t['id_user']]);

            $saldo = (int) $stmt->fetchColumn();

            // Memeriksa kecukupan saldo
            if ($saldo < (int) $t['nominal']) {
                throw new Exception(
                    'Saldo pengguna tidak mencukupi untuk penarikan ini.'
                );
            }

            // Memotong saldo pengguna
            $stmt = $pdo->prepare("
                UPDATE users
                SET saldo = saldo - ?
                WHERE id_user = ?
            ");

            $stmt->execute([
                $t['nominal'],
                $t['id_user']
            ]);

            $new = 'diterima';


        /* ========================================
           JIKA PENARIKAN DITOLAK
        ======================================== */

        } elseif ($aksi === 'tolak') {

            $new = 'ditolak';

        } else {

            throw new Exception('Aksi tidak valid.');
        }


        /* ========================================
           PERBARUI STATUS PENGAJUAN
        ======================================== */

        $stmt = $pdo->prepare("
            UPDATE tarik_saldo
            SET status = ?
            WHERE id_tarik = ?
        ");

        $stmt->execute([$new, $id]);

        // Simpan seluruh perubahan
        $pdo->commit();


        /* ========================================
           NOTIFIKASI HASIL
        ======================================== */

        if ($new === 'diterima') {

            flash(
                'ok',
                'Penarikan diterima. Saldo pengguna sudah dipotong.'
            );

        } else {

            flash(
                'ok',
                'Penarikan ditolak.'
            );
        }

    } catch (Exception $ex) {

        // Batalkan perubahan jika terjadi kesalahan
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            'err',
            $ex->getMessage()
        );
    }

    header('Location: tarik.php');
    exit;
}


/* ========================================
   AMBIL PENGAJUAN YANG MENUNGGU
======================================== */

$sql = "
    SELECT
        t.*,
        u.nama,
        u.role,
        u.saldo
    FROM tarik_saldo t
    JOIN users u
        ON u.id_user = t.id_user
    WHERE t.status = ?
    ORDER BY t.id_tarik DESC
";

$pending = $pdo->prepare($sql);
$pending->execute(['pending']);

$pending = $pending->fetchAll();


/* ========================================
   AMBIL RIWAYAT PENARIKAN
======================================== */

$sqlHist = "
    SELECT
        t.*,
        u.nama,
        u.role,
        u.saldo
    FROM tarik_saldo t
    JOIN users u
        ON u.id_user = t.id_user
    WHERE t.status <> ?
    ORDER BY t.id_tarik DESC
    LIMIT 30
";

$hist = $pdo->prepare($sqlHist);
$hist->execute(['pending']);

$hist = $hist->fetchAll();


/* ========================================
   TAMPILKAN HEADER
======================================== */

require __DIR__ . '/../includes/header.php';

?>


<!-- ========================================
     DAFTAR PENARIKAN MENUNGGU PERSETUJUAN
======================================== -->

<section class="card">

    <h2>
        Menunggu persetujuan

        <span class="pill">
            <?= count($pending) ?>
        </span>
    </h2>

    <p class="muted">
        Saldo pengguna baru dipotong saat penarikan diterima.
    </p>

    <?php if (!$pending): ?>

        <div class="empty">
            Tidak ada pengajuan penarikan.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <tr>
                    <th>Tanggal</th>
                    <th>Pengguna</th>
                    <th class="num">Nominal</th>
                    <th class="num">Saldo saat ini</th>
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

                        <td class="num">
                            <?= rp($r['nominal']) ?>
                        </td>

                        <td
                            class="num <?= $r['saldo'] < $r['nominal'] ? 'neg' : '' ?>"
                        >
                            <?= rp($r['saldo']) ?>
                        </td>

                        <td class="act">

                            <form method="post">

                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $r['id_tarik'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="aksi"
                                    value="setuju"
                                    class="btn btn-red btn-sm"
                                >
                                    Terima
                                </button>

                                <button
                                    type="submit"
                                    name="aksi"
                                    value="tolak"
                                    class="btn btn-ghost btn-sm"
                                    onclick="return confirm('Tolak penarikan ini?')"
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
     RIWAYAT PENARIKAN YANG SUDAH DIPROSES
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
                    <th class="num">Nominal</th>
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

                        <td class="num">
                            <?= rp($r['nominal']) ?>
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