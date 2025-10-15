<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$pelanggan_id = isset($_GET['pelanggan_id']) ? intval($_GET['pelanggan_id']) : 0;

if (!$pelanggan_id) {
    header('Location: manajemen_service.php');
    exit;
}

$service_detail = null;
$tindakan_teknisi = [];
if (isset($_GET['service_id'])) {
    $service_id = intval($_GET['service_id']);
    
    $stmt = $pdo->prepare('
        SELECT s.*, t.nama as nama_teknisi 
        FROM service s 
        LEFT JOIN teknisi t ON s.teknisi_id = t.id 
        WHERE s.id = ? AND s.pelanggan_id = ?
    ');
    $stmt->execute([$service_id, $pelanggan_id]);
    $service_detail = $stmt->fetch();
    
    if ($service_detail) {
        $stmt = $pdo->prepare('
            SELECT tindakan.*, teknisi.nama as nama_teknisi
            FROM tindakan 
            JOIN teknisi ON tindakan.teknisi_id = teknisi.id
            WHERE tindakan.service_id = ?
            ORDER BY tindakan.tanggal DESC
        ');
        $stmt->execute([$service_id]);
        $tindakan_teknisi = $stmt->fetchAll();
    }
}

$stmt = $pdo->prepare('
    SELECT s.*, t.nama as nama_teknisi 
    FROM service s 
    LEFT JOIN teknisi t ON s.teknisi_id = t.id 
    WHERE s.pelanggan_id = ? 
    ORDER BY s.tanggal_masuk DESC
');
$stmt->execute([$pelanggan_id]);
$services = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT nama FROM pelanggan WHERE id = ?');
$stmt->execute([$pelanggan_id]);
$pelanggan = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pelanggan - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            margin: 0;
            color: #333;
        }
        .container {
            max-width: 1150px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 25px 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-bottom: 3px solid #6366f1;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 1.7rem;
            color: #111827;
            margin: 0;
        }
        .header p {
            margin: 3px 0 0 0;
            color: #6b7280;
        }
        .header-actions a {
            margin-left: 10px;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover { background: #dc2626; }

        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        .card-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 18px 25px;
            border-radius: 12px 12px 0 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
        }
        thead {
            background: #eef2ff;
            color: #4338ca;
        }
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        tbody tr:hover {
            background: #f1f5f9;
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-masuk { background: #e0f2fe; color: #0369a1; }
        .status-proses { background: #fef9c3; color: #92400e; }
        .status-selesai { background: #dcfce7; color: #166534; }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #6b7280;
        }
        .empty-state i {
            font-size: 3rem;
            color: #9ca3af;
        }
        .empty-state h3 {
            margin: 10px 0 5px;
        }

        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        .btn-primary:hover { background: #4f46e5; }
        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }
        .btn-secondary:hover { background: #d1d5db; }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            table { font-size: 0.9rem; }
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fas fa-history"></i> Riwayat Service Pelanggan</h1>
            <p>Riwayat service untuk <strong><?= htmlspecialchars($pelanggan['nama']) ?></strong></p>
        </div>
        <div class="header-actions">
            <a href="manajemen_service.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <a href="logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="content">
        <?php if ($service_detail): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Detail Service
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <div><strong>Merk HP:</strong><br><?= htmlspecialchars($service_detail['merk_hp']) ?></div>
                        <div><strong>Kerusakan:</strong><br><?= htmlspecialchars($service_detail['kerusakan']) ?></div>
                        <div><strong>Status:</strong><br>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($service_detail['status']) {
                                case 'masuk': $status_class = 'status-masuk'; $status_text = 'Masuk'; break;
                                case 'proses': $status_class = 'status-proses'; $status_text = 'Proses'; break;
                                case 'selesai': $status_class = 'status-selesai'; $status_text = 'Selesai'; break;
                            }
                            ?>
                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                        </div>
                        <div><strong>Teknisi:</strong><br><?= $service_detail['nama_teknisi'] ? htmlspecialchars($service_detail['nama_teknisi']) : '-' ?></div>
                        <div><strong>Tanggal Masuk:</strong><br><?= date('d/m/Y', strtotime($service_detail['tanggal_masuk'])) ?></div>
                        <div><strong>Tanggal Selesai:</strong><br><?= $service_detail['tanggal_selesai'] ? date('d/m/Y', strtotime($service_detail['tanggal_selesai'])) : '-' ?></div>
                        <div><strong>Biaya:</strong><br>
                            <?php if ($service_detail['biaya_akhir']): ?>
                                Rp <?= number_format($service_detail['biaya_akhir'], 0, ',', '.') ?>
                            <?php elseif ($service_detail['estimasi_biaya']): ?>
                                Rp <?= number_format($service_detail['estimasi_biaya'], 0, ',', '.') ?>
                            <?php else: ?> - <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($tindakan_teknisi)): ?>
                        <h4><i class="fas fa-tools"></i> Riwayat Tindakan Teknisi</h4>
                        <div class="table-container" style="margin-top: 15px;">
                            <table>
                                <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Teknisi</th>
                                    <th>Deskripsi Tindakan</th>
                                    <th>Sparepart</th>
                                    <th>Biaya Sparepart</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($tindakan_teknisi as $t): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($t['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($t['nama_teknisi']) ?></td>
                                        <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                                        <td><?= htmlspecialchars($t['sparepart'] ?: '-') ?></td>
                                        <td><?= $t['biaya_sparepart'] ? 'Rp '.number_format($t['biaya_sparepart'], 0, ',', '.') : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 25px;">
                        <a href="riwayat_pelanggan_admin.php?pelanggan_id=<?= $pelanggan_id ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Riwayat</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Daftar Service
                </div>
                <div class="card-body">
                    <?php if (empty($services)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>Belum ada riwayat service</h3>
                            <p>Pelanggan ini belum pernah melakukan service HP</p>
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
                                    <th>Tanggal Selesai</th>
                                    <th>Biaya</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($services as $index => $s): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                        <td><?= htmlspecialchars($s['kerusakan']) ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($s['status']) {
                                                case 'masuk': $status_class = 'status-masuk'; $status_text = 'Masuk'; break;
                                                case 'proses': $status_class = 'status-proses'; $status_text = 'Proses'; break;
                                                case 'selesai': $status_class = 'status-selesai'; $status_text = 'Selesai'; break;
                                            }
                                            ?>
                                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?= $s['nama_teknisi'] ? htmlspecialchars($s['nama_teknisi']) : '-' ?></td>
                                        <td><?= date('d/m/Y', strtotime($s['tanggal_masuk'])) ?></td>
                                        <td><?= $s['tanggal_selesai'] ? date('d/m/Y', strtotime($s['tanggal_selesai'])) : '-' ?></td>
                                        <td>
                                            <?php if ($s['biaya_akhir']): ?>
                                                Rp <?= number_format($s['biaya_akhir'], 0, ',', '.') ?>
                                            <?php elseif ($s['estimasi_biaya']): ?>
                                                Rp <?= number_format($s['estimasi_biaya'], 0, ',', '.') ?>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
