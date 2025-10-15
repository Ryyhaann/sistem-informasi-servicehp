<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$error = '';

$pelanggan = $pdo->query('SELECT * FROM pelanggan ORDER BY nama')->fetchAll();

$teknisi = $pdo->query('SELECT * FROM teknisi ORDER BY nama')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pelanggan_id = $_POST['pelanggan_id'];
    $teknisi_id = $_POST['teknisi_id'] ?: null;
    $merk_hp = trim($_POST['merk_hp']);
    $kerusakan = trim($_POST['kerusakan']);
    $estimasi_biaya = $_POST['estimasi_biaya'];
    
    if (empty($pelanggan_id) || empty($merk_hp) || empty($kerusakan) || empty($estimasi_biaya)) {
        $error = 'Semua field harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO service (pelanggan_id, teknisi_id, merk_hp, kerusakan, estimasi_biaya, tanggal_masuk, status) VALUES (?, ?, ?, ?, ?, CURDATE(), "masuk")');
            $stmt->execute([$pelanggan_id, $teknisi_id, $merk_hp, $kerusakan, $estimasi_biaya]);
            
            $message = '✅ Service berhasil diterima!';
            $_POST = array();
            
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan Service - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 950px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            padding: 25px 35px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
        }

        .header p {
            margin: 3px 0 0;
            color: #666;
        }

        .header-actions a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            margin-left: 8px;
        }

        .btn {
            cursor: pointer;
            transition: 0.3s;
            border: none;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.85;
        }

        .content .card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            color: #007bff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #888;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px 12px 10px 38px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }

        textarea {
            resize: vertical;
        }

        .btn-lg {
            padding: 12px 18px;
            font-size: 1rem;
            border-radius: 10px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeInDown 0.6s ease;
            font-weight: 500;
            position: relative;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
        }

        .alert-info {
            background: #cfe2ff;
            color: #084298;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-float {
            position: fixed;
            top: 20px;
            right: 25px;
            z-index: 1000;
            width: 300px;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .container { padding: 20px; }
        }
    </style>
</head>
<body>
    <?php if ($message): ?>
        <div class="alert alert-success alert-float">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.alert-float').style.opacity = '0';
                document.querySelector('.alert-float').style.transition = 'opacity 0.5s';
            }, 3000);
        </script>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error alert-float">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.alert-float').style.opacity = '0';
                document.querySelector('.alert-float').style.transition = 'opacity 0.5s';
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-clipboard-list"></i> Penerimaan Service</h1>
                <p>Terima dan catat service HP dari pelanggan</p>
            </div>
            <div class="header-actions">
                <a href="dashboard_admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Form Penerimaan Service</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="pelanggan_id">Pilih Pelanggan</label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <select id="pelanggan_id" name="pelanggan_id" required>
                                    <option value="">Pilih Pelanggan</option>
                                    <?php foreach ($pelanggan as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($_POST['pelanggan_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['kontak']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="teknisi_id">Pilih Teknisi (Opsional)</label>
                            <div class="input-icon">
                                <i class="fas fa-tools"></i>
                                <select id="teknisi_id" name="teknisi_id">
                                    <option value="">Pilih Teknisi</option>
                                    <?php foreach ($teknisi as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= ($_POST['teknisi_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="merk_hp">Merk HP</label>
                            <div class="input-icon">
                                <i class="fas fa-mobile-alt"></i>
                                <input type="text" id="merk_hp" name="merk_hp" value="<?= htmlspecialchars($_POST['merk_hp'] ?? '') ?>" placeholder="Contoh: Samsung Galaxy A10" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="kerusakan">Deskripsi Kerusakan</label>
                            <div class="input-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                                <textarea id="kerusakan" name="kerusakan" rows="4" placeholder="Jelaskan kerusakan yang dialami HP" required><?= htmlspecialchars($_POST['kerusakan'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="estimasi_biaya">Estimasi Biaya (Rp)</label>
                            <div class="input-icon">
                                <i class="fas fa-money-bill-wave"></i>
                                <input type="number" id="estimasi_biaya" name="estimasi_biaya" value="<?= htmlspecialchars($_POST['estimasi_biaya'] ?? '') ?>" placeholder="Contoh: 150000" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Terima Service
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Informasi</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h4><i class="fas fa-lightbulb"></i> Tips Penerimaan Service:</h4>
                        <ul>
                            <li>Pastikan data pelanggan sudah terdaftar sebelum menerima service</li>
                            <li>Catat kerusakan dengan detail untuk memudahkan teknisi</li>
                            <li>Estimasi biaya dapat diubah nanti oleh teknisi</li>
                            <li>Teknisi dapat dipilih langsung atau ditugaskan nanti</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
