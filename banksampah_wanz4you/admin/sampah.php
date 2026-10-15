
<?php

require_once __DIR__ . '/../config.php';

need('admin');

$pageTitle = 'Data Sampah';


/* ========================================
   PROSES TAMBAH, UBAH, DAN HAPUS SAMPAH
======================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';

    try {

        /* ========================================
           TAMBAH ATAU UBAH DATA SAMPAH
        ======================================== */

        if ($aksi === 'simpan') {

            $id = (int) ($_POST['id'] ?? 0);

            $jenis = trim(
                $_POST['jenis_sampah'] ?? ''
            );

            $harga = (int) (
                $_POST['harga_per_kg'] ?? -1
            );

            $stok = round(
                (float) str_replace(
                    ',',
                    '.',
                    $_POST['stok_kg'] ?? '0'
                ),
                2
            );

            // Validasi input
            if (
                $jenis === ''
                || $harga < 0
                || $stok < 0
            ) {
                throw new Exception(
                    'Isi jenis sampah, harga, dan stok dengan benar.'
                );
            }

            // Jika ID tersedia, perbarui data
            if ($id) {

                $stmt = $pdo->prepare("
                    UPDATE sampah
                    SET
                        jenis_sampah = ?,
                        harga_per_kg = ?,
                        stok_kg = ?
                    WHERE id_sampah = ?
                ");

                $stmt->execute([
                    $jenis,
                    $harga,
                    $stok,
                    $id
                ]);

                flash(
                    'ok',
                    'Data sampah diperbarui.'
                );

            } else {

                // Jika tidak ada ID, tambahkan data baru
                $stmt = $pdo->prepare("
                    INSERT INTO sampah (
                        jenis_sampah,
                        harga_per_kg,
                        stok_kg
                    )
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $jenis,
                    $harga,
                    $stok
                ]);

                flash(
                    'ok',
                    'Jenis sampah ditambahkan.'
                );
            }

        /* ========================================
           HAPUS DATA SAMPAH
        ======================================== */

        } elseif ($aksi === 'hapus') {

            $id = (int) ($_POST['id'] ?? 0);

            // Periksa apakah sampah sudah digunakan
            // dalam transaksi
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM transaksi
                WHERE id_sampah = ?
            ");

            $stmt->execute([$id]);

            if ($stmt->fetchColumn() > 0) {

                throw new Exception(
                    'Jenis sampah ini sudah dipakai di transaksi, '
                    . 'jadi tidak bisa dihapus.'
                );
            }

            // Hapus data sampah
            $stmt = $pdo->prepare("
                DELETE FROM sampah
                WHERE id_sampah = ?
            ");

            $stmt->execute([$id]);

            flash(
                'ok',
                'Jenis sampah dihapus.'
            );
        }

    } catch (Exception $ex) {

        flash(
            'err',
            $ex->getMessage()
        );
    }

    header('Location: sampah.php');
    exit;
}


/* ========================================
   AMBIL DATA UNTUK FORM EDIT
======================================== */

$edit = null;

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM sampah
        WHERE id_sampah = ?
    ");

    $stmt->execute([
        (int) $_GET['edit']
    ]);

    $edit = $stmt->fetch();
}


/* ========================================
   AMBIL SEMUA DATA SAMPAH
======================================== */

$rows = $pdo->query("
    SELECT *
    FROM sampah
    ORDER BY jenis_sampah
")->fetchAll();


/* ========================================
   TAMPILKAN HEADER
======================================== */

require __DIR__ . '/../includes/header.php';

?>


<!-- ========================================
     DAFTAR JENIS SAMPAH
======================================== -->

<div class="split">

    <section class="card">

        <h2>Daftar sampah</h2>

        <?php if (!$rows): ?>

            <div class="empty">
                Belum ada jenis sampah.
                Tambahkan lewat formulir di samping.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <tr>
                        <th>Jenis</th>
                        <th class="num">Harga per kg</th>
                        <th class="num">Stok</th>
                        <th></th>
                    </tr>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <td>
                                <?= e($r['jenis_sampah']) ?>
                            </td>

                            <td class="num">
                                <?= rp($r['harga_per_kg']) ?>
                            </td>

                            <td class="num">
                                <?= kg($r['stok_kg']) ?>
                            </td>

                            <td class="act">

                                <!-- Tombol ubah -->
                                <a
                                    class="btn btn-ghost btn-sm"
                                    href="?edit=<?= $r['id_sampah'] ?>"
                                >
                                    Ubah
                                </a>

                                <!-- Tombol hapus -->
                                <form
                                    method="post"
                                    class="inline"
                                >

                                    <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="aksi"
                                        value="hapus"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $r['id_sampah'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-ghost btn-sm"
                                        onclick="return confirm('Hapus jenis sampah ini?')"
                                    >
                                        Hapus
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
         FORM TAMBAH DAN UBAH SAMPAH
    ======================================== -->

    <section class="card">

        <h2>
            <?= $edit ? 'Ubah sampah' : 'Tambah sampah' ?>
        </h2>

        <form method="post" class="form">

            <?= csrf_field() ?>

            <input
                type="hidden"
                name="aksi"
                value="simpan"
            >

            <input
                type="hidden"
                name="id"
                value="<?= $edit['id_sampah'] ?? 0 ?>"
            >

            <!-- Jenis sampah -->
            <label>
                Jenis sampah

                <input
                    type="text"
                    name="jenis_sampah"
                    required
                    value="<?= e($edit['jenis_sampah'] ?? '') ?>"
                >
            </label>

            <!-- Harga per kilogram -->
            <label>
                Harga per kg (Rp)

                <input
                    type="number"
                    min="0"
                    name="harga_per_kg"
                    required
                    value="<?= e($edit['harga_per_kg'] ?? '') ?>"
                >
            </label>

            <!-- Stok sampah -->
            <label>
                Stok (kg)

                <input
                    type="text"
                    name="stok_kg"
                    inputmode="decimal"
                    required
                    value="<?= e($edit['stok_kg'] ?? '0') ?>"
                >
            </label>

            <!-- Tombol form -->
            <div class="row">

                <button
                    type="submit"
                    class="btn btn-red"
                >
                    Simpan
                </button>

                <?php if ($edit): ?>

                    <a
                        class="btn btn-ghost"
                        href="sampah.php"
                    >
                        Batal
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </section>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>