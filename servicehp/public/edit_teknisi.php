<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$error = '';
$teknisi = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare('
        SELECT t.*, u.username, u.password 
        FROM teknisi t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ');
    $stmt->execute([$id]);
    $teknisi = $stmt->fetch();

    if (!$teknisi) {
        header('Location: manajemen_teknisi.php');
        exit;
    }
} else {
    header('Location: manajemen_teknisi.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama = trim($_POST['nama']);
    $kontak = trim($_POST['kontak']);

    if (empty($username) || empty($nama) || empty($kontak)) {
        $error = 'Username, nama, dan kontak harus diisi!';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $teknisi['user_id']]);
        if ($stmt->rowCount() > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            try {
                $pdo->beginTransaction();

                if (!empty($password)) {
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, nama = ?, kontak = ? WHERE id = ?');
                    $stmt->execute([$username, $password, $nama, $kontak, $teknisi['user_id']]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, nama = ?, kontak = ? WHERE id = ?');
                    $stmt->execute([$username, $nama, $kontak, $teknisi['user_id']]);
                }

                $stmt = $pdo->prepare('UPDATE teknisi SET nama = ?, kontak = ? WHERE id = ?');
                $stmt->execute([$nama, $kontak, $id]);

                $pdo->commit();
                $message = 'Data teknisi berhasil diperbarui!';

                $stmt = $pdo->prepare('
                    SELECT t.*, u.username, u.password 
                    FROM teknisi t 
                    JOIN users u ON t.user_id = u.id 
                    WHERE t.id = ?
                ');
                $stmt->execute([$id]);
                $teknisi = $stmt->fetch();

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
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
    <title>Edit Teknisi - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f9;
            margin: 0;
            color: #333;
        }

        .container {
            max-width: 700px;
            margin: 60px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 25px 35px;
        }

        .header h1 {
            font-size: 1.6rem;
            margin: 0;
        }

        .header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .content {
            padding: 35px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            background: #f9fafb;
            padding: 15px 25px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #1e293b;
        }

        .card-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 500;
            color: #374151;
            display: block;
            margin-bottom: 8px;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        input {
            width: 100%;
            padding: 12px 12px 12px 38px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 2px rgba(37,99,235,0.2);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn i { font-size: 0.9rem; }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1e40af;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Notifikasi */
        .alert {
            position: fixed;
            top: 20px;
            right: 25px;
            padding: 14px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            animation: slideIn 0.4s ease-out;
            z-index: 999;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .alert-error {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <script>
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.style.display = 'none';
            }, 3000);
        </script>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <script>
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.style.display = 'none';
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit Data Teknisi</h1>
            <p>Perbarui informasi teknisi dengan mudah dan cepat</p>
        </div>

        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-cog"></i> Form Edit Teknisi</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($teknisi['username']) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Masukkan password baru">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <div class="input-icon">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($teknisi['nama']) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="kontak">Nomor Kontak</label>
                            <div class="input-icon">
                                <i class="fas fa-phone"></i>
                                <input type="text" id="kontak" name="kontak" value="<?= htmlspecialchars($teknisi['kontak']) ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="manajemen_teknisi.php" class="btn btn-secondary" style="margin-left: 10px;">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
