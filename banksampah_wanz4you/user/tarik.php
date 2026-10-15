
<?php

require_once __DIR__ . '/../config.php';

// Batasi akses untuk guru, siswa, dan pengepul
need(['guru', 'siswa', 'pengepul']);

$u = user();

$pageTitle = 'Tarik Saldo';


/* =========================================
   HITUNG SALDO YANG SEDANG DITAHAN
========================================= */

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(nominal), 0)
     FROM tarik_saldo
     WHERE id_user = ?
       AND status = 'pending'"
);

$stmt->execute([$u['id_user']]);

$ditahan = (int) $stmt->fetchColumn();

// Hitung saldo yang masih bisa ditarik
$tersedia = max(
    0,
    (int) $u['saldo'] - $ditahan
);


/* =========================================
   PROSES PENGAJUAN PENARIKAN
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nominal = (int) ($_POST['nominal'] ?? 0);

    // Validasi nominal minimum
    if ($nominal < 1000) {

        flash(
            'err',
            'Penarikan minimal Rp 1.000.'
        );

    // Periksa saldo yang tersedia
    } elseif ($nominal > $tersedia) {

        flash(
            'err',
            'Nominal melebihi saldo yang bisa ditarik (' .
            rp($tersedia) . ').'
        );

    } else {

        // Simpan pengajuan dengan status pending
        $stmt = $pdo->prepare(
            "INSERT INTO tarik_saldo
                (id_user, nominal, tanggal, status)
             VALUES (?, ?, ?, 'pending')"
        );

        $stmt->execute([
            $u['id_user'],
            $nominal,
            date('Y-m-d')
        ]);

        flash(
            'ok',
            'Pengajuan penarikan terkirim. ' .
            'Saldo dipotong setelah admin menerima.'
        );

    }

    // Kembali ke halaman tarik saldo
    header('Location: tarik.php');
    exit;
}


/* =========================================
   AMBIL RIWAYAT PENARIKAN PENGGUNA
========================================= */

$stmt = $pdo->prepare(
    'SELECT *
     FROM tarik_saldo
     WHERE id_user = ?
     ORDER BY id_tarik DESC
     LIMIT 15'
);

$stmt->execute([$u['id_user']]);

$mine = $stmt->fetchAll();


/* =========================================
   TAMPILKAN HEADER
========================================= */

require __DIR__ . '/../includes/header.php';

?>

<div class="split">

    <!-- =================================
         FORM PENARIKAN SALDO
    ================================== -->

    <section class="card">

        <h2>Ajukan penarikan</h2>

        <!-- Informasi saldo pengguna -->
        <div class="total">

            <span>Saldo Anda</span>

            <strong>
                <?= rp($u['saldo']) ?>
            </strong>

        </div>

        <!-- Informasi saldo yang sedang ditahan -->
        <?php if ($ditahan > 0): ?>

            <p class="muted small">

                Sebesar <?= rp($ditahan) ?>
                sedang menunggu persetujuan,
                jadi yang bisa diajukan sekarang
                <?= rp($tersedia) ?>.

            </p>

        <?php endif; ?>

        <!-- Form pengajuan -->
        <form method="post" class="form">

            <!-- Token keamanan CSRF -->
            <?= csrf_field() ?>

            <label>
                Nominal (Rp)

                <input
                    type="number"
                    name="nominal"
                    min="1000"
                    max="<?= $tersedia ?>"
                    step="1"
                    required
                    placeholder="Minimal 1000"
                >
            </label>

            <!-- Tombol kirim pengajuan -->
            <button
                type="submit"
                class="btn btn-red btn-block"
                <?= $tersedia < 1000 ? 'disabled' : '' ?>
            >
                Kirim pengajuan
            </button>

        </form>

    </section>


    <!-- =================================
         RIWAYAT PENARIKAN
    ================================== -->

    <section class="card">

        <h2>Riwayat penarikan</h2>

        <?php if (!$mine): ?>

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

                        <?php foreach ($mine as $r): ?>

                            <tr>

                                <td>
                                    <?= tgl($r['tanggal']) ?>
                                </td>

                                <td class="num">
                                    <?= rp($r['nominal']) ?>
                                </td>

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