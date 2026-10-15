<?php
require_once __DIR__ . '/config.php';
$u = user();
if (!$u) { header('Location: ' . BASE . '/login.php'); exit; }
header('Location: ' . BASE . ($u['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php'));
