<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teknisi') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id FROM teknisi WHERE user_id = ?');
$stmt->execute([$user_id]);
$teknisi = $stmt->fetch(PDO::FETCH_ASSOC);

if ($teknisi) {
    $teknisi_id = $teknisi['id'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service WHERE teknisi_id = ?');
    $stmt->execute([$teknisi_id]);
    $total_service = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service WHERE teknisi_id = ? AND status IN ("masuk","proses")');
    $stmt->execute([$teknisi_id]);
    $service_ditugaskan = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service WHERE teknisi_id = ? AND status = "selesai"');
    $stmt->execute([$teknisi_id]);
    $service_selesai = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT s.*, p.nama AS nama_pelanggan, p.kontak
        FROM service s
        JOIN pelanggan p ON s.pelanggan_id = p.id
        WHERE s.teknisi_id = ? AND s.status IN ("masuk","proses")
        ORDER BY s.tanggal_masuk DESC
    ');
    $stmt->execute([$teknisi_id]);
    $service_aktif = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $total_service = $service_ditugaskan = $service_selesai = 0;
    $service_aktif = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Teknisi - Service HP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }
    body {
        background: #f5f6fa;
        color: #333;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .container {
        width: 95%;
        max-width: 1100px;
        margin-top: 40px;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(90deg, #2563eb, #1e40af);
        color: white;
        padding: 20px 30px;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .header h1 {
        font-size: 1.6rem;
        font-weight: 600;
    }
    .header p {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #dc2626;
        color: white;
        padding: 10px 16px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: 0.3s;
    }
    .btn:hover { background: #b91c1c; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-card h3 {
        font-weight: 500;
        color: #555;
        margin-bottom: 10px;
    }
    .stat-card .number {
        font-size: 2rem;
        font-weight: 700;
        color: #1e3a8a;
    }
    .processing .number { color: #f59e0b; }
    .completed .number { color: #16a34a; }

    .card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        overflow: hidden;
    }
    .card-header {
        padding: 15px 25px;
        background: #1e3a8a;
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-body {
        padding: 25px;
    }
    .empty-state {
        text-align: center;
        color: #6b7280;
        padding: 40px 0;
    }
    .empty-state i {
        font-size: 3rem;
        color: #9ca3af;
        margin-bottom: 10px;
    }

    .table-container {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }
    th {
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
    }
    tr:hover td {
        background: #f1f5f9;
    }
    .status-badge {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .status-masuk { background: #dbeafe; color: #1d4ed8; }
    .status-proses { background: #fef3c7; color: #b45309; }
    .status-selesai { background: #dcfce7; color: #15803d; }

    .btn-primary {
        background: #2563eb;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.85rem;
    }
    .btn-primary:hover { background: #1e40af; }

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .menu-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        background: #f9fafb;
        padding: 25px;
        border-radius: 15px;
        text-decoration: none;
        color: #111827;
        transition: all 0.3s;
    }
    .menu-card i {
        font-size: 2rem;
        color: #2563eb;
        margin-bottom: 10px;
    }
    .menu-card:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-4px);
    }
    .menu-card:hover i { color: white; }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-tools"></i> Dashboard Teknisi</h1>
                <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama']); ?></p>
            </div>
            <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-clipboard-list"></i> Total Service</h3>
                <div class="number"><?= $total_service ?></div>
            </div>
            <div class="stat-card processing">
                <h3><i class="fas fa-user-check"></i> Ditugaskan</h3>
                <div class="number"><?= $service_ditugaskan ?></div>
            </div>
            <div class="stat-card completed">
                <h3><i class="fas fa-check-circle"></i> Selesai</h3>
                <div class="number"><?= $service_selesai ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-list"></i> Service yang Ditugaskan</div>
            <div class="card-body">
                <?php if (empty($service_aktif)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>Tidak ada service yang ditugaskan</h3>
                        <p>Belum ada service yang ditugaskan kepada Anda.</p>
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
                                    <th>Estimasi Biaya</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($service_aktif as $index => $s): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($s['nama_pelanggan']) ?></strong><br>
                                        <small><?= htmlspecialchars($s['kontak']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                    <td><?= htmlspecialchars($s['kerusakan']) ?></td>
                                    <td>
                                        <?php
                                        $class = match($s['status']) {
                                            'masuk' => 'status-masuk',
                                            'proses' => 'status-proses',
                                            'selesai' => 'status-selesai',
                                            default => ''
                                        };
                                        ?>
                                        <span class="status-badge <?= $class ?>"><?= ucfirst($s['status']) ?></span>
                                    </td>
                                    <td><?= $s['tanggal_masuk'] ? date('d/m/Y', strtotime($s['tanggal_masuk'])) : '-' ?></td>
                                    <td><?= $s['estimasi_biaya'] ? 'Rp '.number_format($s['estimasi_biaya'],0,',','.') : '-' ?></td>
                                    <td><a href="tindakan_teknisi.php?service_id=<?= (int)$s['id'] ?>" class="btn-primary"><i class="fas fa-edit"></i> Update</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-cogs"></i> Menu Utama</div>
            <div class="card-body">
                <div class="menu-grid">
                    <a href="tindakan_teknisi.php" class="menu-card">
                        <i class="fas fa-tools"></i>
                        <h4>Update Tindakan</h4>
                        <p>Update progress dan tindakan perbaikan service</p>
                    </a>
                    <a href="riwayat_teknisi.php" class="menu-card">
                        <i class="fas fa-history"></i>
                        <h4>Riwayat Service</h4>
                        <p>Lihat riwayat service yang telah ditangani</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
