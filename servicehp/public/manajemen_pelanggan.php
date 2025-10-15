<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$pelanggan = $pdo->query('SELECT * FROM pelanggan')->fetchAll();

// Pesan notifikasi
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deleted':
            $message = 'Data pelanggan berhasil dihapus!';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f9fafb;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }

        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 26px;
            color: #111827;
            margin: 0;
        }

        .header p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn {
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn i {
            font-size: 14px;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #fff;
        }
        .btn-primary:hover { background-color: #1d4ed8; }

        .btn-secondary {
            background-color: #6b7280;
            color: #fff;
        }
        .btn-secondary:hover { background-color: #4b5563; }

        .btn-danger {
            background-color: #dc2626;
            color: #fff;
        }
        .btn-danger:hover { background-color: #b91c1c; }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease-in-out;
        }

        .alert-success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .stat-card h3 {
            font-size: 16px;
            margin: 0 0 8px;
            font-weight: 500;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .card-header {
            background: #f3f4f6;
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 16px;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body {
            padding: 20px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        tr:hover {
            background-color: #f3f4f6;
            transition: 0.2s;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 13px;
            border-radius: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        .empty-state h3 {
            color: #111827;
            margin-bottom: 8px;
        }

        .empty-state p {
            margin-bottom: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .header-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-users"></i> Manajemen Data Pelanggan</h1>
                <p>Kelola, tambahkan, dan perbarui akun pelanggan dengan mudah.</p>
            </div>
            <div class="header-actions">
                <a href="tambah_pelanggan.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Akun</a>
                <a href="dashboard_admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success" id="notif">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
            <script>
                setTimeout(() => {
                    document.getElementById('notif').style.display = 'none';
                }, 3000);
            </script>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Total Pelanggan</h3>
                <div class="number"><?= count($pelanggan) ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #059669, #10b981); box-shadow: 0 4px 12px rgba(16,185,129,0.2);">
                <h3><i class="fas fa-user-check"></i> Pelanggan Aktif</h3>
                <div class="number"><?= count($pelanggan) ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Daftar Pelanggan
            </div>
            <div class="card-body">
                <?php if (empty($pelanggan)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-times"></i>
                        <h3>Belum ada data pelanggan</h3>
                        <p>Tambahkan pelanggan pertama Anda sekarang.</p>
                        <a href="tambah_pelanggan.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pelanggan</a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Kontak</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pelanggan as $index => $p): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
                                        <td><i class="fas fa-phone"></i> <?= htmlspecialchars($p['kontak']) ?></td>
                                        <td><span style="color:#10b981; font-weight:600;"><i class="fas fa-circle-check"></i> Aktif</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="riwayat_pelanggan_admin.php?pelanggan_id=<?= $p['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-history"></i> Riwayat</a>
                                                <a href="edit_pelanggan.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="hapus_pelanggan.php?id=<?= $p['id'] ?>" onclick="return confirm('Yakin ingin menghapus pelanggan ini?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</a>
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
</body>
</html>
