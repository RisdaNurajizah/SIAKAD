<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = '';

if (isset($_POST['tambah'])) {
    $nim      = trim($_POST['nim']);
    $nama     = trim($_POST['nama']);
    $password = trim($_POST['password']);
    $jurusan  = trim($_POST['jurusan']);

    try {
        $stmt = $koneksi->prepare("INSERT INTO mahasiswa (nim, nama, password, jurusan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nim, $nama, $password, $jurusan]);
        $pesan = "<div class='alert alert-success'>Mahasiswa berhasil ditambahkan!</div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-danger'>Gagal: " . $e->getMessage() . "</div>";
    }
}

if (isset($_GET['hapus'])) {
    $nim_hapus = $_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM mahasiswa WHERE nim = ?");
    $stmt->execute([$nim_hapus]);
    header("Location: mahasiswa.php");
    exit;
}

$mahasiswa = $koneksi->query("SELECT * FROM mahasiswa ORDER BY nim ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Mahasiswa - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">SIAKAD - Admin</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="mahasiswa.php">Mahasiswa</a>
                <a class="nav-link" href="dosen.php">Dosen</a>
                <a class="nav-link" href="matakuliah.php">Mata Kuliah</a>
                <a class="nav-link btn btn-outline-light btn-sm ms-2" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="fw-bold mb-3">Kelola Data Mahasiswa</h3>
        <?= $pesan ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm p-3">
                    <h5 class="fw-bold mb-3">Tambah Mahasiswa</h5>
                    <form action="" method="POST">
                        <div class="mb-2">
                            <label class="form-label small">NIM</label>
                            <input type="text" name="nim" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Jurusan</label>
                            <input type="text" name="jurusan" class="form-control" value="Teknik Informatika" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary w-100 btn-sm">Simpan Data</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm p-3">
                    <h5 class="fw-bold mb-3">Daftar Mahasiswa</h5>
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Jurusan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mahasiswa as $mhs): ?>
                            <tr>
                                <td><?= htmlspecialchars($mhs['nim']) ?></td>
                                <td><?= htmlspecialchars($mhs['nama']) ?></td>
                                <td><?= htmlspecialchars($mhs['jurusan']) ?></td>
                                <td>
                                    <a href="mahasiswa.php?hapus=<?= $mhs['nim'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>