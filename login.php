<?php
session_start();
require_once 'koneksi.php';

// Jika pengguna sudah login, langsung arahkan ke dashboard masing-masing
if (isset($_SESSION['login'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    elseif ($_SESSION['role'] == 'dosen') header("Location: dosen/dashboard.php");
    elseif ($_SESSION['role'] == 'mahasiswa') header("Location: mahasiswa/dashboard.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        if ($role == 'admin') {
            $stmt = $koneksi->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['login'] = true;
                $_SESSION['role']  = 'admin';
                $_SESSION['id']    = $user['id_admin'];
                $_SESSION['nama']  = $user['nama'];
                header("Location: admin/dashboard.php");
                exit;
            } else {
                $error = "Username atau Password Admin salah!";
            }
        } elseif ($role == 'dosen') {
            $stmt = $koneksi->prepare("SELECT * FROM dosen WHERE nip = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['login'] = true;
                $_SESSION['role']  = 'dosen';
                $_SESSION['id']    = $user['nip'];
                $_SESSION['nama']  = $user['nama'];
                header("Location: dosen/dashboard.php");
                exit;
            } else {
                $error = "NIP atau Password Dosen salah!";
            }
        } elseif ($role == 'mahasiswa') {
            $stmt = $koneksi->prepare("SELECT * FROM mahasiswa WHERE nim = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['login'] = true;
                $_SESSION['role']  = 'mahasiswa';
                $_SESSION['id']    = $user['nim'];
                $_SESSION['nama']  = $user['nama'];
                header("Location: mahasiswa/dashboard.php");
                exit;
            } else {
                $error = "NIM atau Password Mahasiswa salah!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIAKAD</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px; border-radius: 12px;">
        <div class="card-body">
            <h3 class="card-title text-center fw-bold text-primary mb-1">SIAKAD</h3>
            <p class="text-center text-muted small mb-4">Sistem Informasi Akademik</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger p-2 small text-center" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Status Login / Role</label>
                    <select name="role" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Hak Akses --</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username / NIP / NIM</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan ID Login" required autocomplete="off">
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary w-100 py-2 mt-2 fw-bold">Masuk Sistem</button>
            </form>
        </div>
    </div>

</body>
</html>