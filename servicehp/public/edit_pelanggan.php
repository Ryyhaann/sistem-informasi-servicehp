<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$error = '';
$pelanggan = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare('SELECT p.*, u.username, u.password FROM pelanggan p JOIN users u ON p.user_id = u.id WHERE p.id = ?');
    $stmt->execute([$id]);
    $pelanggan = $stmt->fetch();
    if (!$pelanggan) {
        header('Location: manajemen_pelanggan.php');
        exit;
    }
} else {
    header('Location: manajemen_pelanggan.php');
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
        $stmt->execute([$username, $pelanggan['user_id']]);
        if ($stmt->rowCount() > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            try {
                $pdo->beginTransaction();
                if (!empty($password)) {
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, nama = ?, kontak = ? WHERE id = ?');
                    $stmt->execute([$username, $password, $nama, $kontak, $pelanggan['user_id']]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, nama = ?, kontak = ? WHERE id = ?');
                    $stmt->execute([$username, $nama, $kontak, $pelanggan['user_id']]);
                }
                $stmt = $pdo->prepare('UPDATE pelanggan SET nama = ?, kontak = ? WHERE id = ?');
                $stmt->execute([$nama, $kontak, $id]);
                $pdo->commit();
                $message = 'Data pelanggan berhasil diperbarui!';
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
    <title>Edit Pelanggan - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #eef2f3, #dfe9f3);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            margin: 60px auto;
            border-radius: 16px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            text-align: center;
            margin-bottom: 8px;
        }

        .header p {
            text-align: center;
            color: #6b7280;
            margin-bottom: 25px;
        }

        form .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        input {
            width: 100%;
            padding: 10px 12px 10px 38px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            font-size: 15px;
            transition: all 0.25s ease;
        }

        input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 4px 10px rgba(37,99,235,0.3);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #22c55e;
            color: white;
            padding: 14px 22px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(34,197,94,0.3);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease forwards;
            z-index: 9999;
        }

        .notification.error {
            background: #ef4444;
            box-shadow: 0 4px 15px rgba(239,68,68,0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-15px); }
        }
    </style>
</head>
<body>

<?php if ($message): ?>
<div class="notification" id="notif">
    <i class="fas fa-check-circle"></i><?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="notification error" id="notif">
    <i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-user-edit"></i> Edit Data Pelanggan</h1>
        <p>Perbarui informasi pelanggan dengan benar</p>
    </div>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="username" value="<?= htmlspecialchars($pelanggan['username']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Password Baru (opsional)</label>
            <div class="input-icon">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah">
            </div>
        </div>

        <div class="form-group">
            <label>Nama Lengkap</label>
            <div class="input-icon">
                <i class="fas fa-id-card"></i>
                <input type="text" name="nama" value="<?= htmlspecialchars($pelanggan['nama']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Nomor Kontak</label>
            <div class="input-icon">
                <i class="fas fa-phone"></i>
                <input type="text" name="kontak" value="<?= htmlspecialchars($pelanggan['kontak']) ?>" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>

        <a href="manajemen_pelanggan.php" class="btn btn-secondary" style="margin-left:10px;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </form>
</div>

<script>
    const notif = document.getElementById('notif');
    if (notif) {
        setTimeout(() => {
            notif.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => notif.remove(), 500);
        }, 3000);
    }
</script>
</body>
</html>
