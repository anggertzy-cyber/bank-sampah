
<?php

session_start();

date_default_timezone_set('Asia/Jakarta');


/* =========================================
   PENGATURAN DATABASE
========================================= */

$DB_HOST = 'localhost';
$DB_NAME = 'banksampah_anggerr';
$DB_USER = 'root';
$DB_PASS = '';

try {

    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

} catch (PDOException $ex) {

    die(
        'Koneksi database gagal: ' .
        htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8')
    );

}


/* =========================================
   PENGATURAN BASE URL
========================================= */

$root = str_replace('\\', '/', realpath(__DIR__));
$doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));

define(
    'BASE',
    rtrim(substr($root, strlen($doc)), '/')
);


/* =========================================
   KEAMANAN CSRF
========================================= */

// Membuat token CSRF jika belum tersedia
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// Memeriksa token pada setiap permintaan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $token)) {

        http_response_code(400);

        die(
            'Sesi formulir tidak valid. ' .
            'Muat ulang halaman lalu coba lagi.'
        );

    }

}


// Membuat input token CSRF
function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' .
        e($_SESSION['csrf']) .
        '">';
}


/* =========================================
   HELPER TAMPILAN
========================================= */

// Mengamankan teks HTML
function e($s)
{
    return htmlspecialchars(
        (string) $s,
        ENT_QUOTES,
        'UTF-8'
    );
}


// Format mata uang Rupiah
function rp($n)
{
    return 'Rp ' . number_format(
        (float) $n,
        0,
        ',',
        '.'
    );
}


// Format berat sampah dalam kilogram
function kg($n)
{
    return rtrim(
        rtrim(
            number_format((float) $n, 2, ',', '.'),
            '0'
        ),
        ','
    ) . ' kg';
}


// Format tanggal
function tgl($d)
{
    return $d
        ? date('d/m/Y', strtotime($d))
        : '-';
}


// Menyimpan pesan sementara
function flash($type, $msg)
{
    $_SESSION['flash'] = [$type, $msg];
}


// Membuat badge status transaksi
function badge($s)
{
    $map = [
        'pending'   => 'Menunggu',
        'disetujui' => 'Disetujui',
        'diterima'  => 'Diterima',
        'ditolak'   => 'Ditolak',
    ];

    if (in_array($s, ['disetujui', 'diterima'], true)) {
        $cls = 'ok';
    } elseif ($s === 'ditolak') {
        $cls = 'no';
    } else {
        $cls = 'wait';
    }

    $label = $map[$s] ?? e($s);

    return '<span class="badge ' . $cls . '">' .
        $label .
        '</span>';
}


// Mengubah nama role menjadi label tampilan
function roleName($r)
{
    $roles = [
        'admin'    => 'Admin',
        'guru'     => 'Guru',
        'siswa'    => 'Siswa',
        'pengepul' => 'Pengepul',
    ];

    return $roles[$r] ?? $r;
}


/* =========================================
   HELPER IKON SVG
========================================= */

function icon($n)
{
    $p = [

        'home' =>
            '<path d="M3 11l9-8 9 8"/>' .
            '<path d="M5 10v10h14V10"/>',

        'down' =>
            '<path d="M12 3v12"/>' .
            '<path d="M7 10l5 5 5-5"/>' .
            '<path d="M4 21h16"/>',

        'up' =>
            '<path d="M12 21V9"/>' .
            '<path d="M7 14l5-5 5 5"/>' .
            '<path d="M4 3h16"/>',

        'wallet' =>
            '<rect x="3" y="6" width="18" height="14" rx="2"/>' .
            '<path d="M3 10h18"/>' .
            '<path d="M16 15h2"/>',

        'box' =>
            '<path d="M21 8l-9-5-9 5 9 5 9-5z"/>' .
            '<path d="M3 8v8l9 5 9-5V8"/>',

        'users' =>
            '<circle cx="9" cy="8" r="3.5"/>' .
            '<path d="M2 21c0-4 3-6 7-6s7 2 7 6"/>' .
            '<path d="M17 4a3.5 3.5 0 010 7"/>',

        'report' =>
            '<path d="M6 3h9l4 4v14H6z"/>' .
            '<path d="M9 12h7M9 16h7"/>',

        'history' =>
            '<circle cx="12" cy="12" r="9"/>' .
            '<path d="M12 7v5l3 2"/>',

        'logout' =>
            '<path d="M9 4H4v16h5"/>' .
            '<path d="M16 8l4 4-4 4"/>' .
            '<path d="M20 12H9"/>',

        'menu' =>
            '<path d="M4 7h16M4 12h16M4 17h16"/>',

    ];

    $path = $p[$n] ?? '';

    return
        '<svg class="ic" viewBox="0 0 24 24" ' .
        'fill="none" stroke="currentColor" ' .
        'stroke-width="1.8" stroke-linecap="round" ' .
        'stroke-linejoin="round">' .
        $path .
        '</svg>';
}


/* =========================================
   AUTENTIKASI PENGGUNA
========================================= */

// Mengambil data pengguna yang sedang login
function user()
{
    global $pdo;

    static $u = null;

    if (!isset($_SESSION['uid'])) {
        return null;
    }

    if ($u === null) {

        $stmt = $pdo->prepare(
            'SELECT * FROM users WHERE id_user = ?'
        );

        $stmt->execute([$_SESSION['uid']]);

        $u = $stmt->fetch() ?: null;

        // Hapus sesi jika akun tidak ditemukan
        if (!$u) {
            session_destroy();
        }

    }

    return $u;
}


/* =========================================
   PEMERIKSAAN AKSES HALAMAN
========================================= */

// Membatasi akses berdasarkan role pengguna
function need($roles)
{
    $u = user();

    // Jika belum login, arahkan ke halaman login
    if (!$u) {
        header('Location: ' . BASE . '/login.php');
        exit;
    }

    // Jika role tidak diizinkan, kembali ke halaman utama
    if (!in_array($u['role'], (array) $roles, true)) {
        header('Location: ' . BASE . '/index.php');
        exit;
    }
}