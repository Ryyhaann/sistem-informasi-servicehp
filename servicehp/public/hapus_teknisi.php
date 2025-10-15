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
    
    $stmt = $pdo->prepare('SELECT * FROM teknisi WHERE id = ?');
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();
        

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM service WHERE teknisi_id = ? AND status IN ("masuk", "proses")');
        $stmt->execute([$id]);
        $active_services = $stmt->fetchColumn();
        
        if ($active_services > 0) {
            $error = 'Tidak dapat menghapus teknisi yang memiliki service aktif!';
        } else {

            $stmt = $pdo->prepare('DELETE FROM teknisi WHERE id = ?');
            $stmt->execute([$id]);
            
            $pdo->commit();
            
            header('Location: manajemen_teknisi.php?message=deleted');
            exit;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Teknisi - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .content {
            text-align: center;
        }

        .warning-icon {
            font-size: 64px;
            color: #ef4444;
            margin-bottom: 20px;
        }

        .warning-text {
            color: #374151;
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .technician-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #ef4444;
        }

        .technician-info h3 {
            color: #374151;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .technician-info p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .btn {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-times"></i> Hapus Teknisi</h1>
            <p>Konfirmasi penghapusan data teknisi</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <div class="warning-text">
                Anda yakin ingin menghapus data teknisi ini?<br>
                <strong>Tindakan ini tidak dapat dibatalkan!</strong>
            </div>

            <div class="technician-info">
                <h3>Data Teknisi yang Akan Dihapus:</h3>
                <p><strong>Nama:</strong> <?= htmlspecialchars($teknisi['nama']) ?></p>
                <p><strong>Kontak:</strong> <?= htmlspecialchars($teknisi['kontak']) ?></p>
            </div>

            <form method="POST">
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Ya, Hapus Teknisi
                </button>
            </form>

            <a href="manajemen_teknisi.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </div>
</body>
</html>
