
<?php

require_once __DIR__ . '/../config.php';

need('admin');

$me = user();
$pageTitle = 'Data Pengguna';


/* PROSES FORM */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan') {
            $id       = (int) ($_POST['id'] ?? 0);
            $nama     = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role     = $_POST['role'] ?? '';

            $saldo = $role === 'admin'
                ? 0
                : max(0, (int) ($_POST['saldo'] ?? 0));

            if (
                $nama === '' ||
                $username === '' ||
                !in_array(
                    $role,
                    ['admin', 'guru', 'siswa', 'pengepul'],
                    true
                )
            ) {
                throw new Exception(
                    'Lengkapi nama, username, dan role.'
                );
            }

            if (!$id && $password === '') {
                throw new Exception(
                    'Password wajib diisi untuk pengguna baru.'
                );
            }

            /* UBAH PENGGUNA */
            if ($id) {
                if (
                    $id === (int) $me['id_user'] &&
                    $role !== 'admin'
                ) {
                    throw new Exception(
                        'Anda tidak bisa mengubah role akun Anda sendiri.'
                    );
                }

                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET nama = ?,
                             username = ?,
                             password = ?,
                             role = ?,
                             saldo = ?
                         WHERE id_user = ?'
                    );

                    $stmt->execute([
                        $nama,
                        $username,
                        md5($password),
                        $role,
                        $saldo,
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET nama = ?,
                             username = ?,
                             role = ?,
                             saldo = ?
                         WHERE id_user = ?'
                    );

                    $stmt->execute([
                        $nama,
                        $username,
                        $role,
                        $saldo,
                        $id
                    ]);
                }

                flash('ok', 'Pengguna diperbarui.');

            /* TAMBAH PENGGUNA */
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users
                        (nama, username, password, saldo, role)
                     VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $nama,
                    $username,
                    md5($password),
                    $saldo,
                    $role
                ]);

                flash('ok', 'Pengguna ditambahkan.');
            }

        /* HAPUS PENGGUNA */
        } elseif ($aksi === 'hapus') {
            $id = (int) ($_POST['id'] ?? 0);

            if ($id === (int) $me['id_user']) {
                throw new Exception(
                    'Anda tidak bisa menghapus akun sendiri.'
                );
            }

            $stmt = $pdo->prepare(
                'SELECT
                    (
                        SELECT COUNT(*)
                        FROM transaksi
                        WHERE id_user = ?
                    ) +
                    (
                        SELECT COUNT(*)
                        FROM tarik_saldo
                        WHERE id_user = ?
                    )'
            );

            $stmt->execute([$id, $id]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception(
                    'Pengguna ini punya riwayat transaksi, jadi tidak bisa dihapus.'
                );
            }

            $stmt = $pdo->prepare(
                'DELETE FROM users WHERE id_user = ?'
            );

            $stmt->execute([$id]);

            flash('ok', 'Pengguna dihapus.');
        }

    } catch (PDOException $ex) {
        if ($ex->getCode() == 23000) {
            flash(
                'err',
                'Username sudah dipakai pengguna lain.'
            );
        } else {
            flash(
                'err',
                'Gagal menyimpan: ' . $ex->getMessage()
            );
        }

    } catch (Exception $ex) {
        flash('err', $ex->getMessage());
    }

    header('Location: users.php');
    exit;
}


/* DATA PENGGUNA YANG AKAN DIEDIT */
$edit = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE id_user = ?'
    );

    $stmt->execute([(int) $_GET['edit']]);

    $edit = $stmt->fetch();
}


/* DAFTAR SEMUA PENGGUNA */
$rows = $pdo->query(
    "SELECT *
     FROM users
     ORDER BY FIELD(role, 'admin', 'guru', 'siswa', 'pengepul'),
              nama"
)->fetchAll();

$curRole = $edit['role'] ?? 'siswa';


/* HEADER */
require __DIR__ . '/../includes/header.php';

?>

<div class="split">

    <!-- DAFTAR PENGGUNA -->
    <section class="card">

        <h2>Daftar Pengguna</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th class="num">Saldo</th>
                    <th>Aksi</th>
                </tr>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <?= e($r['nama']) ?>
                        </td>

                        <td>
                            <?= e($r['username']) ?>
                        </td>

                        <td>
                            <span class="role role-<?= e($r['role']) ?>">
                                <?= roleName($r['role']) ?>
                            </span>
                        </td>

                        <td class="num">
                            <?php if ($r['role'] === 'admin'): ?>
                                <span class="muted">-</span>
                            <?php else: ?>
                                <?= rp($r['saldo']) ?>
                            <?php endif; ?>
                        </td>

                        <td class="act">

                            <a
                                class="btn btn-ghost btn-sm"
                                href="?edit=<?= (int) $r['id_user'] ?>"
                            >
                                Ubah
                            </a>

                            <?php if (
                                (int) $r['id_user'] !==
                                (int) $me['id_user']
                            ): ?>

                                <form
                                    method="post"
                                    class="inline"
                                    onsubmit="return confirm('Hapus pengguna ini?')"
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
                                        value="<?= (int) $r['id_user'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-ghost btn-sm"
                                    >
                                        Hapus
                                    </button>
                                </form>

                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endforeach; ?>

            </table>
        </div>

    </section>


    <!-- FORM TAMBAH / EDIT PENGGUNA -->
    <section class="card">

        <h2>
            <?= $edit ? 'Ubah Pengguna' : 'Tambah Pengguna' ?>
        </h2>

        <form
            method="post"
            class="form"
            autocomplete="off"
        >
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="aksi"
                value="simpan"
            >

            <input
                type="hidden"
                name="id"
                value="<?= (int) ($edit['id_user'] ?? 0) ?>"
            >

            <label>
                Nama
                <input
                    type="text"
                    name="nama"
                    required
                    value="<?= e($edit['nama'] ?? '') ?>"
                >
            </label>

            <label>
                Username
                <input
                    type="text"
                    name="username"
                    required
                    value="<?= e($edit['username'] ?? '') ?>"
                >
            </label>

            <label>
                Password
                <input
                    type="password"
                    name="password"
                    <?= $edit ? '' : 'required' ?>
                    placeholder="<?= $edit
                        ? 'Kosongkan jika tidak diganti'
                        : '' ?>"
                >
            </label>

            <label>
                Role

                <select name="role" id="role">
                    <?php foreach (
                        ['admin', 'guru', 'siswa', 'pengepul'] as $ro
                    ): ?>

                        <option
                            value="<?= e($ro) ?>"
                            <?= $curRole === $ro ? 'selected' : '' ?>
                        >
                            <?= roleName($ro) ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </label>

            <label id="saldoWrap">
                Saldo (Rp)

                <input
                    type="number"
                    min="0"
                    name="saldo"
                    value="<?= e($edit['saldo'] ?? 0) ?>"
                >
            </label>

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
                        href="users.php"
                    >
                        Batal
                    </a>
                <?php endif; ?>

            </div>

            <p class="muted small">
                Admin tidak memiliki saldo. Untuk pengepul,
                isi saldo di sini sebagai dana pembelian sampah.
            </p>

        </form>

    </section>

</div>


<!-- JAVASCRIPT UNTUK SALDO -->
<script>
    const roleSel = document.getElementById('role');
    const saldoWrap = document.getElementById('saldoWrap');

    function toggleSaldo() {
        saldoWrap.style.display =
            roleSel.value === 'admin' ? 'none' : '';
    }

    roleSel.addEventListener('change', toggleSaldo);

    toggleSaldo();
</script>


<?php require __DIR__ . '/../includes/footer.php'; ?>