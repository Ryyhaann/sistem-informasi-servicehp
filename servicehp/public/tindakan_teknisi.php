<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teknisi') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM teknisi WHERE user_id = ?");
$stmt->execute([$user_id]);
$teknisi = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error   = '';

function fetch_service_aktif($pdo, $teknisi_id) {
    $sql = "SELECT s.*, p.nama AS nama_pelanggan, p.kontak
            FROM service s
            JOIN pelanggan p ON s.pelanggan_id = p.id
            WHERE s.teknisi_id = ? AND s.status IN ('masuk','proses')
            ORDER BY s.tanggal_masuk DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teknisi_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$service_aktif = [];
$selected_service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
if ($teknisi) {
    $teknisi_id = $teknisi['id'];
    $service_aktif = fetch_service_aktif($pdo, $teknisi_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id      = (int)($_POST['service_id'] ?? 0);
    $deskripsi       = trim($_POST['deskripsi'] ?? '');
    $sparepart       = trim($_POST['sparepart'] ?? '');
    $biaya_sparepart = (float)($_POST['biaya_sparepart'] ?? 0);
    $biaya_akhir     = (float)($_POST['biaya_akhir'] ?? 0);
    $status_sel      = $_POST['status'] ?? 'proses';

    if (!$teknisi) {
        $error = 'Akun teknisi tidak valid.';
    } elseif ($service_id <= 0 || $deskripsi === '') {
        $error = 'Service dan deskripsi tindakan wajib diisi!';
    } else {
        try {
            $pdo->beginTransaction();

            $insert = $pdo->prepare("
                INSERT INTO tindakan (service_id, teknisi_id, deskripsi, sparepart, biaya_sparepart, tanggal) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insert->execute([$service_id, $teknisi_id, $deskripsi, $sparepart, $biaya_sparepart]);

            $stmt = $pdo->prepare("SELECT status FROM service WHERE id = ? FOR UPDATE");
            $stmt->execute([$service_id]);
            $current_status = $stmt->fetchColumn();

            if (!$current_status) throw new Exception("Service tidak ditemukan.");

            $new_status = ($current_status === 'masuk') ? 'proses' : $status_sel;

            $update = $pdo->prepare("
                UPDATE service 
                SET status = ?, biaya_akhir = ?, 
                    tanggal_selesai = CASE WHEN ? = 'selesai' THEN NOW() ELSE tanggal_selesai END
                WHERE id = ?
            ");
            $update->execute([$new_status, $biaya_akhir, $new_status, $service_id]);

            $pdo->commit();
            $message = "✅ Tindakan berhasil disimpan.";
            $service_aktif = fetch_service_aktif($pdo, $teknisi_id);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "❌ Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Tindakan Teknisi</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif}
    body{background:#f5f6fa;color:#333;display:flex;justify-content:center;min-height:100vh;}
    .container{width:95%;max-width:1100px;margin:40px 0;}
    .header{
        background:linear-gradient(90deg,#2563eb,#1e40af);
        color:white;
        padding:20px 30px;
        border-radius:15px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        box-shadow:0 4px 12px rgba(0,0,0,0.1);
    }
    .header h1{font-size:1.5rem;font-weight:600;}
    .header p{opacity:0.9;font-size:0.9rem;}
    .header-actions a{
        background:white;
        color:#1e3a8a;
        font-weight:600;
        text-decoration:none;
        padding:8px 14px;
        border-radius:8px;
        margin-left:8px;
        transition:0.3s;
    }
    .header-actions a:hover{background:#e0e7ff;}

    .alert{
        margin-top:20px;
        padding:12px 16px;
        border-radius:8px;
        font-weight:500;
        display:flex;
        align-items:center;
        gap:8px;
        box-shadow:0 3px 6px rgba(0,0,0,0.05);
    }
    .alert.success{background:#dcfce7;color:#166534;}
    .alert.error{background:#fee2e2;color:#991b1b;}

    .card{
        background:white;
        border-radius:15px;
        margin-top:30px;
        box-shadow:0 4px 12px rgba(0,0,0,0.08);
        overflow:hidden;
    }
    .card-header{
        background:#1e3a8a;
        color:white;
        padding:15px 25px;
        font-weight:600;
        display:flex;
        align-items:center;
        gap:10px;
    }
    .card-body{padding:25px;}
    .empty-state{text-align:center;color:#6b7280;padding:40px 0;}
    .empty-state i{font-size:3rem;margin-bottom:10px;color:#9ca3af;}

    table{width:100%;border-collapse:collapse;}
    th,td{padding:12px 16px;border-bottom:1px solid #e5e7eb;text-align:left;}
    th{background:#f9fafb;font-weight:600;color:#374151;}
    tr:hover td{background:#f1f5f9;}
    .status-badge{padding:6px 10px;border-radius:6px;font-size:0.85rem;font-weight:600;text-transform:capitalize;}
    .status-masuk{background:#dbeafe;color:#1d4ed8;}
    .status-proses{background:#fef3c7;color:#b45309;}
    .status-selesai{background:#dcfce7;color:#15803d;}

    .form-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
        gap:16px;
        margin-top:10px;
    }
    .form-group{display:flex;flex-direction:column;}
    .form-group label{font-weight:600;margin-bottom:6px;color:#374151;}
    .form-group input,.form-group select,.form-group textarea{
        border:1px solid #d1d5db;
        border-radius:8px;
        padding:10px;
        font-size:14px;
        transition:border-color .2s,box-shadow .2s;
    }
    .form-group input:focus,.form-group select:focus,.form-group textarea:focus{
        border-color:#2563eb;
        box-shadow:0 0 0 2px rgba(37,99,235,0.2);
        outline:none;
    }
    textarea{resize:vertical;}
    .full-width{grid-column:1/-1;}
    .form-actions{grid-column:1/-1;display:flex;justify-content:flex-end;}
    .btn-primary{
        background:#2563eb;
        color:white;
        padding:10px 18px;
        border:none;
        border-radius:8px;
        cursor:pointer;
        font-weight:600;
        display:inline-flex;
        align-items:center;
        gap:6px;
        transition:0.3s;
    }
    .btn-primary:hover{background:#1e40af;}
    .table-container{overflow-x:auto;}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fas fa-tools"></i> Update Tindakan Teknisi</h1>
            <p>Selamat datang, <b><?= htmlspecialchars($_SESSION['nama'] ?? 'Teknisi') ?></b></p>
        </div>
        <div class="header-actions">
            <a href="dashboard_teknisi.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a href="logout.php" style="background:#dc2626;color:white;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if ($message): ?><div class="alert success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header"><i class="fas fa-list"></i> Service Aktif</div>
        <div class="card-body">
            <?php if (!$service_aktif): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>Tidak ada service aktif</h3>
                    <p>Belum ada service yang perlu ditangani</p>
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
                                <th>Estimasi Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_aktif as $i => $s): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><strong><?= htmlspecialchars($s['nama_pelanggan']) ?></strong><br><small><?= htmlspecialchars($s['kontak']) ?></small></td>
                                <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                <td><?= htmlspecialchars($s['kerusakan']) ?></td>
                                <td><span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                                <td><?= 'Rp '.number_format($s['estimasi_biaya'],0,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="margin-top:20px;">
                    <div class="card-header"><i class="fas fa-pen"></i> Tambahkan / Update Tindakan</div>
                    <div class="card-body">
                        <form method="post" class="form-grid">
                            <div class="form-group">
                                <label for="service_id">Pilih Service</label>
                                <select id="service_id" name="service_id" required>
                                    <option value="">-- Pilih Service --</option>
                                    <?php foreach ($service_aktif as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= $selected_service_id == $s['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['nama_pelanggan']) ?> - <?= htmlspecialchars($s['merk_hp']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="deskripsi">Deskripsi Tindakan</label>
                                <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Tuliskan detail tindakan perbaikan..." required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="sparepart">Sparepart</label>
                                <input id="sparepart" name="sparepart" type="text" placeholder="Nama sparepart jika ada">
                            </div>

                            <div class="form-group">
                                <label for="biaya_sparepart">Biaya Sparepart</label>
                                <input id="biaya_sparepart" name="biaya_sparepart" type="number" min="0" value="0">
                            </div>

                            <div class="form-group">
                                <label for="biaya_akhir">Biaya Akhir</label>
                                <input id="biaya_akhir" name="biaya_akhir" type="number" min="0" placeholder="Masukkan biaya akhir">
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="proses">Proses</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Simpan Tindakan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
