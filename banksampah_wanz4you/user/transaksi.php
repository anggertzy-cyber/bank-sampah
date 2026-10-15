
<?php

require_once __DIR__ . '/../config.php';

// Batasi akses untuk guru, siswa, dan pengepul
need(['guru', 'siswa', 'pengepul']);

$u = user();

// Tentukan jenis transaksi berdasarkan role
$jual = $u['role'] === 'pengepul';
$tipe = $jual ? 'jual' : 'setor';

$pageTitle = $jual ? 'Beli Sampah' : 'Setor Sampah';


/* =========================================
   PROSES PENGAJUAN TRANSAKSI
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idS = (int) ($_POST['id_sampah'] ?? 0);

    $berat = round(
        (float) str_replace(
            ',',
            '.',
            $_POST['berat'] ?? '0'
        ),
        2
    );

    // Ambil data jenis sampah
    $stmt = $pdo->prepare(
        'SELECT * FROM sampah WHERE id_sampah = ?'
    );

    $stmt->execute([$idS]);

    $sp = $stmt->fetch();

    // Validasi jenis sampah dan berat
    if (!$sp || $berat <= 0) {

        flash(
            'err',
            'Pilih jenis sampah dan isi berat dengan benar.'
        );

    } else {

        // Hitung total harga transaksi
        $total = (int) round(
            $berat * $sp['harga_per_kg']
        );

        // Periksa stok untuk transaksi pembelian
        if (
            $jual &&
            $berat > (float) $sp['stok_kg']
        ) {

            flash(
                'err',
                'Stok ' . $sp['jenis_sampah'] .
                ' hanya ' . kg($sp['stok_kg']) . '.'
            );

        // Periksa saldo pengepul
        } elseif (
            $jual &&
            $total > (int) $u['saldo']
        ) {

            flash(
                'err',
                'Saldo tidak cukup. Total ' .
                rp($total) .
                ', saldo Anda ' .
                rp($u['saldo']) . '.'
            );

        } else {

            // Simpan pengajuan dengan status pending
            $stmt = $pdo->prepare(
                "INSERT INTO transaksi
                (
                    id_user,
                    id_sampah,
                    tanggal,
                    tipe,
                    berat_kg,
                    total_rp,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );

            $stmt->execute([
                $u['id_user'],
                $idS,
                date('Y-m-d'),
                $tipe,
                $berat,
                $total
            ]);

            flash(
                'ok',
                'Pengajuan terkirim. ' .
                (
                    $jual
                    ? 'Stok dan saldo berubah'
                    : 'Saldo bertambah'
                ) .
                ' setelah admin menyetujui.'
            );

        }
    }

    // Kembali ke halaman transaksi
    header('Location: transaksi.php');
    exit;
}


/* =========================================
   AMBIL DATA JENIS SAMPAH
========================================= */

$sampah = $pdo->query(
    'SELECT * FROM sampah ORDER BY jenis_sampah'
)->fetchAll();


/* =========================================
   AMBIL RIWAYAT PENGAJUAN PENGGUNA
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
    LIMIT 8"
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
         FORM PENGAJUAN TRANSAKSI
    ================================== -->

    <section class="card">

        <h2>
            <?= $jual ? 'Ajukan pembelian' : 'Ajukan setoran' ?>
        </h2>

        <div class="note">
            <?= $jual
                ? 'Pembelian dipotong dari saldo dan mengurangi stok setelah admin menyetujui.'
                : 'Saldo Anda bertambah setelah admin memeriksa dan menyetujui setoran.'
            ?>
        </div>

        <?php if (!$sampah): ?>

            <div class="empty">
                Admin belum menambahkan jenis sampah.
            </div>

        <?php else: ?>

            <form method="post" class="form">

                <!-- Token keamanan CSRF -->
                <?= csrf_field() ?>

                <!-- Pilihan jenis sampah -->
                <label>
                    Jenis sampah

                    <select
                        name="id_sampah"
                        id="jenis"
                        required
                    >

                        <?php foreach ($sampah as $r): ?>

                            <option
                                value="<?= (int) $r['id_sampah'] ?>"
                                data-harga="<?= e($r['harga_per_kg']) ?>"
                                data-stok="<?= e($r['stok_kg']) ?>"
                            >
                                <?= e($r['jenis_sampah']) ?>
                                -
                                <?= rp($r['harga_per_kg']) ?>/kg
                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <!-- Input berat sampah -->
                <label>
                    Berat (kg)

                    <input
                        type="text"
                        name="berat"
                        id="berat"
                        inputmode="decimal"
                        placeholder="Contoh: 2,5"
                        required
                    >
                </label>

                <!-- Total harga otomatis -->
                <div class="total">

                    <span>Perkiraan total</span>

                    <strong id="total">
                        Rp 0
                    </strong>

                </div>

                <!-- Informasi stok untuk pengepul -->
                <?php if ($jual): ?>

                    <p
                        class="muted small"
                        id="stokInfo"
                    ></p>

                <?php endif; ?>

                <!-- Tombol kirim -->
                <button
                    type="submit"
                    class="btn btn-red btn-block"
                >
                    Kirim pengajuan
                </button>

            </form>

        <?php endif; ?>

    </section>


    <!-- =================================
         DAFTAR PENGAJUAN TERBARU
    ================================== -->

    <section class="card">

        <h2>Pengajuan saya</h2>

        <?php if (!$mine): ?>

            <div class="empty">
                Belum ada pengajuan.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Sampah</th>
                            <th class="num">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($mine as $r): ?>

                            <tr>

                                <td>
                                    <?= tgl($r['tanggal']) ?>
                                </td>

                                <td>
                                    <?= e($r['jenis_sampah']) ?>

                                    <small class="muted">
                                        <?= kg($r['berat_kg']) ?>
                                    </small>
                                </td>

                                <td class="num">
                                    <?= rp($r['total_rp']) ?>
                                </td>

                                <td>
                                    <?= badge($r['status']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <a
                class="link"
                href="riwayat.php"
            >
                Lihat semua riwayat
            </a>

        <?php endif; ?>

    </section>

</div>


<!-- =====================================
     JAVASCRIPT PERHITUNGAN OTOMATIS
====================================== -->

<script>
const jenis = document.getElementById('jenis');
const berat = document.getElementById('berat');
const total = document.getElementById('total');
const info = document.getElementById('stokInfo');

function hitung() {

    if (!jenis || !berat || !total) {
        return;
    }

    const pilihan = jenis.options[jenis.selectedIndex];

    if (!pilihan) {
        return;
    }

    const harga = parseFloat(pilihan.dataset.harga) || 0;

    const jumlahBerat =
        parseFloat(
            (berat.value || '0').replace(',', '.')
        ) || 0;

    // Hitung perkiraan harga
    const hasil = Math.round(jumlahBerat * harga);

    total.textContent =
        'Rp ' + hasil.toLocaleString('id-ID');

    // Tampilkan stok untuk pengepul
    if (info) {

        const stok = parseFloat(pilihan.dataset.stok) || 0;

        info.textContent =
            'Stok tersedia: ' +
            stok.toLocaleString('id-ID') +
            ' kg';

    }
}


// Perbarui total saat jenis atau berat berubah
if (jenis && berat) {

    jenis.addEventListener('change', hitung);

    berat.addEventListener('input', hitung);

    hitung();

}
</script>

<?php

/* =========================================
   TAMPILKAN FOOTER
========================================= */

require __DIR__ . '/../includes/footer.php';

?>