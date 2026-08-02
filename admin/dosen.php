<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = '';
$tipe_pesan = '';

// 1. Tambah Data Dosen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_dosen'])) {
    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $password = trim($_POST['password'] ?? '123456');

    if (!empty($nip) && !empty($nama)) {
        $cek = $koneksi->prepare("SELECT * FROM dosen WHERE nip = ?");
        $cek->execute([$nip]);
        if ($cek->rowCount() > 0) {
            $pesan = "NIP $nip sudah terdaftar!";
            $tipe_pesan = "warning";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO dosen (nip, nama, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$nip, $nama, md5($password)])) {
                $pesan = "Dosen berhasil ditambahkan!";
                $tipe_pesan = "success";
            }
        }
    }
}

// 2. Edit Data Dosen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dosen'])) {
    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');

    if (!empty($nip) && !empty($nama)) {
        $stmt = $koneksi->prepare("UPDATE dosen SET nama = ? WHERE nip = ?");
        if ($stmt->execute([$nama, $nip])) {
            $pesan = "Data dosen berhasil diperbarui!";
            $tipe_pesan = "success";
        }
    }
}

// 3. Hapus Data Dosen
if (isset($_GET['hapus'])) {
    $nip_hapus = $_GET['hapus'];
    // Unset nip_dosen di matakuliah agar tidak error relasi
    $koneksi->prepare("UPDATE matakuliah SET nip_dosen = NULL WHERE nip_dosen = ?")->execute([$nip_hapus]);
    $stmt = $koneksi->prepare("DELETE FROM dosen WHERE nip = ?");
    if ($stmt->execute([$nip_hapus])) {
        $pesan = "Dosen berhasil dihapus!";
        $tipe_pesan = "info";
    }
}

// Ambil Seluruh Data Dosen
$stmt_dosen = $koneksi->query("SELECT * FROM dosen ORDER BY nip ASC");
$dosen_list = $stmt_dosen->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dosen - SIAKAD UNIBBA</title>
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
                <a href="dosen.php" class="nav-link active">
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
            <h5 class="fw-bold mb-0 text-dark">Kelola Data Dosen</h5>
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
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-badge-fill text-success me-2"></i>Daftar Dosen Pengajar</h6>
                <button class="btn btn-primary btn-sm fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Dosen
                </button>
            </div>

            <div class="card-custom p-4">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>NIP</th>
                                <th>Nama Dosen & Gelar</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($dosen_list) > 0): ?>
                                <?php $no = 1; foreach ($dosen_list as $dsn): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-semibold text-success"><?= htmlspecialchars($dsn['nip']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($dsn['nama']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEdit<?= htmlspecialchars($dsn['nip']) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="?hapus=<?= urlencode($dsn['nip']) ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus dosen ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="modal fade" id="modalEdit<?= htmlspecialchars($dsn['nip']) ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Edit Data Dosen</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted">NIP</label>
                                                        <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($dsn['nip']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted">Nama & Gelar Dosen</label>
                                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($dsn['nama']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_dosen" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada data dosen.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Tambah Dosen -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Tambah Dosen Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">NIP</label>
                        <input type="text" name="nip" class="form-control" placeholder="Contoh: 198501012010121001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Nama Lengkap & Gelar</label>
                        <input type="text" name="nama" class="form-control" placeholder="Dr. Aris Kurniawan, M.T." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Password Default</label>
                        <input type="password" name="password" class="form-control" value="123456" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_dosen" class="btn btn-primary btn-sm">Tambah Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>