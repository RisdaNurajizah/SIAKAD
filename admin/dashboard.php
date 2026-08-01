<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">SIAKAD - Panel Admin</a>
            <div class="d-flex text-white align-items-center">
                <span class="me-3">Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="p-4 bg-white rounded shadow-sm">
            <h3 class="fw-bold">Selamat Datang, Admin!</h3>
            <p class="text-muted">Gunakan menu di bawah untuk mengelola data master akademis.</p>
            <hr>
            <div class="row text-center mt-4">
                <div class="col-md-4 mb-3">
                    <a href="mahasiswa.php" class="btn btn-primary w-100 py-3 fw-bold fs-5 shadow-sm">
                        Kelola Mahasiswa
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="dosen.php" class="btn btn-success w-100 py-3 fw-bold fs-5 shadow-sm">
                        Kelola Dosen
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="matakuliah.php" class="btn btn-warning text-dark w-100 py-3 fw-bold fs-5 shadow-sm">
                        Kelola Mata Kuliah
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>