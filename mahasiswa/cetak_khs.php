<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$nim = $_SESSION['username'];

// 1. Ambil Data Mahasiswa
$stmt_mhs = $koneksi->prepare("SELECT * FROM mahasiswa WHERE nim = ?");
$stmt_mhs->execute([$nim]);
$mhs = $stmt_mhs->fetch(PDO::FETCH_ASSOC);

if (!$mhs) {
    die("Data mahasiswa tidak ditemukan.");
}

// 2. Ambil Nilai KHS
$stmt_khs = $koneksi->prepare("
    SELECT k.kode_mk, m.nama_mk, m.sks, k.nilai 
    FROM krs k 
    JOIN matakuliah m ON k.kode_mk = m.kode_mk 
    WHERE k.nim = ?
    ORDER BY k.kode_mk ASC
");
$stmt_khs->execute([$nim]);
$khs_list = $stmt_khs->fetchAll(PDO::FETCH_ASSOC);

// Konversi Nilai ke Bobot untuk perhitungan IPK
function konversiBobot($nilai) {
    switch ($nilai) {
        case 'A': return 4.0;
        case 'B': return 3.0;
        case 'C': return 2.0;
        case 'D': return 1.0;
        default: return 0.0;
    }
}

$total_sks = 0;
$total_bobot = 0;

foreach ($khs_list as $item) {
    if ($item['nilai'] !== 'Belum Diisi' && !empty($item['nilai'])) {
        $bobot = konversiBobot($item['nilai']);
        $total_sks += $item['sks'];
        $total_bobot += ($bobot * $item['sks']);
    }
}

$ipk = $total_sks > 0 ? number_format($total_bobot / $total_sks, 2) : "0.00";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak KHS - <?= htmlspecialchars($mhs['nama']) ?> (<?= htmlspecialchars($nim) ?>)</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background-color: #fff;
            color: #000;
        }

        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .table-khs th, .table-khs td {
            border: 1px solid #000 !important;
            padding: 6px 10px;
            font-size: 14px;
        }

        .no-print {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                size: A4 portrait;
                margin: 1.5cm;
            }
        }
    </style>
</head>
<body>

<!-- Panel Tombol Cetak (Sembunyi saat diprint) -->
<div class="no-print p-3 text-center mb-4">
    <div class="d-flex justify-content-center gap-2">
        <button onclick="window.print()" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-printer-fill me-2"></i> Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4">
            Tutup
        </button>
    </div>
</div>

<div class="container my-3" style="max-width: 800px;">
    
    <!-- Kop Surat Kampus -->
    <div class="kop-surat text-center position-relative">
        <h5 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;">UNIVERSITAS BALE BANDUNG (UNIBBA)</h5>
        <h6 class="fw-bold mb-1 text-uppercase">FAKULTAS TEKNOLOGI INFORMASI</h6>
        <small class="d-block" style="font-size: 11px;">Jl. Raya Baleendah No. 160, Kab. Bandung, Jawa Barat</small>
        <small class="d-block" style="font-size: 11px;">Website: www.unibba.ac.id | Email: info@unibba.ac.id</small>
    </div>

    <!-- Judul Dokumen -->
    <div class="text-center mb-4">
        <h5 class="fw-bold text-uppercase mb-1" style="text-decoration: underline;">KARTU HASIL STUDI (KHS)</h5>
        <small class="fw-bold">Semester Genap - Tahun Akademik 2025/2026</small>
    </div>

    <!-- Identitas Mahasiswa -->
    <div class="row mb-3" style="font-size: 14px;">
        <div class="col-6">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td style="width: 110px;"><strong>NIM</strong></td>
                    <td>: <?= htmlspecialchars($mhs['nim']) ?></td>
                </tr>
                <tr>
                    <td><strong>Nama</strong></td>
                    <td>: <?= htmlspecialchars($mhs['nama']) ?></td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td style="width: 110px;"><strong>Program Studi</strong></td>
                    <td>: Teknik Informatika</td>
                </tr>
                <tr>
                    <td><strong>Jenjang</strong></td>
                    <td>: S1 (Sarjana)</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Tabel Nilai -->
    <table class="table table-khs align-middle mb-4">
        <thead>
            <tr class="text-center bg-light">
                <th style="width: 50px;">No</th>
                <th style="width: 120px;">Kode MK</th>
                <th>Mata Kuliah</th>
                <th style="width: 70px;">SKS</th>
                <th style="width: 80px;">Nilai</th>
                <th style="width: 80px;">Bobot</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($khs_list) > 0): ?>
                <?php $no = 1; foreach ($khs_list as $item): ?>
                <?php 
                    $nilai = $item['nilai'] ?? 'Belum Diisi';
                    $bobot = ($nilai !== 'Belum Diisi' && !empty($nilai)) ? konversiBobot($nilai) : '-';
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['kode_mk']) ?></td>
                    <td><?= htmlspecialchars($item['nama_mk']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['sks']) ?></td>
                    <td class="text-center fw-bold"><?= htmlspecialchars($nilai) ?></td>
                    <td class="text-center"><?= $bobot !== '-' ? number_format($bobot, 1) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-3">Belum ada mata kuliah yang diambil.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ringkasan Nilai & IPK -->
    <div class="row mb-5" style="font-size: 14px;">
        <div class="col-7">
            <table class="table table-sm table-bordered">
                <tr>
                    <td><strong>Total SKS Diambil</strong></td>
                    <td class="text-center fw-bold" style="width: 100px;"><?= $total_sks ?> SKS</td>
                </tr>
                <tr>
                    <td><strong>Indeks Prestasi Kumulatif (IPK)</strong></td>
                    <td class="text-center fw-bold text-success" style="width: 100px; font-size: 16px;"><?= $ipk ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Tanda Tangan -->
    <div class="row text-center mt-4" style="font-size: 14px;">
        <div class="col-6">
            <p class="mb-5">Mengetahui,<br>Dosen Pembimbing Akademik</p>
            <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................... )</p>
            <small>NIP. ....................................</small>
        </div>
        <div class="col-6">
            <p class="mb-5">Baleendah, <?= date('d F Y') ?><br>Ketua Program Studi</p>
            <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................... )</p>
            <small>NIP. ....................................</small>
        </div>
    </div>

</div>

</body>
</html>