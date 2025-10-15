<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$total_service = $pdo->query('SELECT COUNT(*) FROM service')->fetchColumn();
$service_proses = $pdo->query('SELECT COUNT(*) FROM service WHERE status = "proses"')->fetchColumn();
$service_selesai = $pdo->query('SELECT COUNT(*) FROM service WHERE status = "selesai"')->fetchColumn();
$total_pelanggan = $pdo->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
$total_teknisi = $pdo->query('SELECT COUNT(*) FROM teknisi')->fetchColumn();

$total_pendapatan = $pdo->query('SELECT SUM(biaya_akhir) FROM service WHERE status = "selesai" AND biaya_akhir IS NOT NULL')->fetchColumn() ?: 0;

$service_terbaru = $pdo->query('
    SELECT s.*, p.nama as nama_pelanggan, t.nama as nama_teknisi 
    FROM service s 
    LEFT JOIN pelanggan p ON s.pelanggan_id = p.id 
    LEFT JOIN teknisi t ON s.teknisi_id = t.id 
    ORDER BY s.tanggal_masuk DESC
')->fetchAll();

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=laporan_service_" . date('Ymd') . ".xls");
    echo "No\tPelanggan\tMerk HP\tTeknisi\tStatus\tTanggal Masuk\tBiaya\n";
    foreach ($service_terbaru as $index => $s) {
        echo ($index + 1) . "\t" .
            $s['nama_pelanggan'] . "\t" .
            $s['merk_hp'] . "\t" .
            ($s['nama_teknisi'] ?: '-') . "\t" .
            ucfirst($s['status']) . "\t" .
            date('d/m/Y', strtotime($s['tanggal_masuk'])) . "\t" .
            ($s['biaya_akhir'] ? 'Rp ' . number_format($s['biaya_akhir'], 0, ',', '.') : '-') . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Service HP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f6fa;
            color: #333;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #0d6efd, #007bff);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            font-size: 1.9rem;
            font-weight: 600;
        }
        .header p {
            margin: 0;
            opacity: 0.8;
        }
        .btn-custom { margin-left: 8px; transition: transform .2s; }
        .btn-custom:hover { transform: scale(1.05); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(13,110,253,0.2);
        }
        .stat-card h3 {
            font-size: 1rem;
            color: #6c757d;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }

        .card {
            margin-top: 40px;
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .card-header {
            background: #0d6efd;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table thead th {
            background: #e9f0ff;
            color: #0d6efd;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .table tbody tr {
            transition: all 0.2s ease-in-out;
        }
        .table tbody tr:hover {
            background: #f1f6ff;
            transform: scale(1.01);
        }


        .status-badge {
            padding: 5px 10px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .status-masuk { background: #dee2e6; color: #495057; }
        .status-proses { background: #fff3cd; color: #856404; }
        .status-selesai { background: #d1e7dd; color: #0f5132; }


        @media print {
            .header, .btn-custom { display: none !important; }
            body { background: white; }
            .card { box-shadow: none; }
            .stat-card { box-shadow: none; border: 1px solid #ccc; }

.pendapatan-card {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    position: relative;
    overflow: hidden;
}

.pendapatan-card::after {
    content: "💰";
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 2.2rem;
    opacity: 0.15;
    animation: swing 2s infinite ease-in-out;
}

.pendapatan-card h3 {
    color: #e9fce5 !important;
    font-weight: 600;
}

.pendapatan-amount {
    font-family: 'Poppins', sans-serif;
    font-size: 2.3rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.15);
    margin-top: 5px;
    transition: transform 0.3s ease, color 0.3s ease;
}

.pendapatan-card:hover .pendapatan-amount {
    transform: scale(1.08);
    color: #fff8dc;
}

@keyframes swing {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(-15deg); }
}

        }
    </style>
</head>
<body>
<div class="container my-4">
    <div class="header mb-4">
        <div>
            <h1><i class="fas fa-chart-line"></i> Laporan Service HP</h1>
            <p>Rekapitulasi Data Service dan Pendapatan</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn btn-light btn-custom"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a href="laporan.php?export=excel" class="btn btn-success btn-custom"><i class="fas fa-file-excel"></i> Export Excel</a>
            <button onclick="window.print()" class="btn btn-primary btn-custom"><i class="fas fa-print"></i> Cetak</button>
            <a href="logout.php" class="btn btn-danger btn-custom"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-clipboard-list"></i> Total Service</h3>
            <div class="number"><?= $total_service ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-cogs"></i> Sedang Diproses</h3>
            <div class="number"><?= $service_proses ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-check-circle"></i> Selesai</h3>
            <div class="number"><?= $service_selesai ?></div>
        </div>
        <div class="stat-card pendapatan-card">
            <h3><i class="fas fa-money-bill-wave"></i> Pendapatan</h3>
            <div class="number pendapatan-amount">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-users"></i> Pelanggan</h3>
            <div class="number"><?= $total_pelanggan ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-user-cog"></i> Teknisi</h3>
            <div class="number"><?= $total_teknisi ?></div>
        </div>
    </div>

    <div class="card mt-5">
        <div class="card-header">
            <i class="fas fa-list"></i> Daftar Service
        </div>
        <div class="card-body">
            <?php if (empty($service_terbaru)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                    <h5>Belum ada data service</h5>
                    <p>Belum ada service yang tercatat dalam sistem</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pelanggan</th>
                                <th>Merk HP</th>
                                <th>Teknisi</th>
                                <th>Status</th>
                                <th>Tanggal Masuk</th>
                                <th>Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_terbaru as $index => $s): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($s['nama_pelanggan']) ?></td>
                                    <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                    <td><?= $s['nama_teknisi'] ? htmlspecialchars($s['nama_teknisi']) : '-' ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($s['status']) {
                                            case 'masuk': $status_class = 'status-masuk'; break;
                                            case 'proses': $status_class = 'status-proses'; break;
                                            case 'selesai': $status_class = 'status-selesai'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $status_class ?>"><?= ucfirst($s['status']) ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($s['tanggal_masuk'])) ?></td>
                                    <td>
                                        <?php if ($s['biaya_akhir']): ?>
                                            Rp <?= number_format($s['biaya_akhir'], 0, ',', '.') ?>
                                        <?php elseif ($s['estimasi_biaya']): ?>
                                            Rp <?= number_format($s['estimasi_biaya'], 0, ',', '.') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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
