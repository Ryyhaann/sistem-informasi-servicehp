<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

$service_terbaru = $pdo->query('
    SELECT s.*, p.nama AS nama_pelanggan, t.nama AS nama_teknisi 
    FROM service s 
    LEFT JOIN pelanggan p ON s.pelanggan_id = p.id 
    LEFT JOIN teknisi t ON s.teknisi_id = t.id 
    ORDER BY s.tanggal_masuk DESC
')->fetchAll();

$success_message = '';
if (isset($_POST['update_pembayaran'])) {
    $service_id = $_POST['service_id'];
    $status_pembayaran = $_POST['status_pembayaran'];
    $stmt = $pdo->prepare("UPDATE service SET status_pembayaran = ? WHERE id = ?");
    $stmt->execute([$status_pembayaran, $service_id]);
    $success_message = 'Status pembayaran berhasil diperbarui!';
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
    body { font-family: 'Inter', sans-serif; background-color: #f5f7fb; color: #333; }
    .header { background: linear-gradient(135deg, #0d6efd, #007bff); color: white; padding: 25px 30px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .btn-custom { margin-left: 8px; transition: transform .2s; }
    .btn-custom:hover { transform: scale(1.05); }
    .card { margin-top: 40px; border-radius: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); overflow: hidden; }
    .card-header { background: #0d6efd; color: white; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .table thead th { background: #e9f0ff; color: #0d6efd; text-transform: uppercase; font-size: 0.85rem; }
    .table tbody tr:hover { background: #f1f6ff; }
    .aksi-btn { display: flex; justify-content: center; gap: 5px; }
    .alert-popup {
        position: fixed;
        top: 30px; left: 50%;
        transform: translateX(-50%) translateY(-20px);
        background: linear-gradient(135deg, #198754, #28a745);
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        font-weight: 500;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        opacity: 0;
        transition: all 0.4s ease;
        z-index: 9999;
    }
    .alert-popup.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    .alert-popup i {
        font-size: 1.2rem;
    }
</style>
</head>
<body>

<div class="container my-4">
    <div class="header mb-4">
        <div>
            <h1><i class="fas fa-chart-line"></i> Laporan Service HP</h1>
            <p>Rekapitulasi Data Service dan Pembayaran</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn btn-light btn-custom"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert-popup" id="popupNotif">
            <i class="fas fa-check-circle"></i>
            <?= $success_message ?>
        </div>
        <script>
            const popup = document.getElementById('popupNotif');
            popup.classList.add('show');
            setTimeout(() => {
                popup.classList.remove('show');
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Daftar Service
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pelanggan</th>
                            <th>Merk HP</th>
                            <th>Teknisi</th>
                            <th>Status</th>
                            <th>Status Pembayaran</th>
                            <th>Tanggal Masuk</th>
                            <th>Biaya</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($service_terbaru)): ?>
                            <tr><td colspan="9" class="text-muted py-4">Belum ada data service</td></tr>
                        <?php else: foreach ($service_terbaru as $index => $s): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($s['nama_pelanggan']) ?></td>
                                <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                <td><?= $s['nama_teknisi'] ?: '-' ?></td>
                                <td><?= ucfirst($s['status']) ?></td>
                                <td><?= ucfirst($s['status_pembayaran'] ?? '-') ?></td>
                                <td><?= date('d/m/Y', strtotime($s['tanggal_masuk'])) ?></td>
                                <td><?= $s['biaya_akhir'] ? 'Rp ' . number_format($s['biaya_akhir'], 0, ',', '.') : '-' ?></td>
                                <td class="aksi-btn">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?= $s['id'] ?>"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>

                            <div class="modal fade" id="detailModal<?= $s['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-circle-info"></i> Detail Transaksi</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Pelanggan:</strong> <?= htmlspecialchars($s['nama_pelanggan']) ?></p>
                                            <p><strong>Merk HP:</strong> <?= htmlspecialchars($s['merk_hp']) ?></p>
                                            <p><strong>Teknisi:</strong> <?= $s['nama_teknisi'] ?: '-' ?></p>
                                            <p><strong>Status:</strong> <?= ucfirst($s['status']) ?></p>
                                            <p><strong>Status Pembayaran:</strong> <?= ucfirst($s['status_pembayaran'] ?? '-') ?></p>
                                            <p><strong>Tanggal Masuk:</strong> <?= date('d/m/Y', strtotime($s['tanggal_masuk'])) ?></p>
                                            <p><strong>Biaya Akhir:</strong> <?= $s['biaya_akhir'] ? 'Rp ' . number_format($s['biaya_akhir'], 0, ',', '.') : '-' ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title"><i class="fas fa-pen"></i> Ubah Status Pembayaran</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Status Pembayaran</label>
                                                    <select class="form-select" name="status_pembayaran" required>
                                                        <option value="">Pilih Status</option>
                                                        <option value="belum" <?= ($s['status_pembayaran'] == 'belum') ? 'selected' : '' ?>>Belum</option>
                                                        <option value="lunas" <?= ($s['status_pembayaran'] == 'lunas') ? 'selected' : '' ?>>Lunas</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="update_pembayaran" class="btn btn-success">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
