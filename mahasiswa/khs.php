<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$nim = $_SESSION['username'] ?? $_SESSION['nim'] ?? $_SESSION['id'] ?? '';

// 1. Ambil Data Mahasiswa
$stmt_mhs = $koneksi->prepare("SELECT * FROM mahasiswa WHERE nim = ?");
$stmt_mhs->execute([$nim]);
$mhs = $stmt_mhs->fetch(PDO::FETCH_ASSOC);

$nama_mhs = $mhs['nama'] ?? 'Mahasiswa';
$initial = strtoupper(substr($nama_mhs, 0, 1));

// 2. Ambil data KHS
$stmt = $koneksi->prepare("
    SELECT k.kode_mk, k.nilai, mk.nama_mk, mk.sks, d.nama AS nama_dosen
    FROM krs k
    JOIN matakuliah mk ON k.kode_mk = mk.kode_mk
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip
    WHERE k.nim = ?
");
$stmt->execute([$nim]);
$khs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Perhitungan IPK & SKS
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

function getBadgeClass($nilai) {
    switch (strtoupper(trim($nilai ?? ''))) {
        case 'A': return 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
        case 'B': return 'bg-info bg-opacity-10 text-info border border-info border-opacity-25';
        case 'C': return 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
        case 'D': 
        case 'E': return 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
        default: return 'bg-secondary bg-opacity-10 text-secondary border';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Hasil Studi (KHS) - SIAKAD UNIBBA</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --bg-body: #f8fafc;
            --sidebar-bg: #064e3b;
            --card-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #334155;
        }

        .wrapper { display: flex; min-height: 100vh; }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #fff;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sidebar-brand {
            padding: 1.5rem;
            font-size: 1.2rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #34d399;
        }

        .sidebar-menu { padding: 1rem 0.75rem; }

        .sidebar-menu .nav-link {
            color: #a7f3d0;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }

        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            color: #ffffff;
            background-color: var(--primary);
        }

        /* Content Area */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        .top-navbar { background: #fff; border-bottom: 1px solid var(--card-border); padding: 0.85rem 1.75rem; }
        .content-body { padding: 1.75rem; }

        .card-custom {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .table-custom th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 0.85rem 1rem;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <i class="bi bi-mortarboard-fill fs-4"></i>
                <span>SIAKAD UNIBBA</span>
            </div>
            <div class="sidebar-menu">
                <small class="text-uppercase fw-bold px-3 fs-7 mb-2 d-block" style="font-size: 0.7rem; color: #a7f3d0;">Menu Utama</small>
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="krs.php" class="nav-link">
                    <i class="bi bi-file-earmark-text-fill"></i> Rencana Studi (KRS)
                </a>
                <a href="khs.php" class="nav-link active">
                    <i class="bi bi-file-earmark-check-fill"></i> Hasil Studi (KHS)
                </a>
            </div>
        </div>
        <div class="p-3 border-top border-success border-opacity-25">
            <a href="../logout.php" class="btn btn-outline-danger w-100 btn-sm d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Portal Akademik</h5>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($nama_mhs) ?></div>
                    <small class="text-muted" style="font-size: 0.8rem;">NIM: <?= htmlspecialchars($nim) ?></small>
                </div>
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; background-color: var(--primary) !important;">
                    <?= $initial ?>
                </div>
            </div>
        </header>

        <div class="content-body">
            <!-- Header KHS & Tombol Cetak -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Kartu Hasil Studi (KHS)</h4>
                    <p class="text-muted small mb-0">Laporan perolehan nilai dan Indeks Prestasi Kumulatif (IPK)</p>
                </div>
                <a href="cetak_khs.php" target="_blank" class="btn btn-outline-success btn-sm fw-semibold d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-printer"></i> Cetak KHS Resmi (PDF)
                </a>
            </div>

            <!-- Tabel KHS -->
            <div class="card-custom p-4 mb-4">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th class="text-center">SKS</th>
                                <th>Dosen Pengampu</th>
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
                                    <td class="fw-semibold text-success"><?= htmlspecialchars($row['kode_mk']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_mk']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['sks']) ?> SKS</span>
                                    </td>
                                    <td class="small text-secondary"><?= htmlspecialchars($row['nama_dosen'] ?? 'Belum Diatur') ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['nilai'])): ?>
                                            <span class="badge <?= getBadgeClass($row['nilai']) ?> px-3 py-2 fw-bold fs-6">
                                                <?= htmlspecialchars($row['nilai']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">Belum Diisi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        <?= $bobot !== null ? number_format($bobot, 1) : '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada nilai atau mata kuliah yang diambil.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card Statistik Ringkasan -->
            <?php 
                $ipk = $total_sks > 0 ? number_format($total_bobot / $total_sks, 2) : '0.00';
            ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card-custom p-3 border-start border-success border-4 d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                            <i class="bi bi-journal-check fs-3"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block mb-1">Total SKS Diambil</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_sks ?> SKS</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom p-3 border-start border-success border-4 d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                            <i class="bi bi-trophy-fill fs-3"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block mb-1">IP Kumulatif (IPK)</span>
                            <h3 class="fw-bold mb-0 text-success"><?= $ipk ?></h3>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>