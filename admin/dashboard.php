<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';

// 1. Hitung Total Mahasiswa
$total_mhs = $koneksi->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn() ?: 0;

// 2. Hitung Total Dosen
$total_dosen = $koneksi->query("SELECT COUNT(*) FROM dosen")->fetchColumn() ?: 0;

// 3. Hitung Total Mata Kuliah
$total_mk = $koneksi->query("SELECT COUNT(*) FROM matakuliah")->fetchColumn() ?: 0;

// 4. Hitung Total Transaksi KRS
$total_krs = $koneksi->query("SELECT COUNT(*) FROM krs")->fetchColumn() ?: 0;

// 5. Ambil 5 Mata Kuliah Terbaru / Paling Banyak Diambil
$stmt_recent = $koneksi->query("
    SELECT mk.kode_mk, mk.nama_mk, mk.sks, d.nama AS nama_dosen,
           (SELECT COUNT(*) FROM krs WHERE kode_mk = mk.kode_mk) AS total_peserta
    FROM matakuliah mk
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip
    ORDER BY total_peserta DESC
    LIMIT 5
");
$popular_mk = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIAKAD UNIBBA</title>
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

        .btn-primary, .bg-primary {
            background-color: var(--primary) !important;
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

        .card-custom {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease-in-out;
        }

        .card-custom:hover {
            transform: translateY(-2px);
        }

        .table-custom th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 0.85rem 1rem;
        }

        .welcome-card {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar Admin -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill fs-4"></i>
                <span>SIAKAD ADMIN</span>
            </div>
            <div class="sidebar-menu">
                <small class="text-uppercase fw-bold px-3 fs-7 mb-2 d-block" style="font-size: 0.7rem; color: #a7f3d0;">Menu Utama</small>
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="mahasiswa.php" class="nav-link">
                    <i class="bi bi-people-fill"></i> Data Mahasiswa
                </a>
                <a href="dosen.php" class="nav-link">
                    <i class="bi bi-person-badge-fill"></i> Data Dosen
                </a>
                <a href="matakuliah.php" class="nav-link">
                    <i class="bi bi-journal-bookmark-fill"></i> Data Mata Kuliah
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
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Dashboard Administrator</h5>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($username) ?></div>
                    <small class="text-muted" style="font-size: 0.8rem;">Administrator</small>
                </div>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px;">
                    A
                </div>
            </div>
        </header>

        <div class="content-body">
            <!-- Banner Selamat Datang -->
            <div class="welcome-card mb-4 shadow-sm">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <h3 class="fw-bold mb-2">Selamat Datang, Administrator! ⚡</h3>
                        <p class="mb-0 text-white-50">
                            Kelola seluruh master data sivitas akademika seperti Mahasiswa, Dosen, dan Mata Kuliah secara efisien dari panel kontrol ini.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Statistik Utama -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <a href="mahasiswa.php" class="text-decoration-none">
                        <div class="card-custom p-3 border-start border-success border-4 d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block mb-1">Total Mahasiswa</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= $total_mhs ?></h3>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="dosen.php" class="text-decoration-none">
                        <div class="card-custom p-3 border-start border-success border-4 d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                                <i class="bi bi-person-badge-fill fs-3"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block mb-1">Total Dosen</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= $total_dosen ?></h3>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="matakuliah.php" class="text-decoration-none">
                        <div class="card-custom p-3 border-start border-success border-4 d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                                <i class="bi bi-journal-bookmark-fill fs-3"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block mb-1">Mata Kuliah</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= $total_mk ?></h3>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <div class="card-custom p-3 border-start border-emerald border-4 d-flex align-items-center gap-3" style="border-left-color: var(--primary) !important;">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                            <i class="bi bi-card-checklist fs-3"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block mb-1">KRS Terdaftar</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_krs ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Ringkasan Matkul Populer -->
            <div class="card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-star-fill text-warning me-2"></i>Mata Kuliah Paling Banyak Diambil
                    </h6>
                    <a href="matakuliah.php" class="btn btn-sm btn-outline-success">Lihat Semua MK</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode MK</th>
                                <th>Nama Mata Kuliah</th>
                                <th class="text-center">SKS</th>
                                <th>Dosen Pengampu</th>
                                <th class="text-center">Jumlah Peminat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($popular_mk) > 0): ?>
                                <?php foreach ($popular_mk as $mk): ?>
                                <tr>
                                    <td class="fw-semibold text-success"><?= htmlspecialchars($mk['kode_mk']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($mk['nama_mk']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($mk['sks']) ?> SKS</span>
                                    </td>
                                    <td>
                                        <span class="small text-secondary">
                                            <i class="bi bi-person-badge text-success me-1"></i>
                                            <?= htmlspecialchars($mk['nama_dosen'] ?? 'Belum Diatur') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 fw-semibold">
                                            <?= $mk['total_peserta'] ?> Mahasiswa
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data transaksi KRS.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>