<?php
session_start();

// Jika pengguna sudah login, langsung arahkan ke dashboard masing-masing
if (isset($_SESSION['login'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'dosen') {
        header("Location: dosen/dashboard.php");
        exit;
    }
}

// Jika belum login, arahkan ke halaman login.php
header("Location: login.php");
exit;