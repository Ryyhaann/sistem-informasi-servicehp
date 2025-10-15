<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_teknisi'])) {
    $service_id = $_POST['service_id'];
    $teknisi_id = $_POST['teknisi_id'];
    
    if (empty($teknisi_id)) {
        $error = 'Pilih teknisi untuk ditugaskan!';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE service SET teknisi_id = ? WHERE id = ?');
            $stmt->execute([$teknisi_id, $service_id]);
            $message = 'Teknisi berhasil ditugaskan!';
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$service = $pdo->query('
    SELECT s.*, p.nama as nama_pelanggan, p.kontak, t.nama as nama_teknisi 
    FROM service s 
    LEFT JOIN pelanggan p ON s.pelanggan_id = p.id 
    LEFT JOIN teknisi t ON s.teknisi_id = t.id 
    ORDER BY s.tanggal_masuk DESC
')->fetchAll();

$teknisi = $pdo->query('SELECT * FROM teknisi ORDER BY nama')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Service - Service HP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f3f4f6;
    margin: 0;
    color: #1f2937;
}

.header {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
.header h1 {
    margin: 0;
    font-size: 22px;
}
.header p {
    font-weight: 300;
    margin: 4px 0 0;
    font-size: 14px;
}
.header-actions a {
    background: rgba(255,255,255,0.15);
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    margin-left: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}
.header-actions a:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-1px);
}

.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}

