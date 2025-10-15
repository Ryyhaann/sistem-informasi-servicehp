<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id FROM pelanggan WHERE user_id = ?');
$stmt->execute([$user_id]);
$pelanggan = $stmt->fetch();

if ($pelanggan) {
    $pelanggan_id = $pelanggan['id'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service WHERE pelanggan_id = ?');
    $stmt->execute([$pelanggan_id]);
    $total_service = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service WHERE pelanggan_id = ? AND status = "proses"');
    $stmt->execute([$pelanggan_id]);
    $service_proses = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service WHERE pelanggan_id = ? AND status = "selesai"');
    $stmt->execute([$pelanggan_id]);
    $service_selesai = $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT s.*, t.nama as nama_teknisi 
        FROM service s 
        LEFT JOIN teknisi t ON s.teknisi_id = t.id 
        WHERE s.pelanggan_id = ? 
        ORDER BY s.tanggal_masuk DESC 
        LIMIT 5
    ');
    $stmt->execute([$pelanggan_id]);
    $service_terbaru = $stmt->fetchAll();
} else {
    $total_service = 0;
    $service_proses = 0;
    $service_selesai = 0;
    $service_terbaru = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pelanggan - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {margin:0; padding:0; box-sizing:border-box;}
        body {
            font-family:'Inter',sans-serif;
            background:#f5f7fb;
            color:#333;
            min-height:100vh;
        }
        .container {
            max-width:1200px;
            margin:0 auto;
            padding:30px 20px;
        }
        .header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:30px;
        }
        .header h1 {
            font-size:24px;
            font-weight:600;
            color:#4f46e5;
        }
        .header p {
            color:#6b7280;
            margin-top:4px;
            font-size:14px;
        }
        .btn {
            padding:10px 18px;
            border:none;
            border-radius:8px;
            font-weight:500;
            cursor:pointer;
            transition:all 0.3s ease;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:6px;
        }
        .btn-danger {
            background:#ef4444;
            color:white;
        }
        .btn-danger:hover {
            background:#dc2626;
        }

        .stats-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
            gap:20px;
            margin-bottom:30px;
        }
        .stat-card {
            background:white;
            border-radius:14px;
            padding:25px;
            box-shadow:0 5px 15px rgba(0,0,0,0.05);
            transition:all 0.3s ease;
        }
        .stat-card:hover {
            transform:translateY(-4px);
            box-shadow:0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size:16px;
            color:#6b7280;
            margin-bottom:12px;
        }
        .stat-card .number {
            font-size:30px;
            font-weight:700;
            color:#4f46e5;
        }
        .processing .number {color:#f59e0b;}
        .completed .number {color:#10b981;}

        .card {
            background:white;
            border-radius:14px;
            box-shadow:0 5px 15px rgba(0,0,0,0.05);
            overflow:hidden;
        }
        .card-header {
            background:linear-gradient(135deg,#6366f1,#8b5cf6);
            color:white;
            padding:18px 25px;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:10px;
            font-size:16px;
        }
        .card-body {
            padding:25px;
        }

        .table-container {
            overflow-x:auto;
        }
        table {
            width:100%;
            border-collapse:collapse;
            min-width:700px;
        }
        th, td {
            text-align:left;
            padding:14px 16px;
            border-bottom:1px solid #e5e7eb;
            font-size:14px;
        }
        th {
            background:#f9fafb;
            font-weight:600;
            color:#374151;
        }
        tr:hover td {
            background:#f3f4f6;
        }

        .status-badge {
            display:inline-block;
            padding:6px 12px;
            border-radius:20px;
            font-size:12px;
            font-weight:600;
        }
        .status-masuk {background:#e0e7ff; color:#4338ca;}
        .status-proses {background:#fef3c7; color:#92400e;}
        .status-selesai {background:#dcfce7; color:#166534;}

        .btn-sm {
            padding:6px 10px;
            font-size:13px;
            border-radius:6px;
        }
        .btn-primary {
            background:#4f46e5;
            color:white;
        }
        .btn-primary:hover {
            background:#4338ca;
        }

        .empty-state {
            text-align:center;
            color:#6b7280;
            padding:40px 0;
        }
        .empty-state i {
            font-size:40px;
            color:#a5b4fc;
            margin-bottom:10px;
        }
        .empty-state h3 {
            font-size:18px;
            margin-bottom:6px;
        }

        @media(max-width:768px) {
            .header {
                flex-direction:column;
                align-items:flex-start;
                gap:10px;
            }
            .stats-grid {
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fas fa-user"></i> Dashboard Pelanggan</h1>
            <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama']); ?></p>
        </div>
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-clipboard-list"></i> Total Service</h3>
            <div class="number"><?= $total_service ?></div>
        </div>
        <div class="stat-card processing">
            <h3><i class="fas fa-tools"></i> Sedang Diproses</h3>
            <div class="number"><?= $service_proses ?></div>
        </div>
        <div class="stat-card completed">
            <h3><i class="fas fa-check-circle"></i> Selesai</h3>
            <div class="number"><?= $service_selesai ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-history"></i> Riwayat Service Terbaru
        </div>
        <div class="card-body">
            <?php if (empty($service_terbaru)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Belum ada riwayat service</h3>
                    <p>Anda belum pernah melakukan service HP</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Merk HP</th>
                                <th>Kerusakan</th>
                                <th>Status</th>
                                <th>Teknisi</th>
                                <th>Tanggal Masuk</th>
                                <th>Biaya</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_terbaru as $index => $service): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($service['merk_hp']) ?></td>
                                <td><?= htmlspecialchars($service['kerusakan']) ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($service['status']) {
                                        case 'masuk': $status_class = 'status-masuk'; $status_text = 'Masuk'; break;
                                        case 'proses': $status_class = 'status-proses'; $status_text = 'Proses'; break;
                                        case 'selesai': $status_class = 'status-selesai'; $status_text = 'Selesai'; break;
                                    }
                                    ?>
                                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                </td>
                                <td><?= $service['nama_teknisi'] ? htmlspecialchars($service['nama_teknisi']) : '-' ?></td>
                                <td><?= date('d/m/Y', strtotime($service['tanggal_masuk'])) ?></td>
                                <td>
                                    <?php if ($service['biaya_akhir']): ?>
                                        Rp <?= number_format($service['biaya_akhir'], 0, ',', '.') ?>
                                    <?php elseif ($service['estimasi_biaya']): ?>
                                        Rp <?= number_format($service['estimasi_biaya'], 0, ',', '.') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="riwayat_pelanggan.php?service_id=<?= $service['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
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
