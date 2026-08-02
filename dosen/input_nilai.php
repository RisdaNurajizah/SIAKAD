<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'dosen') {
    header("Location: ../login.php");
    exit;
}

$nip = $_SESSION['username'] ?? $_SESSION['nip'] ?? '';
$nama_dosen = $_SESSION['nama'] ?? 'Dosen';
$pesan = '';
$tipe_pesan = '';

// 1. Ambil daftar mata kuliah yang diampu oleh dosen ini
$stmt_mk = $koneksi->prepare("SELECT * FROM matakuliah WHERE nip_dosen = ? ORDER BY kode_mk ASC");
$stmt_mk->execute([$nip]);
$matkul_list = $stmt_mk->fetchAll(PDO::FETCH_ASSOC);

// Tangkap mata kuliah yang dipilih (jika ada)
$selected_mk = $_GET['kode_mk'] ?? ($matkul_list[0]['kode_mk'] ?? '');

// 2. Process SIMPAN / UPDATE NILAI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    $nilai_data = $_POST['nilai'] ?? []; // Array [nim => nilai]
    
    $updated = 0;
    $stmt_update = $koneksi->prepare("UPDATE krs SET nilai = ? WHERE nim = ? AND kode_mk = ?");
    
    foreach ($nilai_data as $nim => $nilai) {
        $nilai_clean = !empty($nilai) ? strtoupper(trim($nilai)) : NULL;
        if ($stmt_update->execute([$nilai_clean, $nim, $selected_mk])) {
            $updated++;
        }
    }
    
    $pesan = "Berhasil memperbarui nilai untuk $updated mahasiswa!";
    $tipe_pesan = "success";
}

// 3. Ambil daftar mahasiswa yang mengambil mata kuliah yang dipilih
$mahasiswa_list = [];
if (!empty($selected_mk)) {
    $stmt_mhs = $koneksi->prepare("
        SELECT k.nim, m.nama, k.nilai 
        FROM krs k
        JOIN mahasiswa m ON k.nim = m.nim
        WHERE k.kode_mk = ?
        ORDER BY m.nama ASC
    ");
    $stmt_mhs->execute([$selected_mk]);
    $mahasiswa_list = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai Mahasiswa - SIAKAD UNIBBA</title>
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
    <!-- Sidebar Dosen -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <i class="bi bi-person-workspace fs-4"></i>
                <span>SIAKAD DOSEN</span>
            </div>
            <div class="sidebar-menu">
                <small class="text-uppercase fw-bold px-3 fs-7 mb-2 d-block" style="font-size: 0.7rem; color: #a7f3d0;">Menu Utama</small>
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="input_nilai.php" class="nav-link active">
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
            <h5 class="fw-bold mb-0 text-dark">Input Nilai Mahasiswa</h5>
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
            <?php if (!empty($pesan)): ?>
                <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($pesan) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Pilih Mata Kuliah -->
            <div class="card-custom p-4 mb-4">
                <form method="GET" action="" class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <label class="form-label small text-muted fw-semibold">Pilih Mata Kuliah yang Diampu:</label>
                        <select name="kode_mk" class="form-select" onchange="this.form.submit()">
                            <?php if (count($matkul_list) == 0): ?>
                                <option value="">-- Tidak ada mata kuliah diampu --</option>
                            <?php else: ?>
                                <?php foreach ($matkul_list as $mk): ?>
                                    <option value="<?= htmlspecialchars($mk['kode_mk']) ?>" <?= $selected_mk === $mk['kode_mk'] ? 'selected' : '' ?>>
                                        [<?= htmlspecialchars($mk['kode_mk']) ?>] <?= htmlspecialchars($mk['nama_mk']) ?> (<?= $mk['sks'] ?> SKS)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Form Tabel Input Nilai -->
            <?php if (!empty($selected_mk)): ?>
                <div class="card-custom p-4">
                    <form method="POST" action="">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-people-fill text-success me-2"></i>Daftar Peserta Kelas
                            </h6>
                            <button type="submit" name="simpan_nilai" class="btn btn-primary btn-sm px-3 fw-semibold">
                                <i class="bi bi-floppy-fill me-1"></i> Simpan Semua Nilai
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>NIM</th>
                                        <th>Nama Mahasiswa</th>
                                        <th class="text-center" style="width: 200px;">Nilai Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($mahasiswa_list) > 0): ?>
                                        <?php $no = 1; foreach ($mahasiswa_list as $mhs): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td class="fw-semibold text-success"><?= htmlspecialchars($mhs['nim']) ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($mhs['nama']) ?></td>
                                            <td>
                                                <select name="nilai[<?= htmlspecialchars($mhs['nim']) ?>]" class="form-select form-select-sm text-center fw-bold">
                                                    <option value="" <?= empty($mhs['nilai']) ? 'selected' : '' ?>>-- Belum --</option>
                                                    <option value="A" <?= $mhs['nilai'] === 'A' ? 'selected' : '' ?>>A</option>
                                                    <option value="B" <?= $mhs['nilai'] === 'B' ? 'selected' : '' ?>>B</option>
                                                    <option value="C" <?= $mhs['nilai'] === 'C' ? 'selected' : '' ?>>C</option>
                                                    <option value="D" <?= $mhs['nilai'] === 'D' ? 'selected' : '' ?>>D</option>
                                                    <option value="E" <?= $mhs['nilai'] === 'E' ? 'selected' : '' ?>>E</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                Belum ada mahasiswa yang mengambil mata kuliah ini.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>