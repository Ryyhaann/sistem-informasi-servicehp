<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama'] ?? '');
    $kontak = trim($_POST['kontak'] ?? '');
    
    if ($username === '' || $password === '' || $nama === '' || $kontak === '') {
        $error = 'Semua field harus diisi!';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            try {
                $pdo->beginTransaction();
                
              
                $stmt = $pdo->prepare('INSERT INTO users (username, password, role, nama, kontak) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$username, $password, 'pelanggan', $nama, $kontak]);
                $user_id = $pdo->lastInsertId();
                
             
                $stmt = $pdo->prepare('INSERT INTO pelanggan (user_id, nama, kontak) VALUES (?, ?, ?)');
                $stmt->execute([$user_id, $nama, $kontak]);
                
                $pdo->commit();
                $message = 'Akun pelanggan berhasil ditambahkan!';
           
                $_POST = array();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tambah Akun Pelanggan - Service HP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root{
  --bg:#f4f7fb;
  --card:#ffffff;
  --primary:#2563eb;
  --muted:#6b7280;
  --success:#10b981;
  --danger:#ef4444;
  --radius:12px;
  --shadow: 0 10px 30px rgba(16,24,40,0.06);
  --glass: rgba(255,255,255,0.7);
}
*{box-sizing:border-box}
body{
  margin:0;
  background: linear-gradient(180deg,#eef2ff 0%, #f4f7fb 100%);
  font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial;
  color:#0f172a;
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
  padding:36px 18px;
  min-height:100vh;
  display:flex;
  align-items:flex-start;
  justify-content:center;
}

.wrapper{
  width:100%;
  max-width:760px;
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
  border: 1px solid rgba(15,23,42,0.04);
}

.header{
  padding:22px 28px;
  background: linear-gradient(135deg, var(--primary), #4f46e5);
  color:white;
  display:flex;
  gap:16px;
  align-items:center;
}
.header .title{
  font-size:18px;
  font-weight:700;
  margin:0;
}
.header .subtitle{
  font-weight:500;
  opacity:0.9;
  font-size:13px;
}

.body{
  padding:26px 28px;
}

.form-grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:14px 18px;
}
@media (max-width:720px){ .form-grid{ grid-template-columns: 1fr; } }

.form-group{ display:flex; flex-direction:column; gap:8px; }
label{ font-size:13px; color:var(--muted); font-weight:600; margin:0; }
.input {
  position:relative;
}
.input i{
  position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9aa4b2;
  pointer-events:none;
  font-size:14px;
}
.input input, .input select, .input textarea{
  width:100%;
  padding:11px 12px 11px 40px;
  border-radius:10px;
  border:1px solid #e6eef8;
  background: #fbfdff;
  font-size:14px;
  color:#0f172a;
  transition: box-shadow .18s ease, border-color .18s ease, transform .18s ease;
}
.input textarea{ padding-top:10px; padding-bottom:10px; min-height:96px; resize:vertical; }
.input input:focus, .input textarea:focus, .input select:focus{
  outline:none;
  border-color: rgba(37,99,235,0.9);
  box-shadow: 0 6px 20px rgba(37,99,235,0.08);
  transform: translateY(-1px);
}

.actions{
  margin-top:16px;
  display:flex;
  gap:12px;
  align-items:center;
  justify-content:center;
}
.btn{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:10px 16px;
  border-radius:10px;
  cursor:pointer;
  border:0;
  font-weight:600;
  font-size:14px;
  transition:transform .14s ease, box-shadow .14s ease, opacity .14s;
}
.btn-primary{
  background: linear-gradient(135deg,var(--primary), #4f46e5);
  color:white;
  box-shadow: 0 8px 24px rgba(37,99,235,0.12);
}
.btn-primary:hover{ transform:translateY(-3px); }
.btn-ghost{
  background:transparent; color:var(--muted); border:1px solid #e6eef8;
}
.btn-ghost:hover{ background:#f8fafc; transform:translateY(-2px); }

.alert-inline{
  padding:12px 14px;
  border-radius:10px;
  margin-bottom:14px;
  display:flex;
  align-items:center;
  gap:10px;
  font-weight:600;
}
.alert-success{ background: #ecfdf5; color: #065f46; border:1px solid rgba(16,185,129,0.12); }
.alert-error{ background: #fff1f2; color: #9f1239; border:1px solid rgba(239,68,68,0.08); }

.toast {
  position:fixed;
  top:20px;
  right:20px;
  z-index:1200;
  min-width:220px;
  padding:12px 14px;
  border-radius:10px;
  display:flex; gap:10px; align-items:center;
  color:white; font-weight:600;
  box-shadow:0 8px 30px rgba(2,6,23,0.12);
  transform:translateY(-4px);
  animation:slideIn .32s ease;
}
.toast.success{ background: linear-gradient(90deg, #10b981, #059669); }
.toast.error{ background: linear-gradient(90deg, #ef4444, #dc2626); }
@keyframes slideIn{ from { opacity:0; transform:translateY(-8px) } to { opacity:1; transform:translateY(0) } }

.hint{ font-size:13px; color:var(--muted); }

.footer-note{ margin-top:10px; font-size:13px; color:#627183; text-align:center; }
</style>
</head>
<body>

<div class="wrapper" role="main" aria-labelledby="pageTitle">
  <div class="header">
    <div style="flex:1">
      <div class="title"><i class="fas fa-user-plus" style="margin-right:10px"></i> Tambah Akun Pelanggan</div>
      <div class="subtitle">Buat akun baru untuk pelanggan dengan cepat dan aman</div>
    </div>
  </div>

  <div class="body">
    <?php if ($message): ?>
      <div class="alert-inline alert-success" role="status">
        <i class="fas fa-check-circle"></i>
        <div><?= htmlspecialchars($message) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-inline alert-error" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-grid">
        <div class="form-group">
          <label for="username">Username</label>
          <div class="input">
            <i class="fas fa-user"></i>
            <input id="username" name="username" type="text" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="username" required>
          </div>
          <div class="hint">Gunakan username unik (tanpa spasi).</div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input">
            <i class="fas fa-lock"></i>
            <input id="password" name="password" type="password" placeholder="minimal 6 karakter" required>
          </div>
          <div class="hint">Untuk keamanan produksi sebaiknya gunakan hashing (password_hash).</div>
        </div>

        <div class="form-group">
          <label for="nama">Nama Lengkap</label>
          <div class="input">
            <i class="fas fa-id-card"></i>
            <input id="nama" name="nama" type="text" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" placeholder="Nama lengkap pelanggan" required>
          </div>
        </div>

        <div class="form-group">
          <label for="kontak">Nomor Kontak</label>
          <div class="input">
            <i class="fas fa-phone"></i>
            <input id="kontak" name="kontak" type="text" value="<?= htmlspecialchars($_POST['kontak'] ?? '') ?>" placeholder="08xx-xxxx-xxxx" required>
          </div>
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-plus"></i> Tambah Akun
        </button>
        <a href="manajemen_pelanggan.php" class="btn btn-ghost btn-ghost" style="background:#fff;border:1px solid #eef2ff;color:#374151">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>

      <div class="footer-note">Pastikan data pelanggan valid sebelum menyimpan.</div>
    </form>
  </div>
</div>

<?php if ($message): ?>
  <div id="toast" class="toast success" role="status" aria-live="polite">
    <i class="fas fa-check-circle"></i>
    <div><?= htmlspecialchars($message) ?></div>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div id="toast" class="toast error" role="alert" aria-live="assertive">
    <i class="fas fa-exclamation-triangle"></i>
    <div><?= htmlspecialchars($error) ?></div>
  </div>
<?php endif; ?>

<script>
  const t = document.getElementById('toast');
  if (t) {
    setTimeout(()=> {
      t.style.transition = 'opacity .35s ease, transform .35s ease';
      t.style.opacity = '0';
      t.style.transform = 'translateY(-10px)';
      setTimeout(()=> t.remove(), 380);
    }, 3000);
  }

  (function(){
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){
      const u = document.getElementById('username').value.trim();
      const p = document.getElementById('password').value.trim();
      const n = document.getElementById('nama').value.trim();
      const k = document.getElementById('kontak').value.trim();
      if(!u || !p || !n || !k){
        e.preventDefault();
        alert('Mohon lengkapi semua field sebelum submit.');
      }
    });
  })();
</script>
</body>
</html>