.alert {
    position: fixed;
    top: 25px;
    right: 25px;
    padding: 14px 22px;
    border-radius: 10px;
    color: white;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    z-index: 1000;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    animation: slideIn 0.5s ease;
}
.alert-success { background: #16a34a; }
.alert-error { background: #dc2626; }
.alert i { font-size: 18px; }
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 25px;
}
.stat-card {
    background: white;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: 0.3s;
}
.stat-card:hover {
    transform: translateY(-3px);
}
.stat-card h3 {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 8px;
}
.stat-card .number {
    font-size: 26px;
    font-weight: 700;
    color: #4f46e5;
    margin-top: 6px;
}

.card {
    background: white;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #4f46e5;
    display: flex;
    align-items: center;
    gap: 8px;
}
.table-container {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}
tr:hover {
    background: #f3f4f6;
}
td {
    font-size: 14px;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}
.status-masuk { background: #dbeafe; color: #1e40af; }
.status-proses { background: #fef9c3; color: #92400e; }
.status-selesai { background: #dcfce7; color: #166534; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: 0.3s;
}
.btn-primary { background: #4f46e5; color: white; }
.btn-primary:hover { background: #4338ca; }
.btn-secondary { background: #9ca3af; color: white; }
.btn-danger { background: #ef4444; color: white; }
.btn-sm { font-size: 12px; padding: 6px 10px; }

/* Modal */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background-color: white;
    border-radius: 12px;
    width: 90%;
    max-width: 450px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    animation: fadeIn 0.4s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.modal-header {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 { margin: 0; font-size: 18px; }
.modal-body { padding: 24px; }
.modal-footer {
    padding: 16px 24px;
    background: #f9fafb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.close {
    font-size: 22px;
    cursor: pointer;
    color: white;
}

@media (max-width: 640px) {
    th, td { padding: 10px 12px; font-size: 13px; }
}
</style>
</head>

<body>
<?php if ($message): ?>
    <div class="alert alert-success" id="notif">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error" id="notif">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="header">
    <div>
        <h1><i class="fas fa-clipboard-list"></i> Manajemen Service</h1>
        <p>Kelola dan tugaskan service kepada teknisi</p>
    </div>
    <div class="header-actions">
        <a href="manajemen_teknisi.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <div class="stats-grid">
        <div class="stat-card"><h3><i class="fas fa-clipboard-list"></i> Total Service</h3><div class="number"><?= count($service) ?></div></div>
        <div class="stat-card"><h3><i class="fas fa-clock"></i> Belum Ditugaskan</h3><div class="number"><?= count(array_filter($service, fn($s)=>empty($s['teknisi_id']))) ?></div></div>
        <div class="stat-card"><h3><i class="fas fa-tools"></i> Sedang Diproses</h3><div class="number"><?= count(array_filter($service, fn($s)=>$s['status']=='proses')) ?></div></div>
        <div class="stat-card"><h3><i class="fas fa-check-circle"></i> Selesai</h3><div class="number"><?= count(array_filter($service, fn($s)=>$s['status']=='selesai')) ?></div></div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-list"></i> Daftar Service</div>
        <div class="card-body">
            <?php if (empty($service)): ?>
                <div style="text-align:center; padding:40px;">
                    <i class="fas fa-clipboard-list" style="font-size:48px; color:#9ca3af;"></i>
                    <h3 style="margin-top:10px;">Belum ada data service</h3>
                    <p>Mulai dengan menerima service dari pelanggan</p>
                    <a href="penerimaan_service.php" class="btn btn-primary"><i class="fas fa-plus"></i> Terima Service</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th><th>Pelanggan</th><th>Merk HP</th><th>Kerusakan</th>
                                <th>Status</th><th>Teknisi</th><th>Tanggal Masuk</th><th>Estimasi Biaya</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($service as $index => $s): ?>
                            <tr>
                                <td><?= $index+1 ?></td>
                                <td><strong><?= htmlspecialchars($s['nama_pelanggan']) ?></strong><br><small><?= htmlspecialchars($s['kontak']) ?></small></td>
                                <td><?= htmlspecialchars($s['merk_hp']) ?></td>
                                <td><?= htmlspecialchars($s['kerusakan']) ?></td>
                                <td>
                                    <?php
                                        $status_map = [
                                            'masuk' => ['status-masuk','Masuk'],
                                            'proses' => ['status-proses','Proses'],
                                            'selesai' => ['status-selesai','Selesai']
                                        ];
                                        [$class,$text] = $status_map[$s['status']] ?? ['','-'];
                                    ?>
                                    <span class="status-badge <?= $class ?>"><?= $text ?></span>
                                </td>
                                <td>
                                    <?php if ($s['nama_teknisi']): ?>
                                        <span style="color:#10b981; font-weight:600;"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($s['nama_teknisi']) ?></span>
                                    <?php else: ?>
                                        <span style="color:#ef4444; font-weight:600;"><i class="fas fa-exclamation-triangle"></i> Belum ditugaskan</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($s['tanggal_masuk'])) ?></td>
                                <td>Rp <?= number_format($s['estimasi_biaya'],0,',','.') ?></td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <?php if (empty($s['teknisi_id']) && $s['status']!='selesai'): ?>
                                            <button onclick="showAssignForm(<?= $s['id'] ?>)" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Tugaskan</button>
                                        <?php endif; ?>
                                        <a href="riwayat_pelanggan_admin.php?pelanggan_id=<?= $s['pelanggan_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Detail</a>
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

<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Tugaskan Teknisi</h3>
            <span class="close" onclick="closeAssignModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="service_id" id="service_id">
            <input type="hidden" name="assign_teknisi" value="1">
            <div class="modal-body">
                <label for="teknisi_id" style="font-weight:500; margin-bottom:6px;">Pilih Teknisi</label>
                <select id="teknisi_id" name="teknisi_id" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #d1d5db;">
                    <option value="">-- Pilih Teknisi --</option>
                    <?php foreach ($teknisi as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeAssignModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Tugaskan</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAssignForm(serviceId) {
    document.getElementById('service_id').value = serviceId;
    document.getElementById('assignModal').style.display = 'flex';
}
function closeAssignModal() {
    document.getElementById('assignModal').style.display = 'none';
}
window.onclick = e => {
    const modal = document.getElementById('assignModal');
    if (e.target === modal) closeAssignModal();
};
setTimeout(()=>{
    const notif=document.getElementById('notif');
    if(notif){
        notif.style.transition='0.5s';
        notif.style.opacity='0';
        notif.style.transform='translateX(100%)';
        setTimeout(()=>notif.remove(),500);
    }
},3000);
</script>
</body>
</html>
