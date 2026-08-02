<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$nim = $_SESSION['username'] ?? $_SESSION['nim'] ?? $_SESSION['id'] ?? '';
$nama = $_SESSION['nama'] ?? 'Mahasiswa';

// 1. Ambil Data Mahasiswa & Matkul yang Diambil (KRS)
$stmt_krs = $koneksi->prepare("
    SELECT k.kode_mk, k.nilai, mk.nama_mk, mk.sks, d.nama AS nama_dosen
    FROM krs k
    JOIN matakuliah mk ON k.kode_mk = mk.kode_mk
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip
    WHERE k.nim = ?
");
$stmt_krs->execute([$nim]);
$krs_items = $stmt_krs->fetchAll(PDO::FETCH_ASSOC);

// 2. Kalkulasi Ringkasan Data Akademik
$total_sks = 0;
$total_matkul = count($krs_items);
$total_bobot = 0;
$sks_bernilai = 0;

foreach ($krs_items as $item) {
    $total_sks += $item['sks'];
    $nilai = strtoupper(trim($item['nilai'] ?? ''));
    
    $bobot = match($nilai) {
        'A' => 4.0,
        'B' => 3.0,
        'C' => 2.0,
        'D' => 1.0,
        'E' => 0.0,
        default => null
    };

    if ($bobot !== null) {
        $total_bobot += ($bobot * $item['sks']);
        $sks_bernilai += $item['sks'];
    }
}

$ipk = $sks_bernilai > 0 ? number_format($total_bobot / $sks_bernilai, 2) : '0.00';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - SIAKAD UNIBBA</title>
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

        /* Override Bootstrap Primary Color dengan Tema Emerald */
        .btn-primary, .bg-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .btn-outline-primary {
            color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary) !important;
            color: #fff !important;
        }
        .text-primary {
            color: var(--primary) !important;
        }
        .border-primary {
            border-color: var(--primary) !important;
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

        /* Welcome Banner Card Emerald Gradient */
        .welcome-card {
            background: linear-gradient(135deg, #064e3b 0%, #059669 60%, #10b981 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.25);
        }

        /* Cards & Metric Badges */
        .card-custom {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.04);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .shortcut-card {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1.25rem;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #fff;
            transition: all 0.2s;
        }
        .shortcut-card:hover {
            border-color: var(--primary);
            background: #ecfdf5;
        }

        .table-custom th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.85rem 1rem;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- 1. Sidebar Navigasi -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <i class="bi bi-mortarboard-fill fs-4"></i>
                <span>SIAKAD UNIBBA</span>
            </div>
            <div class="sidebar-menu">
                <small class="text-uppercase fw-bold text-emerald-200 px-3 fs-7 mb-2 d-block" style="font-size: 0.7rem; letter-spacing: 1px; color: #a7f3d0;">Menu Utama</small>
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="krs.php" class="nav-link">
                    <i class="bi bi-file-earmark-text-fill"></i> Rencana Studi (KRS)
                </a>
                <a href="khs.php" class="nav-link">
                    <i class="bi bi-award-fill"></i> Hasil Studi (KHS)
                </a>
            </div>
        </div>
        <div class="p-3 border-top border-success border-opacity-25">
            <a href="../logout.php" class="btn btn-outline-danger w-100 btn-sm d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- 2. Konten Utama -->
    <main class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Portal Akademik</h5>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($nama) ?></div>
                    <small class="text-muted" style="font-size: 0.8rem;">NIM: <?= htmlspecialchars($nim) ?></small>
                </div>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px;">
                    <?= strtoupper(substr($nama, 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            
            <!-- Banner Selamat Datang Emerald -->
            <div class="welcome-card mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-white bg-opacity-20 text-white mb-2 px-3 py-1 rounded-pill fw-normal">
                        <i class="bi bi-calendar-check me-1"></i> Semester Ganjil 2025/2026
                    </span>
                    <h3 class="fw-bold mb-1">Selamat Datang Kembali, <?= htmlspecialchars($nama) ?>! 👋</h3>
                    <p class="mb-0 text-white-50">Program Studi Teknik Informatika — Universitas Bale Bandung</p>
                </div>
                <div class="d-none d-lg-block">
                    <i class="bi bi-journal-album display-3 text-white opacity-25"></i>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card-custom p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">IPK Kumulatif</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= $ipk ?></h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card-custom p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">SKS Semester Ini</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= $total_sks ?> SKS</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card-custom p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-book-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Mata Kuliah</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= $total_matkul ?> Matkul</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card-custom p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Status Mahasiswa</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success fw-semibold">Aktif</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Tabel Ringkasan Mata Kuliah Aktif -->
                <div class="col-lg-8">
                    <div class="card-custom p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-list-task text-success me-2"></i>Mata Kuliah Yang Diambil
                            </h6>
                            <a href="krs.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">Kelola KRS</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Mata Kuliah</th>
                                        <th class="text-center">SKS</th>
                                        <th>Dosen Pengampu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($krs_items) > 0): ?>
                                        <?php foreach ($krs_items as $item): ?>
                                        <tr>
                                            <td class="fw-semibold text-success"><?= htmlspecialchars($item['kode_mk']) ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['nama_mk']) ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['sks']) ?> SKS</span></td>
                                            <td><?= htmlspecialchars($item['nama_dosen'] ?? 'Belum Diatur') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                                Belum ada mata kuliah yang diambil semester ini.<br>
                                                <a href="krs.php" class="btn btn-primary btn-sm mt-2">Isi KRS Sekarang</a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Kanan: Akses Cepat & Pengumuman -->
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-3">
                        <!-- Akses Cepat -->
                        <div class="card-custom p-3">
                            <h6 class="fw-bold mb-3 text-dark">Akses Cepat</h6>
                            <div class="d-flex flex-column gap-2">
                                <a href="krs.php" class="shortcut-card">
                                    <div class="stat-icon bg-success text-white rounded-circle fs-5">
                                        <i class="bi bi-pencil-square"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Pengisian KRS</h6>
                                        <small class="text-muted">Pilih & atur rencana kuliah</small>
                                    </div>
                                </a>

                                <a href="khs.php" class="shortcut-card">
                                    <div class="stat-icon bg-success text-white rounded-circle fs-5">
                                        <i class="bi bi-award"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Lihat Nilai (KHS)</h6>
                                        <small class="text-muted">Cek nilai & transkrip semester</small>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Pengumuman Akademik -->
                        <div class="card-custom p-3">
                            <h6 class="fw-bold mb-3 text-dark">
                                <i class="bi bi-bell-fill text-warning me-2"></i>Pengumuman Akademik
                            </h6>
                            <div class="border-start border-success border-3 ps-3 mb-3">
                                <small class="text-muted d-block">10 Februari 2026</small>
                                <strong class="d-block text-dark small">Batas Akhir Pengisian KRS</strong>
                                <span class="text-muted extra-small" style="font-size:0.75rem;">Harap menyelesaikan pengisian KRS sebelum tenggat waktu.</span>
                            </div>
                            <div class="border-start border-warning border-3 ps-3">
                                <small class="text-muted d-block">15 Maret 2026</small>
                                <strong class="d-block text-dark small">Ujian Tengah Semester (UTS)</strong>
                                <span class="text-muted extra-small" style="font-size:0.75rem;">Jadwal UTS dapat diunduh pada portal fakultas.</span>
                            </div>
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