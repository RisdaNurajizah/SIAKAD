<?php
session_start();
require_once '../koneksi.php';

// 1. Validasi Keamanan Akses (Harus Login sebagai Dosen)
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'dosen') {
    header("Location: ../login.php");
    exit;
}

// 2. Ambil NIP dari Session Login
$nip_dosen = $_SESSION['nip'] ?? $_SESSION['username'] ?? '';

// 3. Ambil Profile Dosen LANGSUNG dari Database (Agar Nama Selalu Akurat)
$stmt_dosen = $koneksi->prepare("SELECT * FROM dosen WHERE nip = ?");
$stmt_dosen->execute([$nip_dosen]);
$dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);

$nama_dosen  = !empty($dosen['nama']) ? $dosen['nama'] : 'Dosen';
$nip_display = !empty($dosen['nip']) ? $dosen['nip'] : $nip_dosen;

// 4. Ambil Daftar Mata Kuliah & Jumlah Mahasiswa (Menggunakan COUNT(k.nim) agar aman)
$stmt_mk = $koneksi->prepare("
    SELECT m.*, COUNT(k.nim) AS jumlah_mhs 
    FROM matakuliah m 
    LEFT JOIN krs k ON m.kode_mk = k.kode_mk 
    WHERE m.nip_dosen = ? 
    GROUP BY m.kode_mk
");
$stmt_mk->execute([$nip_dosen]);
$list_mk = $stmt_mk->fetchAll(PDO::FETCH_ASSOC);

$total_mk = count($list_mk);

// 5. Hitung Total Mahasiswa Unik yang Diajar
$stmt_mhs_count = $koneksi->prepare("
    SELECT COUNT(DISTINCT k.nim) AS total_mhs 
    FROM krs k 
    JOIN matakuliah m ON k.kode_mk = m.kode_mk 
    WHERE m.nip_dosen = ?
");
$stmt_mhs_count->execute([$nip_dosen]);
$data_mhs_count = $stmt_mhs_count->fetch(PDO::FETCH_ASSOC);
$total_mhs = $data_mhs_count['total_mhs'] ?? 0;
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
            --sidebar-width: 250px;
            --primary-green: #059669;
            --primary-dark: #064e3b;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--primary-dark);
            color: #ffffff;
            padding: 1.5rem 1rem;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            color: #ffffff;
            background-color: var(--primary-green);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        .banner-welcome {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
        }

        .avatar-circle {
            width: 44px;
            height: 44px;
            background-color: var(--primary-green);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .card-stat {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s;
        }

        .card-stat:hover {
            transform: translateY(-3px);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .table-custom {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body>

<!-- Sidebar Navigasi -->
<div class="sidebar d-flex flex-column justify-content-between">
    <div>
        <div class="d-flex align-items-center mb-4 px-2">
            <i class="bi bi-person-badge-fill fs-3 text-warning me-2"></i>
            <h5 class="fw-bold mb-0 text-white">SIAKAD DOSEN</h5>
        </div>
        <small class="text-uppercase text-muted fw-bold fs-7 mb-2 d-block px-2">Menu Utama</small>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-fill me-2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="input_nilai.php" class="nav-link"><i class="bi bi-pencil-square me-2"></i> Input Nilai</a>
            </li>
        </ul>
    </div>
    <div>
        <a href="../logout.php" class="nav-link text-danger fw-semibold px-2">
            <i class="bi bi-box-arrow-right me-2"></i> Keluar
        </a>
    </div>
</div>

<!-- Area Utama -->
<div class="main-content">
    
    <!-- Profil Header Kanan Atas -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark mb-0">Dashboard Dosen</h4>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-bold text-dark"><?= htmlspecialchars($nama_dosen) ?></div>
                <div class="text-muted small">NIP: <?= htmlspecialchars($nip_display) ?></div>
            </div>
            <div class="avatar-circle">
                <?= strtoupper(substr($nama_dosen, 0, 1)) ?>
            </div>
        </div>
    </div>

    <!-- Banner Menyapa -->
    <div class="banner-welcome mb-4 d-flex justify-content-between align-items-center shadow-sm">
        <div>
            <h2 class="fw-bold mb-2">Selamat Datang, <?= htmlspecialchars($nama_dosen) ?>! 👋</h2>
            <p class="mb-0 opacity-90">Kelola nilai mahasiswa dan pantau mata kuliah yang Anda ampu dengan mudah melalui portal ini.</p>
        </div>
        <a href="input_nilai.php" class="btn btn-light text-success fw-bold px-4 py-2 rounded-3 shadow-sm">
            <i class="bi bi-pencil-square me-1"></i> Input Nilai
        </a>
    </div>

    <!-- Kartu Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card card-stat p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Mata Kuliah Diampu</div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $total_mk ?> Matkul</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-stat p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Mahasiswa Ajar</div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $total_mhs ?> Mahasiswa</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Mata Kuliah yang Diampu -->
    <div class="card table-custom p-4 border-0">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-journal-text me-2 text-success"></i>Daftar Mata Kuliah yang Diampu</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>KODE</th>
                        <th>MATA KULIAH</th>
                        <th>SKS</th>
                        <th>JUMLAH MAHASISWA</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list_mk)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada mata kuliah yang diampu.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($list_mk as $mk): ?>
                            <tr>
                                <td class="fw-bold text-success"><?= htmlspecialchars($mk['kode_mk']) ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($mk['nama_mk']) ?></td>
                                <td><?= htmlspecialchars($mk['sks']) ?> SKS</td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                        <i class="bi bi-person me-1"></i><?= $mk['jumlah_mhs'] ?> Mahasiswa
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="input_nilai.php?kode_mk=<?= $mk['kode_mk'] ?>" class="btn btn-sm btn-outline-success rounded-3 px-3">
                                        <i class="bi bi-pencil-square me-1"></i> Input Nilai
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>