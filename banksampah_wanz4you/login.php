
<?php

require_once __DIR__ . '/config.php';

// Redirect jika pengguna sudah login
if (user()) {
    header('Location: ' . BASE . '/index.php');
    exit;
}

$err = '';

// Proses login ketika formulir dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = md5($_POST['password'] ?? '');

    // Periksa username dan password di database
    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE username = ? AND password = ?'
    );

    $stmt->execute([$username, $password]);

    $r = $stmt->fetch();

    if ($r) {

        // Perbarui sesi setelah login berhasil
        session_regenerate_id(true);

        $_SESSION['uid'] = $r['id_user'];
        $_SESSION['csrf'] = bin2hex(random_bytes(16));

        // Masuk ke halaman utama
        header('Location: ' . BASE . '/index.php');
        exit;

    } else {

        // Pesan jika login gagal
        $err = 'Username atau password salah.';

    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Masuk - Bank Sampah</title>

    <link
        rel="stylesheet"
        href="<?= BASE ?>/assets/style.css"
    >
</head>

<body class="login-page">

    <div class="login">

        <!-- Bagian hero -->
        <section class="login-hero">

            <div class="hero-mark">
                Bank<br>
                Sampah
            </div>

            <p>
                SELAMAT DATANG DI BANK SAMPAH ANGGER
            </p>

            <div class="hero-stripes"></div>

        </section>

        <!-- Bagian formulir login -->
        <section class="login-form">

            <form method="post" autocomplete="off">

                <h1>Masuk</h1>

                <p class="muted">
                    UNTUK MEMULAI TRANSAKSI.
                </p>

                <!-- Tampilkan pesan kesalahan -->
                <?php if ($err): ?>
                    <div class="flash err">
                        <?= e($err) ?>
                    </div>
                <?php endif; ?>

                <!-- Token CSRF -->
                <?= csrf_field() ?>

                <!-- Input username -->
                <label>
                    Username

                    <input
                        type="text"
                        name="username"
                        required
                        autofocus
                    >
                </label>

                <!-- Input password -->
                <label>
                    Password

                    <input
                        type="password"
                        name="password"
                        required
                    >
                </label>

                <!-- Tombol login -->
                <button
                    type="submit"
                    class="btn btn-red btn-block"
                >
                    Masuk
                </button>

            </form>

        </section>

    </div>

</body>
</html>
```