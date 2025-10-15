<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teknisi') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT t.id FROM teknisi t JOIN users u ON t.user_id = u.id WHERE u.id = ?');
$stmt->execute([$user_id]);
$teknisi = $stmt->fetch();
$teknisi_id = $teknisi ? $teknisi['id'] : 0;

$service_detail = null;
$tindakan_teknisi = [];
if (isset($_GET['service_id'])) {
    $service_id = intval($_GET['service_id']);
    $stmt = $pdo->prepare('
        SELECT s.*, p.nama as nama_pelanggan, p.kontak 
        FROM service s 
        JOIN pelanggan p ON s.pelanggan_id = p.id 
        WHERE s.id = ? AND s.teknisi_id = ?
    ');
    $stmt->execute([$service_id, $teknisi_id]);
    $service_detail = $stmt->fetch();

    if ($service_detail) {
        $stmt = $pdo->prepare('
            SELECT t.*, k.nama as nama_teknisi
            FROM tindakan t
            JOIN teknisi k ON t.teknisi_id = k.id
            WHERE t.service_id = ?
            ORDER BY t.tanggal DESC
        ');
        $stmt->execute([$service_id]);
        $tindakan_teknisi = $stmt->fetchAll();
    }
}

$stmt = $pdo->prepare('
    SELECT s.*, p.nama as nama_pelanggan, p.kontak 
    FROM service s 
    JOIN pelanggan p ON s.pelanggan_id = p.id 
    WHERE s.teknisi_id = ? 
    ORDER BY s.tanggal_masuk DESC
');
$stmt->execute([$teknisi_id]);
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Pengerjaan - Service HP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f3f4f6;
        color: #1f2937;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 1150px;
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
        font-size: 26px;
        color: #2563eb;
        margin: 0;
    }
    .header p {
        margin: 4px 0 0;
        color: #6b7280;
    }
    .header-actions a {
        text-decoration: none;
        margin-left: 8px;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all .2s ease;
    }
    .btn-primary {
        background: #2563eb;
        color: #fff;
    }
    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }
    .btn-danger {
        background: #ef4444;
        color: #fff;
    }
    .btn-primary:hover { background: #1d4ed8; }
    .btn-secondary:hover { background: #d1d5db; }
    .btn-danger:hover { background: #dc2626; }

    .card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        overflow: hidden;
    }
    .card-header {
        background: #f9fafb;
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-header h3 {
        font-size: 18px;
        color: #1f2937;
        margin: 0;
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
        margin-top: 8px;
    }
    th, td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    th {
        background: #f3f4f6;
        font-weight: 600;
        color: #374151;
    }
    tr:hover td {
        background-color: #f9fafb;
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
    .empty-state {
        text-align: center;
        padding: 50px 0;
        color: #6b7280;
    }
    .empty-state i {
        font-size: 42px;
        color: #9ca3af;
        margin-bottom: 10px;
    }
    .empty-state h3 {
        color: #1f2937;
        margin-bottom: 6px;
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fas fa-history"></i> Riwayat Pengerjaan</h1>
            <p>Riwayat lengkap service yang telah ditangani</p>
        </div>
        <div class="header-actions">
            <a href="dashboard_teknisi.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a href="logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="content">
        <?php if ($service_detail): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Detail Service</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:15px;margin-bottom:20px;">
                        <div><strong>Pelanggan:</strong><br><?= htmlspecialchars($service_detail['nama_pelanggan']) ?><br><small><?= htmlspecialchars($service_detail['kontak']) ?></small></div>
                        <div><strong>Merk HP:</strong><br><?= htmlspecialchars($service_detail['merk_hp']) ?></div>
                        <div><strong>Kerusakan:</strong><br><?= htmlspecialchars($service_detail['kerusakan']) ?></div>
                        <div><strong>Status:</strong><br><span class="status-badge status-<?= htmlspecialchars($service_detail['status']) ?>"><?= ucfirst($service_detail['status']) ?></span></div>
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
                        <h4><i class="fas fa-tools"></i> Riwayat Tindakan</h4>
                        <div class="table-container" style="margin-top:12px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Deskripsi</th>
                                        <th>Sparepart</th>
                                        <th>Biaya Sparepart</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tindakan_teknisi as $t): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($t['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                                        <td><?= htmlspecialchars($t['sparepart'] ?: '-') ?></td>
                                        <td><?= $t['biaya_sparepart'] ? 'Rp '.number_format($t['biaya_sparepart'], 0, ',', '.') : '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:20px;">
                        <a href="riwayat_teknisi.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Service</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($services)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>Belum ada riwayat service</h3>
                            <p>Anda belum pernah menangani service HP</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Pelanggan</th>
                                        <th>Merk HP</th>
                                        <th>Kerusakan</th>
                                        <th>Status</th>
                                        <th>Tanggal Masuk</th>
                                        <th>Tanggal Selesai</th>
                                        <th>Biaya</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $i => $s): ?>
                                    <tr>
                                        <td><?= $i+1 ?></td>
                                        <td><strong><?= htmlspecialchars($s['nama_pelanggan']) ?></strong><br><small><?= htmlspecialchars($s['kontak']) ?></small></td>
                                        <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                        <td><?= htmlspecialchars($s['kerusakan']) ?></td>
                                        <td><span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($s['tanggal_masuk'])) ?></td>
                                        <td><?= $s['tanggal_selesai'] ? date('d/m/Y', strtotime($s['tanggal_selesai'])) : '-' ?></td>
                                        <td><?= $s['biaya_akhir'] ? 'Rp '.number_format($s['biaya_akhir'],0,',','.') : ($s['estimasi_biaya'] ? 'Rp '.number_format($s['estimasi_biaya'],0,',','.') : '-') ?></td>
                                        <td><a href="?service_id=<?= $s['id'] ?>" class="btn-primary" style="padding:6px 10px;font-size:13px;"><i class="fas fa-eye"></i> Detail</a></td>
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
