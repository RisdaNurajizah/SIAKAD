<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$nim = $_SESSION['username'] ?? $_SESSION['nim'] ?? $_SESSION['id'] ?? '';

// Ambil data KHS
$stmt = $koneksi->prepare("
    SELECT k.kode_mk, k.nilai, mk.nama_mk, mk.sks, d.nama AS nama_dosen
    FROM krs k
    JOIN matakuliah mk ON k.kode_mk = mk.kode_mk
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip
    WHERE k.nim = ?
");
$stmt->execute([$nim]);
$khs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Perhitungan IPK
$total_sks = 0;
$total_bobot = 0;

function getBobot($nilai) {
    switch (strtoupper(trim($nilai ?? ''))) {
        case 'A': return 4.0;
        case 'B': return 3.0;
        case 'C': return 2.0;
        case 'D': return 1.0;
        case 'E': return 0.0;
        default: return null;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Hasil Studi (KHS) - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">SIAKAD - Mahasiswa</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="krs.php">KRS</a>
                <a class="nav-link active" href="khs.php">KHS</a>
                <a class="nav-link btn btn-outline-light btn-sm ms-2" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold m-0">Kartu Hasil Studi (KHS)</h3>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">🖨️ Cetak KHS</button>
        </div>

        <div class="card shadow-sm p-3 mb-4">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Dosen</th>
                        <th class="text-center">Nilai</th>
                        <th class="text-center">Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($khs_list) > 0): ?>
                        <?php foreach ($khs_list as $row): 
                            $bobot = getBobot($row['nilai']);
                            if ($bobot !== null) {
                                $total_sks += $row['sks'];
                                $total_bobot += ($bobot * $row['sks']);
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['kode_mk']) ?></td>
                            <td><?= htmlspecialchars($row['nama_mk']) ?></td>
                            <td><?= htmlspecialchars($row['sks']) ?></td>
                            <td><?= htmlspecialchars($row['nama_dosen'] ?? 'Belum Diatur') ?></td>
                            <td class="text-center fw-bold">
                                <?= !empty($row['nilai']) ? htmlspecialchars($row['nilai']) : '<span class="badge bg-secondary">Belum Diisi</span>' ?>
                            </td>
                            <td class="text-center">
                                <?= $bobot !== null ? number_format($bobot, 1) : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada nilai atau mata kuliah yang diambil.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Ringkasan IPK -->
        <?php 
            $ipk = $total_sks > 0 ? number_format($total_bobot / $total_sks, 2) : '0.00';
        ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm p-3">
                    <h6 class="mb-1 text-uppercase">Total SKS Diambil</h6>
                    <h2 class="fw-bold mb-0"><?= $total_sks ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm p-3">
                    <h6 class="mb-1 text-uppercase">IP Kumulatif (IPK)</h6>
                    <h2 class="fw-bold mb-0"><?= $ipk ?></h2>
                </div>
            </div>
        </div>
    </div>
</body>
</html>