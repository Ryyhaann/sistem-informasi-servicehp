<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama = trim($_POST['nama']);
    $kontak = trim($_POST['kontak']);
    
    if (empty($username) || empty($password) || empty($nama) || empty($kontak)) {
        $error = 'Semua field harus diisi!';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO users (username, password, role, nama, kontak) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$username, $password, 'teknisi', $nama, $kontak]);
                $user_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO teknisi (user_id, nama, kontak) VALUES (?, ?, ?)');
                $stmt->execute([$user_id, $nama, $kontak]);
                $pdo->commit();

                $message = 'Akun teknisi berhasil ditambahkan!';
                $_POST = [];
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
<title>Tambah Akun Teknisi - Service HP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: #333;
    margin: 0;
    padding: 0;
    min-height: 100vh;
}

.container {
    max-width: 550px;
    margin: 80px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    overflow: hidden;
    animation: fadeIn 0.7s ease;
}

.header {
    background: #2a5298;
    color: white;
    text-align: center;
    padding: 25px 20px;
}

.header h1 {
    margin: 0;
    font-size: 22px;
}

.header p {
    margin: 6px 0 0;
    font-weight: 300;
    font-size: 14px;
    opacity: 0.9;
}

.content {
    padding: 25px;
}

.card-header {
    font-weight: 600;
    color: #2a5298;
    margin-bottom: 15px;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    font-weight: 500;
    color: #444;
    display: block;
    margin-bottom: 6px;
}

.input-icon {
    position: relative;
}

.input-icon i {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #777;
}

.input-icon input {
    width: 100%;
    padding: 12px 12px 12px 38px;
    border-radius: 8px;
    border: 1.5px solid #ccc;
    transition: all 0.3s;
    font-size: 14px;
}

.input-icon input:focus {
    border-color: #2a5298;
    box-shadow: 0 0 6px rgba(42,82,152,0.3);
    outline: none;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #2a5298;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s;
    text-decoration: none;
}

.btn:hover {
    background: #1e3c72;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #ccc;
    color: #333;
}

.btn-secondary:hover {
    background: #bbb;
}

.alert {
    position: fixed;
    top: 25px;
    right: 25px;
    padding: 14px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: slideIn 0.5s ease;
    z-index: 999;
}

.alert i {
    font-size: 18px;
}

.alert-success {
    background: #4CAF50;
    color: white;
}

.alert-error {
    background: #F44336;
    color: white;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>

<body>

<?php if ($message): ?>
    <div class="alert alert-success" id="notif">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error" id="notif">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-user-plus"></i> Tambah Akun Teknisi</h1>
        <p>Buat akun baru untuk teknisi servis HP</p>
    </div>

    <div class="content">
        <div class="card-header">
            <i class="fas fa-user-cog"></i> Form Tambah Teknisi
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <div class="input-icon">
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="kontak">Nomor Kontak</label>
                <div class="input-icon">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="kontak" name="kontak" value="<?= htmlspecialchars($_POST['kontak'] ?? '') ?>" required>
                </div>
            </div>

            <button type="submit" class="btn"><i class="fas fa-plus"></i> Tambah Akun</button>
        </form>

        <div style="margin-top: 25px;">
            <a href="manajemen_teknisi.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<script>

setTimeout(() => {
    const notif = document.getElementById('notif');
    if (notif) {
        notif.style.transition = 'all 0.5s ease';
        notif.style.opacity = '0';
        notif.style.transform = 'translateX(100%)';
        setTimeout(() => notif.remove(), 500);
    }
}, 3000);
</script>

</body>
</html>
