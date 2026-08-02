<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$nim = $_SESSION['username'] ?? $_SESSION['nim'] ?? $_SESSION['id'] ?? '';
$nama = $_SESSION['nama'] ?? 'Mahasiswa';
$pesan = '';
$tipe_pesan = '';

// Process Tambah Matkul
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_krs'])) {
    $kode_mk = trim($_POST['kode_mk'] ?? '');
    
    if (!empty($kode_mk)) {
        // Cek apakah sudah pernah diambil
        $cek = $koneksi->prepare("SELECT * FROM krs WHERE nim = ? AND kode_mk = ?");
        $cek->execute([$nim, $kode_mk]);
        if ($cek->rowCount() > 0) {
            $pesan = "Mata kuliah tersebut sudah ada di KRS kamu!";
            $tipe_pesan = "warning";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO krs (nim, kode_mk) VALUES (?, ?)");
            if ($stmt->execute([$nim, $kode_mk])) {
                $pesan = "Mata kuliah berhasil ditambahkan ke KRS!";
                $tipe_pesan = "success";
            }
        }
    }
}

// Process Hapus Matkul (Batal)
if (isset($_GET['hapus'])) {
    $kode_mk_hapus = $_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM krs WHERE nim = ? AND kode_mk = ?");
    if ($stmt->execute([$nim, $kode_mk_hapus])) {
        $pesan = "Mata kuliah berhasil dibatalkan!";
        $tipe_pesan = "info";
    }
}

// 1. Ambil Daftar Matkul Yang Belum Diambil (Lengkap dengan Nama & NIP Dosen)
$stmt_available = $koneksi->prepare("
    SELECT mk.*, d.nama AS nama_dosen, d.nip AS nip_dosen
    FROM matakuliah mk
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip
    WHERE mk.kode_mk NOT IN (SELECT kode_mk FROM krs WHERE nim = ?)
    ORDER BY mk.kode_mk ASC
");
$stmt_available->execute([$nim]);
$available_mk = $stmt_available->fetchAll(PDO::FETCH_ASSOC);

// 2. Ambil Matkul Yang Sudah Diambil (Lengkap dengan Nama & NIP Dosen)
$stmt_taken = $koneksi->prepare("
    SELECT k.kode_mk, mk.nama_mk, mk.sks, d.nip AS nip_dosen, d.nama AS nama_dosen
    FROM krs k
    JOIN matakuliah mk ON k.kode_mk = mk.kode_mk
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip
    WHERE k.nim = ?
    ORDER BY k.kode_mk ASC
");
$stmt_taken->execute([$nim]);
$taken_mk = $stmt_taken->fetchAll(PDO::FETCH_ASSOC);

// Total SKS yang diambil
$total_sks_diambil = array_sum(array_column($taken_mk, 'sks'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Rencana Studi - SIAKAD UNIBBA</title>
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

        .dosen-badge {
            background-color: #f1f5f9;
            color: #334155;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: 0.825rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
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
                <a href="krs.php" class="nav-link active">
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

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Kartu Rencana Studi (KRS)</h5>
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

        <div class="content-body">
            <?php if (!empty($pesan)): ?>
                <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars($pesan) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Form Tambah Mata Kuliah -->
                <div class="col-lg-5">
                    <div class="card-custom p-4">
                        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-plus-circle-fill text-success me-2"></i>Pilih Mata Kuliah</span>
                            <span class="badge bg-success bg-opacity-10 text-success fw-normal"><?= count($available_mk) ?> Tersedia</span>
                        </h6>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label small text-muted">Mata Kuliah & Dosen Pengampu</label>
                                <select name="kode_mk" class="form-select py-2" required>
                                    <option value="">-- Pilih Mata Kuliah --</option>
                                    <?php foreach ($available_mk as $mk): ?>
                                        <?php 
                                            $dosen_info = !empty($mk['nama_dosen']) 
                                                ? ' | Dosen: ' . $mk['nama_dosen'] 
                                                : ' | Dosen: Belum Diatur';
                                        ?>
                                        <option value="<?= htmlspecialchars($mk['kode_mk']) ?>">
                                            [<?= htmlspecialchars($mk['kode_mk']) ?>] <?= htmlspecialchars($mk['nama_mk']) ?> (<?= $mk['sks'] ?> SKS)<?= htmlspecialchars($dosen_info) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">
                                    *Pilih mata kuliah yang ingin diambil semester ini.
                                </div>
                            </div>
                            <button type="submit" name="tambah_krs" class="btn btn-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-plus-lg me-1"></i> Tambahkan ke KRS
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tabel Ringkasan Matkul Yang Diambil -->
                <div class="col-lg-7">
                    <div class="card-custom p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-journal-check text-success me-2"></i>Mata Kuliah Yang Diambil
                            </h6>
                            <span class="badge bg-primary text-white px-3 py-2 rounded-pill">
                                Total: <?= $total_sks_diambil ?> SKS
                            </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Mata Kuliah</th>
                                        <th class="text-center">SKS</th>
                                        <th>Dosen Pengampu</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($taken_mk) > 0): ?>
                                        <?php foreach ($taken_mk as $row): ?>
                                        <tr>
                                            <td class="fw-semibold text-success"><?= htmlspecialchars($row['kode_mk']) ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_mk']) ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['sks']) ?> SKS</span>
                                            </td>
                                            <td>
                                                <div class="dosen-badge">
                                                    <i class="bi bi-person-badge text-success"></i>
                                                    <div>
                                                        <strong class="d-block text-dark"><?= htmlspecialchars($row['nama_dosen'] ?? 'Belum Diatur') ?></strong>
                                                        <?php if (!empty($row['nip_dosen'])): ?>
                                                            <small class="text-muted" style="font-size: 0.7rem;">NIP: <?= htmlspecialchars($row['nip_dosen']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="?hapus=<?= urlencode($row['kode_mk']) ?>" 
                                                   class="btn btn-outline-danger btn-sm rounded-2"
                                                   onclick="return confirm('Yakin ingin membatalkan mata kuliah ini?')">
                                                    <i class="bi bi-trash me-1"></i> Batal
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                                Belum ada mata kuliah yang diambil.<br> Silakan pilih mata kuliah di sebelah kiri.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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