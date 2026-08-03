<?php
session_start();
require_once 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = trim($_POST['role'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($role) || empty($username) || empty($password)) {
        $error = "Harap isi semua kolom login!";
    } else {
        if ($role === 'mahasiswa') {
            $stmt = $koneksi->prepare("SELECT * FROM mahasiswa WHERE nim = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "NIM '$username' tidak ditemukan di database!";
            } else {
                $pass_db = trim($user['password']);
                if ($password === $pass_db || md5($password) === $pass_db || password_verify($password, $pass_db)) {
                    $_SESSION['login'] = true;
                    $_SESSION['role'] = 'mahasiswa';
                    $_SESSION['username'] = $user['nim'];
                    $_SESSION['nim'] = $user['nim'];
                    $_SESSION['nama'] = $user['nama'];
                    
                    header("Location: mahasiswa/dashboard.php");
                    exit;
                } else {
                    $error = "Password Mahasiswa salah!";
                }
            }
        } 
        elseif ($role === 'admin') {
            $stmt = $koneksi->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && ($password === trim($user['password']) || md5($password) === trim($user['password']) || password_verify($password, trim($user['password'])))) {
                $_SESSION['login'] = true;
                $_SESSION['role'] = 'admin';
                $_SESSION['username'] = $user['username'];
                header("Location: admin/dashboard.php");
                exit;
            } else {
                $error = "Username atau Password Admin salah!";
            }
        }
        elseif ($role === 'dosen') {
            $stmt = $koneksi->prepare("SELECT * FROM dosen WHERE nip = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && ($password === trim($user['password']) || md5($password) === trim($user['password']) || password_verify($password, trim($user['password'])))) {
                $_SESSION['login'] = true;
                $_SESSION['role'] = 'dosen';
                $_SESSION['username'] = $user['nip'];
                header("Location: dosen/dashboard.php");
                exit;
            } else {
                $error = "NIP atau Password Dosen salah!";
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
    <title>Login - SIAKAD UNIBBA</title>
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
            --bg-gradient-1: #064e3b;
            --bg-gradient-2: #059669;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-1) 0%, var(--bg-gradient-2) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card-login {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            background: #ffffff;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: #ecfdf5;
            color: var(--primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
        }

        .btn-emerald {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .btn-emerald:hover {
            background-color: var(--primary-dark);
            color: #ffffff;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(5, 150, 105, 0.2);
        }
    </style>
</head>
<body>

<div class="card card-login p-4 p-sm-5">
    <div class="text-center">
        <div class="brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">SIAKAD UNIBBA</h4>
        <p class="text-muted small mb-4">Sistem Informasi Akademik</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center py-2 fs-6 mb-3 border-0 bg-danger bg-opacity-10 text-danger fw-medium rounded-3">
            <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Status Login / Role</label>
            <select name="role" class="form-select py-2" required>
                <option value="">-- Pilih Hak Akses --</option>
                <option value="mahasiswa">Mahasiswa</option>
                <option value="dosen">Dosen</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Username / NIP / NIM</label>
            <input type="text" name="username" class="form-control py-2" placeholder="Masukkan NIM / NIP / Username" required>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">Password</label>
            <input type="password" name="password" class="form-control py-2" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-emerald w-100 shadow-sm">Masuk Sistem</button>
    </form>
</div>

</body>
</html>