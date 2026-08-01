<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

// Ambil NIM dari session yang tersedia secara aman
$nim = $_SESSION['username'] ?? $_SESSION['nim'] ?? $_SESSION['user'] ?? '';

$data_mhs = null;
if (!empty($nim)) {
    $stmt = $koneksi->prepare("SELECT * FROM mahasiswa WHERE nim = ?");
    $stmt->execute([$nim]);
    $data_mhs = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Variabel penampung aman
$nama_mhs = $data_mhs['nama'] ?? $_SESSION['nama'] ?? 'Mahasiswa';
$nim_mhs  = $data_mhs['nim'] ?? $nim;
$jurusan  = $data_mhs['jurusan'] ?? 'Teknik Informatika';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Mahasiswa - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">SIAKAD - Mahasiswa</a>
            <div class="d-flex text-white align-items-center">
                <span class="me-3">Halo, <strong><?= htmlspecialchars($nama_mhs) ?></strong></span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm p-3">
                    <h5 class="fw-bold">Profil Mahasiswa</h5>
                    <hr>
                    <p class="mb-1"><strong>NIM:</strong> <?= htmlspecialchars($nim_mhs) ?></p>
                    <p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars($nama_mhs) ?></p>
                    <p class="mb-0"><strong>Jurusan:</strong> <?= htmlspecialchars($jurusan) ?></p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="p-4 bg-white rounded shadow-sm">
                    <h4 class="fw-bold mb-3">Selamat Datang di Portal Akademik</h4>
                    <p class="text-muted">Silakan pilih layanan akademik yang ingin kamu akses:</p>
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <a href="krs.php" class="btn btn-outline-primary w-100 py-3 fw-bold">
                                📝 Pengisian KRS
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="khs.php" class="btn btn-outline-success w-100 py-3 fw-bold">
                                📊 Lihat Nilai (KHS)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>