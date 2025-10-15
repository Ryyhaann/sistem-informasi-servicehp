<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deleted':
            $message = 'Data teknisi berhasil dihapus!';
            break;
    }
}

$teknisi = $pdo->query('
    SELECT t.*, COUNT(s.id) as jumlah_service 
    FROM teknisi t 
    LEFT JOIN service s ON t.id = s.teknisi_id 
    GROUP BY t.id 
    ORDER BY t.nama
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Teknisi - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 1.8rem;
            color: #374151;
            margin: 0;
        }

        .header p {
            margin: 4px 0 0;
            color: #6b7280;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }

        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }

        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }

        .stats-grid {
            display: grid;
            gap: 15px;
            margin-bottom: 25px;
        }

        @media (min-width: 768px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }

        .stat-card:hover { transform: translateY(-3px); }

        .stat-card h3 {
            margin: 0;
            color: #4b5563;
            font-size: 1rem;
        }

        .stat-card .number {
            font-size: 1.7rem;
            font-weight: 700;
            color: #2563eb;
            margin-top: 5px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #374151;
        }

        .card-body { padding: 20px; }

        .table-container { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead { background: #f3f4f6; }

        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            color: #374151;
            font-weight: 600;
            font-size: 0.95rem;
        }

        td {
            color: #4b5563;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 6px;
        }

        .empty-state {
            text-align: center;
            color: #6b7280;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        .toast {
            position: fixed;
            top: 25px;
            right: 25px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 14px 20px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            animation: slideIn 0.5s ease, fadeOut 0.6s ease 2.7s forwards;
            z-index: 9999;
        }

        .toast i {
            background: rgba(255,255,255,0.2);
            padding: 6px;
            border-radius: 50%;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.9); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes fadeOut {
            to { opacity: 0; transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-tools"></i> Manajemen Teknisi</h1>
                <p>Kelola data teknisi service HP</p>
            </div>
            <div class="header-actions">
                <a href="tambah_teknisi.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>
                <a href="dashboard_admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="toast" id="notif">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                </div>
                <script>
                    setTimeout(() => {
                        const notif = document.getElementById('notif');
                        if (notif) notif.remove();
                    }, 3000);
                </script>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> Total Teknisi</h3>
                    <div class="number"><?= count($teknisi) ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-user-check"></i> Teknisi Aktif</h3>
                    <div class="number"><?= count(array_filter($teknisi, fn($t) => $t['jumlah_service'] > 0)) ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-clock"></i> Belum Ditugaskan</h3>
                    <div class="number"><?= count(array_filter($teknisi, fn($t) => $t['jumlah_service'] == 0)) ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Teknisi</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($teknisi)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>Belum ada data teknisi</h3>
                            <p>Tambahkan teknisi pertama sekarang</p>
                            <a href="tambah_teknisi.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Teknisi</a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Teknisi</th>
                                        <th>Kontak</th>
                                        <th>Jumlah Service</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teknisi as $index => $t): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($t['nama']) ?></strong></td>
                                        <td><i class="fas fa-phone"></i> <?= htmlspecialchars($t['kontak']) ?></td>
                                        <td><i class="fas fa-clipboard-list"></i> <?= $t['jumlah_service'] ?> service</td>
                                        <td>
                                            <?php if ($t['jumlah_service'] > 0): ?>
                                                <span style="color: #10b981; font-weight: 600;">
                                                    <i class="fas fa-check-circle"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #6b7280; font-weight: 600;">
                                                    <i class="fas fa-clock"></i> Belum Ada Tugas
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="manajemen_service.php" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Detail</a>
                                                <a href="edit_teknisi.php?id=<?= $t['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="hapus_teknisi.php?id=<?= $t['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus teknisi ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
