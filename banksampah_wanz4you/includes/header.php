
<?php

$u = user();
$role = $u['role'];
$cur = basename($_SERVER['SCRIPT_NAME']);

/**
 * Menghitung jumlah data.
 */
function cnt($q)
{
    global $pdo;

    return (int) $pdo->query($q)->fetchColumn();
}

/**
 * Menu berdasarkan role pengguna.
 */
if ($role === 'admin') {
    $menu = [
        [
            'admin/dashboard.php',
            'Dashboard',
            'home',
            0
        ],
        [
            'admin/setoran.php',
            'Setoran Sampah',
            'down',
            cnt("SELECT COUNT(*) FROM transaksi WHERE tipe='setor' AND status='pending'")
        ],
        [
            'admin/penjualan.php',
            'Penjualan Pengepul',
            'up',
            cnt("SELECT COUNT(*) FROM transaksi WHERE tipe='jual' AND status='pending'")
        ],
        [
            'admin/tarik.php',
            'Penarikan Saldo',
            'wallet',
            cnt("SELECT COUNT(*) FROM tarik_saldo WHERE status='pending'")
        ],
        [
            'admin/sampah.php',
            'Data Sampah',
            'box',
            0
        ],
        [
            'admin/users.php',
            'Data Pengguna',
            'users',
            0
        ],
        [
            'admin/laporan.php',
            'Laporan',
            'report',
            0
        ],
    ];
} else {
    $jualRole = $role === 'pengepul';

    $menu = [
        [
            'user/dashboard.php',
            'Dashboard',
            'home',
            0
        ],
        [
            'user/transaksi.php',
            $jualRole ? 'Beli Sampah' : 'Setor Sampah',
            $jualRole ? 'up' : 'down',
            0
        ],
        [
            'user/tarik.php',
            'Tarik Saldo',
            'wallet',
            0
        ],
        [
            'user/riwayat.php',
            'Riwayat',
            'history',
            0
        ],
    ];
}

?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= e($pageTitle ?? 'Bank Sampah') ?> - Bank Sampah
    </title>

    <link rel="stylesheet" href="<?= BASE ?>/assets/style.css">
</head>

<body>

    <div class="app">

        <!-- Sidebar -->
        <aside class="side" id="side">

            <div class="brand">
                Bank Sampah
            </div>

            <nav class="nav">
                <?php foreach ($menu as $m): ?>
                    <a
                        href="<?= BASE . '/' . $m[0] ?>"
                        class="<?= basename($m[0]) === $cur ? 'active' : '' ?>"
                    >
                        <?= icon($m[2]) ?>

                        <span><?= $m[1] ?></span>

                        <?php if ($m[3] > 0): ?>
                            <b class="count">
                                <?= $m[3] ?>
                            </b>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Informasi pengguna dan tombol keluar -->
            <div class="side-foot">

                <div class="who">
                    <strong><?= e($u['nama']) ?></strong>
                    <small><?= roleName($role) ?></small>
                </div>

                <a class="nav-out" href="<?= BASE ?>/logout.php">
                    <?= icon('logout') ?>
                    <span>Keluar</span>
                </a>

            </div>

        </aside>

        <!-- Konten utama -->
        <main class="main">

            <header class="top">

                <button
                    class="burger"
                    onclick="document.getElementById('side').classList.toggle('open')"
                    aria-label="Menu"
                >
                    <?= icon('menu') ?>
                </button>

                <h1><?= e($pageTitle ?? '') ?></h1>

            </header>

            <!-- Notifikasi -->
            <?php if (!empty($_SESSION['flash'])): ?>
                <?php
                [$ft, $fm] = $_SESSION['flash'];
                unset($_SESSION['flash']);
                ?>

                <div class="flash <?= $ft === 'ok' ? 'ok' : 'err' ?>">
                    <?= e($fm) ?>
                </div>
            <?php endif; ?>