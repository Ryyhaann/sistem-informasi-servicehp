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

$pelanggan_id = $pelanggan ? $pelanggan['id'] : 0;

$service_detail = null;
$tindakan_teknisi = [];
if (isset($_GET['service_id'])) {
    $service_id = intval($_GET['service_id']);
    $stmt = $pdo->prepare('
        SELECT s.*, t.nama AS nama_teknisi 
        FROM service s 
        LEFT JOIN teknisi t ON s.teknisi_id = t.id 
        WHERE s.id = ? AND s.pelanggan_id = ?
    ');
    $stmt->execute([$service_id, $pelanggan_id]);
    $service_detail = $stmt->fetch();

    if ($service_detail) {
        $stmt = $pdo->prepare('
            SELECT tindakan.*, teknisi.nama AS nama_teknisi
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
    SELECT s.*, t.nama AS nama_teknisi 
    FROM service s 
    LEFT JOIN teknisi t ON s.teknisi_id = t.id 
    WHERE s.pelanggan_id = ? 
    ORDER BY s.tanggal_masuk DESC
');
$stmt->execute([$pelanggan_id]);
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Service - Service HP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --danger: #ef4444;
            --gray-bg: #f9fafb;
            --gray-border: #e5e7eb;
            --dark: #111827;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-bg);
            margin: 0;
            color: var(--dark);
        }
        .container {
            max-width: 1150px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: var(--primary);
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .header-actions a {
            text-decoration: none;
            background: rgba(255,255,255,0.15);
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 14px;
            transition: 0.2s;
        }
        .header-actions a:hover {
            background: rgba(255,255,255,0.3);
        }
        .content {
            padding: 25px 30px;
        }
        .card {
            background: white;
            border: 1px solid var(--gray-border);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .card-header {
            padding: 14px 18px;
            background: var(--gray-bg);
            border-bottom: 1px solid var(--gray-border);
        }
        .card-header h3 {
            margin: 0;
            font-size: 17px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-body {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--gray-border);
            text-align: left;
        }
        th {
            background: var(--gray-bg);
            font-weight: 600;
        }
        tr:hover td {
            background: #f3f4f6;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-masuk { background: #dbeafe; color: #1e40af; }
        .status-proses { background: #fef3c7; color: #92400e; }
        .status-selesai { background: #dcfce7; color: #166534; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            padding: 7px 12px;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary {
            background: var(--gray-bg);
            color: var(--dark);
            border: 1px solid var(--gray-border);
        }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover { background: #dc2626; }
        .empty-state {
            text-align: center;
            color: #6b7280;
            padding: 50px 0;
        }
        .empty-state i {
            font-size: 40px;
            color: #9ca3af;
        }
        .empty-state h3 {
            margin: 10px 0 5px;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-history"></i> Riwayat Service</h1>
            </div>
            <div class="header-actions">
                <a href="dashboard_pelanggan.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content">
            <?php if ($service_detail): ?>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-info-circle"></i> Detail Service</h3></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:15px;margin-bottom:20px;">
                        <div><strong>Merk HP:</strong><br><?= htmlspecialchars($service_detail['merk_hp']) ?></div>
                        <div><strong>Kerusakan:</strong><br><?= htmlspecialchars($service_detail['kerusakan']) ?></div>
                        <div><strong>Status:</strong><br>
                            <span class="status-badge status-<?= htmlspecialchars($service_detail['status']) ?>">
                                <?= ucfirst($service_detail['status']) ?>
                            </span>
                        </div>
                        <div><strong>Teknisi:</strong><br><?= htmlspecialchars($service_detail['nama_teknisi'] ?: '-') ?></div>
                        <div><strong>Tanggal Masuk:</strong><br><?= date('d/m/Y', strtotime($service_detail['tanggal_masuk'])) ?></div>
                        <div><strong>Tanggal Selesai:</strong><br><?= $service_detail['tanggal_selesai'] ? date('d/m/Y', strtotime($service_detail['tanggal_selesai'])) : '-' ?></div>
                        <div><strong>Biaya:</strong><br>
                            <?php if ($service_detail['biaya_akhir']): ?>
                                Rp <?= number_format($service_detail['biaya_akhir'], 0, ',', '.') ?>
                            <?php elseif ($service_detail['estimasi_biaya']): ?>
                                Rp <?= number_format($service_detail['estimasi_biaya'], 0, ',', '.') ?>
                            <?php else: ?>-<?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($tindakan_teknisi)): ?>
                    <h4><i class="fas fa-tools"></i> Riwayat Tindakan Teknisi</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Teknisi</th>
                                <th>Deskripsi</th>
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
                    <?php endif; ?>

                    <div style="margin-top:20px;">
                        <a href="riwayat_pelanggan.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-list"></i> Daftar Service</h3></div>
                <div class="card-body">
                    <?php if (empty($services)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>Belum ada riwayat</h3>
                            <p>Anda belum pernah melakukan service HP.</p>
                        </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Merk HP</th>
                                <th>Kerusakan</th>
                                <th>Status</th>
                                <th>Teknisi</th>
                                <th>Tgl Masuk</th>
                                <th>Tgl Selesai</th>
                                <th>Biaya</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $i => $s): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                <td><?= htmlspecialchars($s['kerusakan']) ?></td>
                                <td><span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                                <td><?= htmlspecialchars($s['nama_teknisi'] ?: '-') ?></td>
                                <td><?= date('d/m/Y', strtotime($s['tanggal_masuk'])) ?></td>
                                <td><?= $s['tanggal_selesai'] ? date('d/m/Y', strtotime($s['tanggal_selesai'])) : '-' ?></td>
                                <td><?= $s['biaya_akhir'] ? 'Rp '.number_format($s['biaya_akhir'],0,',','.') : ($s['estimasi_biaya'] ? 'Rp '.number_format($s['estimasi_biaya'],0,',','.') : '-') ?></td>
                                <td><a href="?service_id=<?= $s['id'] ?>" class="btn btn-primary"><i class="fas fa-eye"></i> Detail</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
