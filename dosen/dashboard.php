<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'dosen') {
    header("Location: ../login.php");
    exit;
}

$nip = $_SESSION['username'] ?? $_SESSION['nip'] ?? '';
$nama_dosen = $_SESSION['nama'] ?? 'Dosen';

// 1. Total Mata Kuliah yang diampu
$stmt_mk = $koneksi->prepare("SELECT COUNT(*) FROM matakuliah WHERE nip_dosen = ?");
$stmt_mk->execute([$nip]);
$total_mk = $stmt_mk->fetchColumn() ?: 0;

// 2. Total Mahasiswa Bimbingan / Peserta Kelas
$stmt_mhs = $koneksi->prepare("
    SELECT COUNT(DISTINCT k.nim) 
    FROM krs k 
    JOIN matakuliah mk ON k.kode_mk = mk.kode_mk 
    WHERE mk.nip_dosen = ?
");
$stmt_mhs->execute([$nip]);
$total_mhs = $stmt_mhs->fetchColumn() ?: 0;

// 3. Daftar Mata Kuliah yang Diampu
$stmt_list_mk = $koneksi->prepare("
    SELECT mk.kode_mk, mk.nama_mk, mk.sks, 
           (SELECT COUNT(*) FROM krs WHERE kode_mk = mk.kode_mk) AS total_peserta
    FROM matakuliah mk
    WHERE mk.nip_dosen = ?
    ORDER BY mk.kode_mk ASC
");
$stmt_list_mk->execute([$nip]);
$matkul_diampu = $stmt_list_mk->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - SIAKAD UNIBBA</title>
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
    <!-- Sidebar Dosen -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <i class="bi bi-person-workspace fs-4"></i>
                <span>SIAKAD DOSEN</span>
            </div>
            <div class="sidebar-menu">
                <small class="text-uppercase fw-bold px-3 fs-7 mb-2 d-block" style="font-size: 0.7rem; color: #a7f3d0;">Menu Utama</small>
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="input_nilai.php" class="nav-link">
                    <i class="bi bi-pencil-square"></i> Input Nilai
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
            <h5 class="fw-bold mb-0 text-dark">Dashboard Dosen</h5>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($nama_dosen) ?></div>
                    <small class="text-muted" style="font-size: 0.8rem;">NIP: <?= htmlspecialchars($nip) ?></small>
                </div>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px;">
                    <?= strtoupper(substr($nama_dosen, 0, 1)) ?>
                </div>
            </div>
        </header>

        <div class="content-body">
            <!-- Banner Selamat Datang -->
            <div class="welcome-card mb-4 shadow-sm">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-2">Selamat Datang, <?= htmlspecialchars($nama_dosen) ?>! 👋</h3>
                        <p class="mb-0 text-white-50">
                            Kelola nilai mahasiswa dan pantau mata kuliah yang Anda ampu dengan mudah melalui portal ini.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="input_nilai.php" class="btn btn-light text-success fw-bold px-4 py-2 rounded-pill shadow-sm">
                            <i class="bi bi-pencil-square me-1"></i> Input Nilai
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Statistik -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card-custom p-3 border-start border-success border-4 d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                            <i class="bi bi-journal-bookmark-fill fs-3"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block mb-1">Mata Kuliah Diampu</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_mk ?> Matkul</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-custom p-3 border-start border-emerald border-4 d-flex align-items-center gap-3" style="border-left-color: var(--primary) !important;">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                            <i class="bi bi-people-fill fs-3"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block mb-1">Total Mahasiswa Ajar</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_mhs ?> Mahasiswa</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Mata Kuliah yang Diampu -->
            <div class="card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-book-half text-success me-2"></i>Daftar Mata Kuliah yang Diampu
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th class="text-center">SKS</th>
                                <th class="text-center">Jumlah Mahasiswa</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($matkul_diampu) > 0): ?>
                                <?php foreach ($matkul_diampu as $row): ?>
                                <tr>
                                    <td class="fw-semibold text-success"><?= htmlspecialchars($row['kode_mk']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_mk']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['sks']) ?> SKS</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 fw-semibold">
                                            <i class="bi bi-person me-1"></i><?= $row['total_peserta'] ?> Mahasiswa
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="input_nilai.php?kode_mk=<?= urlencode($row['kode_mk']) ?>" class="btn btn-outline-success btn-sm rounded-2">
                                            <i class="bi bi-pencil me-1"></i> Input Nilai
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Belum ada mata kuliah yang dialokasikan untuk Anda.
                                    </td>
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