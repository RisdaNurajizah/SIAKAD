<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = '';
$tipe_pesan = '';

// Ambil Daftar Dosen untuk Dropdown
$stmt_dosen_opt = $koneksi->query("SELECT * FROM dosen ORDER BY nama ASC");
$dosen_options = $stmt_dosen_opt->fetchAll(PDO::FETCH_ASSOC);

// 1. Tambah Mata Kuliah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_matkul'])) {
    $kode_mk = trim($_POST['kode_mk'] ?? '');
    $nama_mk = trim($_POST['nama_mk'] ?? '');
    $sks = intval($_POST['sks'] ?? 3);
    $nip_dosen = trim($_POST['nip_dosen'] ?? '');
    $nip_dosen = !empty($nip_dosen) ? $nip_dosen : NULL;

    if (!empty($kode_mk) && !empty($nama_mk)) {
        $cek = $koneksi->prepare("SELECT * FROM matakuliah WHERE kode_mk = ?");
        $cek->execute([$kode_mk]);
        if ($cek->rowCount() > 0) {
            $pesan = "Kode Mata Kuliah $kode_mk sudah ada!";
            $tipe_pesan = "warning";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO matakuliah (kode_mk, nama_mk, sks, nip_dosen) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$kode_mk, $nama_mk, $sks, $nip_dosen])) {
                $pesan = "Mata kuliah berhasil ditambahkan!";
                $tipe_pesan = "success";
            }
        }
    }
}

// 2. Edit Mata Kuliah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_matkul'])) {
    $kode_mk = trim($_POST['kode_mk'] ?? '');
    $nama_mk = trim($_POST['nama_mk'] ?? '');
    $sks = intval($_POST['sks'] ?? 3);
    $nip_dosen = trim($_POST['nip_dosen'] ?? '');
    $nip_dosen = !empty($nip_dosen) ? $nip_dosen : NULL;

    if (!empty($kode_mk) && !empty($nama_mk)) {
        $stmt = $koneksi->prepare("UPDATE matakuliah SET nama_mk = ?, sks = ?, nip_dosen = ? WHERE kode_mk = ?");
        if ($stmt->execute([$nama_mk, $sks, $nip_dosen, $kode_mk])) {
            $pesan = "Data mata kuliah berhasil diperbarui!";
            $tipe_pesan = "success";
        }
    }
}

// 3. Hapus Mata Kuliah
if (isset($_GET['hapus'])) {
    $kode_hapus = $_GET['hapus'];
    // Hapus relasi KRS dulu
    $koneksi->prepare("DELETE FROM krs WHERE kode_mk = ?")->execute([$kode_hapus]);
    $stmt = $koneksi->prepare("DELETE FROM matakuliah WHERE kode_mk = ?");
    if ($stmt->execute([$kode_hapus])) {
        $pesan = "Mata kuliah berhasil dihapus!";
        $tipe_pesan = "info";
    }
}

// Ambil Seluruh Data Mata Kuliah
$stmt_mk = $koneksi->query("
    SELECT mk.*, d.nama AS nama_dosen 
    FROM matakuliah mk 
    LEFT JOIN dosen d ON mk.nip_dosen = d.nip 
    ORDER BY mk.kode_mk ASC
");
$matkul_list = $stmt_mk->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Kuliah - SIAKAD UNIBBA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
    <!-- Sidebar Admin -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill fs-4"></i>
                <span>SIAKAD ADMIN</span>
            </div>
            <div class="sidebar-menu">
                <small class="text-uppercase fw-bold px-3 fs-7 mb-2 d-block" style="font-size: 0.7rem; color: #a7f3d0;">Menu Utama</small>
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="mahasiswa.php" class="nav-link">
                    <i class="bi bi-people-fill"></i> Data Mahasiswa
                </a>
                <a href="dosen.php" class="nav-link">
                    <i class="bi bi-person-badge-fill"></i> Data Dosen
                </a>
                <a href="matakuliah.php" class="nav-link active">
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
            <h5 class="fw-bold mb-0 text-dark">Kelola Data Mata Kuliah</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px;">
                    A
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

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-bookmark-fill text-success me-2"></i>Daftar Mata Kuliah</h6>
                <button class="btn btn-primary btn-sm fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Matkul
                </button>
            </div>

            <div class="card-custom p-4">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Mata Kuliah</th>
                                <th class="text-center">SKS</th>
                                <th>Dosen Pengampu</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($matkul_list) > 0): ?>
                                <?php foreach ($matkul_list as $mk): ?>
                                <tr>
                                    <td class="fw-semibold text-success"><?= htmlspecialchars($mk['kode_mk']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($mk['nama_mk']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($mk['sks']) ?> SKS</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary small">
                                            <i class="bi bi-person-badge text-success me-1"></i>
                                            <?= htmlspecialchars($mk['nama_dosen'] ?? 'Belum Diatur') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEdit<?= htmlspecialchars($mk['kode_mk']) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="?hapus=<?= urlencode($mk['kode_mk']) ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus mata kuliah ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="modal fade" id="modalEdit<?= htmlspecialchars($mk['kode_mk']) ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Edit Mata Kuliah</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted">Kode MK</label>
                                                        <input type="text" name="kode_mk" class="form-control" value="<?= htmlspecialchars($mk['kode_mk']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted">Nama Mata Kuliah</label>
                                                        <input type="text" name="nama_mk" class="form-control" value="<?= htmlspecialchars($mk['nama_mk']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted">Jumlah SKS</label>
                                                        <input type="number" name="sks" class="form-control" value="<?= htmlspecialchars($mk['sks']) ?>" min="1" max="6" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted">Dosen Pengampu</label>
                                                        <select name="nip_dosen" class="form-select">
                                                            <option value="">-- Belum Diatur --</option>
                                                            <?php foreach ($dosen_options as $dsn): ?>
                                                                <option value="<?= htmlspecialchars($dsn['nip']) ?>" <?= $mk['nip_dosen'] === $dsn['nip'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($dsn['nama']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_matkul" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data mata kuliah.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Tambah Matkul -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Tambah Mata Kuliah Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Kode MK</label>
                        <input type="text" name="kode_mk" class="form-control" placeholder="Contoh: IF101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" class="form-control" placeholder="Contoh: Pemrograman Web" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Jumlah SKS</label>
                        <input type="number" name="sks" class="form-control" value="3" min="1" max="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Dosen Pengampu</label>
                        <select name="nip_dosen" class="form-select">
                            <option value="">-- Pilih Dosen Pengampu --</option>
                            <?php foreach ($dosen_options as $dsn): ?>
                                <option value="<?= htmlspecialchars($dsn['nip']) ?>">
                                    <?= htmlspecialchars($dsn['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_matkul" class="btn btn-primary btn-sm">Tambah Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>