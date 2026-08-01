<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

// Ambil NIM dari session yang tersedia secara aman
$nim = $_SESSION['username'] ?? $_SESSION['nim'] ?? $_SESSION['id'] ?? '';
$pesan = '';

// 1. Proses Ambil Mata Kuliah
if (isset($_POST['ambil'])) {
    $kode_mk = $_POST['kode_mk'];
    try {
        $stmt = $koneksi->prepare("INSERT INTO krs (nim, kode_mk) VALUES (?, ?)");
        $stmt->execute([$nim, $kode_mk]);
        $pesan = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    Mata kuliah berhasil diambil!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                    Mata kuliah ini sudah pernah kamu ambil!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}

// 2. Proses Batal / Hapus Mata Kuliah
if (isset($_POST['batal'])) {
    $kode_mk = $_POST['kode_mk'];
    try {
        $stmt = $koneksi->prepare("DELETE FROM krs WHERE nim = ? AND kode_mk = ?");
        $stmt->execute([$nim, $kode_mk]);
        $pesan = "<div class='alert alert-info alert-dismissible fade show' role='alert'>
                    Mata kuliah berhasil dibatalkan.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    Gagal membatalkan mata kuliah.
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
    }
}

// Ambil Daftar Mata Kuliah Tersedia
$matakuliah = $koneksi->query("SELECT mk.*, d.nama AS nama_dosen FROM matakuliah mk LEFT JOIN dosen d ON mk.nip_dosen = d.nip")->fetchAll(PDO::FETCH_ASSOC);

// Ambil Mata Kuliah yang Sudah Diambil Mahasiswa Ini
$krs_diambil = $koneksi->prepare("
    SELECT mk.kode_mk, mk.nama_mk, mk.sks, d.nama AS nama_dosen 
    FROM krs k 
    JOIN matakuliah mk ON k.kode_mk = mk.kode_mk 
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip 
    WHERE k.nim = ?
");
$krs_diambil->execute([$nim]);
$daftar_krs = $krs_diambil->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rencana Studi (KRS) - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">SIAKAD - Mahasiswa</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="krs.php">KRS</a>
                <a class="nav-link" href="khs.php">KHS</a>
                <a class="nav-link btn btn-outline-light btn-sm ms-2" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="fw-bold mb-3">Kartu Rencana Studi (KRS)</h3>
        <?= $pesan ?>

        <div class="row">
            <!-- Form Pilih MK -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm p-3">
                    <h5 class="fw-bold mb-3">Pilih Mata Kuliah</h5>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small">Mata Kuliah Available</label>
                            <select name="kode_mk" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Matkul --</option>
                                <?php foreach ($matakuliah as $mk): ?>
                                    <option value="<?= htmlspecialchars($mk['kode_mk']) ?>">
                                        <?= htmlspecialchars($mk['kode_mk']) ?> - <?= htmlspecialchars($mk['nama_mk']) ?> (<?= htmlspecialchars($mk['sks']) ?> SKS)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="ambil" class="btn btn-primary w-100 btn-sm fw-bold">Tambahkan ke KRS</button>
                    </form>
                </div>
            </div>

            <!-- Tabel KRS Diambil -->
            <div class="col-md-8">
                <div class="card shadow-sm p-3">
                    <h5 class="fw-bold mb-3">Mata Kuliah yang Diambil</h5>
                    <table class="table table-striped align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Dosen</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daftar_krs) > 0): ?>
                                <?php foreach ($daftar_krs as $krs): ?>
                                <tr>
                                    <td><?= htmlspecialchars($krs['kode_mk']) ?></td>
                                    <td><?= htmlspecialchars($krs['nama_mk']) ?></td>
                                    <td><?= htmlspecialchars($krs['sks']) ?></td>
                                    <td><?= htmlspecialchars($krs['nama_dosen'] ?? 'Belum Diatur') ?></td>
                                    <td class="text-center">
                                        <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan mata kuliah ini?');">
                                            <input type="hidden" name="kode_mk" value="<?= htmlspecialchars($krs['kode_mk']) ?>">
                                            <button type="submit" name="batal" class="btn btn-outline-danger btn-sm">Batal</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada mata kuliah yang diambil.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>