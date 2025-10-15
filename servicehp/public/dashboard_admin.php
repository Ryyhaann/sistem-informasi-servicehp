<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$masuk = $pdo->query('SELECT COUNT(*) FROM service WHERE status = "masuk"')->fetchColumn();
$proses = $pdo->query('SELECT COUNT(*) FROM service WHERE status = "proses"')->fetchColumn();
$selesai = $pdo->query('SELECT COUNT(*) FROM service WHERE status = "selesai"')->fetchColumn();
$belum_bayar = $pdo->query('SELECT COUNT(*) FROM service WHERE status_pembayaran = "belum"')->fetchColumn();
$pendapatan = $pdo->query('SELECT SUM(biaya_akhir) FROM service WHERE status = "selesai"')->fetchColumn();

$teknisi = $pdo->query('SELECT t.nama, COUNT(s.id) as jumlah FROM teknisi t LEFT JOIN service s ON t.id = s.teknisi_id AND s.status = "selesai" GROUP BY t.id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Admin - Service HP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{
  --bg1:#f5f7ff;
  --card:#ffffff;
  --grad1:#6366f1;
  --grad2:#8b5cf6;
  --success:#10b981;
  --danger:#ef4444;
  --warning:#f59e0b;
  --text-dark:#1e293b;
  --text-muted:#64748b;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
body{
  background:linear-gradient(135deg,#eef2ff,#e0e7ff);
  min-height:100vh;
  padding:30px;
  color:var(--text-dark);
}
.container{
  max-width:1250px;
  margin:auto;
  background:rgba(255,255,255,0.85);
  backdrop-filter:blur(15px);
  border-radius:20px;
  box-shadow:0 20px 50px rgba(99,102,241,0.15);
  overflow:hidden;
  border:1px solid rgba(255,255,255,0.3);
}

.header{
  background:linear-gradient(135deg,var(--grad1),var(--grad2));
  padding:28px 32px;
  color:white;
  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-wrap:wrap;
}
.header h1{
  font-size:26px;
  font-weight:700;
  display:flex;
  align-items:center;
  gap:10px;
}
.header p{
  font-size:15px;
  opacity:0.9;
}
.header-actions a{
  background:white;
  color:var(--danger);
  border-radius:10px;
  padding:10px 18px;
  text-decoration:none;
  font-weight:600;
  transition:0.25s;
}
.header-actions a:hover{
  background:var(--danger);
  color:white;
  transform:translateY(-2px);
}

.content{
  padding:30px;
}

.welcome{
  background:linear-gradient(90deg,#eef2ff,#f5f3ff);
  border-left:5px solid var(--grad1);
  border-radius:14px;
  padding:22px 26px;
  margin-bottom:28px;
  box-shadow:0 2px 12px rgba(0,0,0,0.05);
}
.welcome h2{
  font-size:20px;
  color:var(--text-dark);
  margin-bottom:6px;
}
.welcome p{color:var(--text-muted);}

.stats-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
  gap:18px;
  margin-bottom:35px;
}
.stat{
  background:white;
  border-radius:16px;
  padding:22px;
  box-shadow:0 4px 20px rgba(99,102,241,0.05);
  position:relative;
  overflow:hidden;
  transition:0.3s;
}
.stat:hover{transform:translateY(-5px);box-shadow:0 10px 25px rgba(99,102,241,0.15);}
.stat::after{
  content:"";
  position:absolute;
  right:-25px;
  top:-25px;
  width:90px;
  height:90px;
  border-radius:50%;
  opacity:0.1;
  background:linear-gradient(135deg,var(--grad1),var(--grad2));
}
.stat h3{font-size:14px;color:var(--text-muted);margin-bottom:8px;}
.stat .number{font-size:30px;font-weight:700;margin-bottom:6px;}
.stat i{font-size:24px;margin-right:6px;color:var(--grad1);}
.stat .label{font-size:13px;color:var(--text-muted);}
.stat.income .number{color:var(--success);}
.stat.unpaid .number{color:var(--danger);}
.stat.pending .number{color:var(--warning);}
.stat.processing .number{color:#3b82f6;}
.stat.completed .number{color:var(--success);}


.section{
  background:white;
  border-radius:16px;
  margin-bottom:30px;
  box-shadow:0 4px 20px rgba(0,0,0,0.05);
}
.section-header{
  padding:20px 26px;
  border-bottom:1px solid #f1f5f9;
  background:#f8fafc;
}
.section-header h3{font-size:18px;color:var(--text-dark);}
.section-content{padding:26px;}


table{width:100%;border-collapse:collapse;}
th,td{padding:14px 16px;text-align:left;font-size:14px;}
th{background:#f8fafc;color:var(--text-dark);}
td{border-bottom:1px solid #f1f5f9;color:var(--text-muted);}
tr:hover td{background:#f9fafb;}


.menu-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:20px;
}
.menu-card{
  text-decoration:none;
  background:white;
  border-radius:16px;
  padding:24px;
  border:1px solid #e5e7eb;
  box-shadow:0 3px 15px rgba(0,0,0,0.05);
  color:inherit;
  transition:0.3s;
}
.menu-card:hover{
  transform:translateY(-6px);
  border-color:var(--grad1);
  box-shadow:0 8px 25px rgba(99,102,241,0.15);
}
.menu-card i{
  font-size:32px;
  color:var(--grad1);
  margin-bottom:12px;
}
.menu-card h4{
  font-size:16px;
  color:var(--text-dark);
  margin-bottom:6px;
}
.menu-card p{font-size:14px;color:var(--text-muted);line-height:1.5;}
@media(max-width:720px){
  body{padding:15px;}
  .header{flex-direction:column;gap:12px;text-align:center;}
  .header-actions{margin-top:10px;}
}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div>
      <h1><i class="fas fa-gauge"></i> Dashboard Admin</h1>
      <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama']); ?></p>
    </div>
    <div class="header-actions">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="content">
    <div class="welcome">
      <h2><i class="fas fa-chart-line"></i> Ringkasan Operasional</h2>
      <p>Pantau status service, pendapatan, dan performa teknisi secara real-time.</p>
    </div>

    <div class="stats-grid">
      <div class="stat pending">
        <h3><i class="fas fa-inbox"></i> Service Masuk</h3>
        <div class="number"><?= $masuk ?></div>
        <div class="label">Belum dikerjakan</div>
      </div>
      <div class="stat processing">
        <h3><i class="fas fa-tools"></i> Sedang Dikerjakan</h3>
        <div class="number"><?= $proses ?></div>
        <div class="label">Dalam proses teknisi</div>
      </div>
      <div class="stat completed">
        <h3><i class="fas fa-check-circle"></i> Selesai</h3>
        <div class="number"><?= $selesai ?></div>
        <div class="label">Sudah diperbaiki</div>
      </div>
      <div class="stat unpaid">
        <h3><i class="fas fa-exclamation-triangle"></i> Belum Bayar</h3>
        <div class="number"><?= $belum_bayar ?></div>
        <div class="label">Menunggu pembayaran</div>
      </div>
      <div class="stat income">
        <h3><i class="fas fa-money-bill-wave"></i> Total Pendapatan</h3>
        <div class="number">Rp <?= number_format($pendapatan,0,',','.') ?></div>
        <div class="label">Pendapatan bersih</div>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <h3><i class="fas fa-users"></i> Performa Teknisi</h3>
      </div>
      <div class="section-content">
        <table>
          <thead>
            <tr>
              <th>Nama Teknisi</th>
              <th>Jumlah Service Selesai</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($teknisi as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['nama']) ?></td>
              <td><?= $t['jumlah'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <h3><i class="fas fa-cogs"></i> Menu Utama</h3>
      </div>
      <div class="section-content">
        <div class="menu-grid">
          <a href="manajemen_pelanggan.php" class="menu-card">
            <i class="fas fa-users"></i>
            <h4>Manajemen Pelanggan</h4>
            <p>Kelola data pelanggan, tambah, ubah, atau hapus akun pelanggan.</p>
          </a>
          <a href="penerimaan_service.php" class="menu-card">
            <i class="fas fa-clipboard-list"></i>
            <h4>Penerimaan Service</h4>
            <p>Catat layanan baru dari pelanggan dengan cepat dan mudah.</p>
          </a>
          <a href="manajemen_service.php" class="menu-card">
            <i class="fas fa-tasks"></i>
            <h4>Manajemen Service</h4>
            <p>Pantau dan atur status perbaikan dari setiap pesanan pelanggan.</p>
          </a>
          <a href="manajemen_teknisi.php" class="menu-card">
            <i class="fas fa-user-cog"></i>
            <h4>Manajemen Teknisi</h4>
            <p>Atur data teknisi, pembagian tugas, dan performa kerja.</p>
          </a>
          <a href="transaksi.php" class="menu-card">
            <i class="fas fa-credit-card"></i>
            <h4>Transaksi & Pembayaran</h4>
            <p>Proses pembayaran dan kelola catatan transaksi service.</p>
          </a>
          <a href="laporan.php" class="menu-card">
            <i class="fas fa-chart-bar"></i>
            <h4>Laporan Service</h4>
            <p>Lihat laporan dan statistik service dalam format profesional.</p>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
