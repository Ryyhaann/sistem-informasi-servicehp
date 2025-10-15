# 📱 Sistem Informasi Servis HP

Sistem Informasi Servis HP berbasis web yang dirancang untuk mempermudah proses administrasi, pemantauan, dan pelaporan layanan servis handphone.  
Aplikasi ini dibangun menggunakan **PHP Native** dengan 3 role utama: **Admin**, **Teknisi**, dan **Pelanggan**.

---

## 🚀 Fitur Utama

### 👤 Pelanggan
- Membuat permintaan servis HP baru  
- Melihat status perbaikan dan riwayat servis  
- Mengunggah bukti pembayaran (QRIS, DANA, OVO, GoPay)  
- Mencetak bukti transaksi  

### 🧑‍🔧 Teknisi
- Melihat daftar service yang masuk  
- Menambahkan tindakan servis dan sparepart  
- Mengubah status pengerjaan (Masuk → Proses → Selesai)  
- Melihat riwayat tindakan teknisi  

### 🧑‍💼 Admin
- Mengelola data pelanggan dan teknisi  
- Melihat laporan servis dan pembayaran  
- Mengekspor laporan ke **PDF / Excel**  
- Memverifikasi pembayaran  

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi |
|-----------|------------|
| **Bahasa Pemrograman** | PHP 8.x (Native) |
| **Basis Data** | MySQL / MariaDB |
| **Front-End** | HTML5, CSS3, JavaScript |
| **Styling** | Custom CSS (file: `assets/style.css`) |
| **Library Tambahan** | Font Awesome, Chart.js (opsional), PhpSpreadsheet |
| **Export Tools** | FPDF / PhpSpreadsheet |
| **Manajemen Session & Role** | Native PHP Session |

---

## 📂 Struktur Folder
📁 sistem-servis-hp/
│
├── 📁 config/
│ └── db.php
│
├── 📁 database/
│ └── servis_hp.sql
│
├── 📁 public/
│ ├── dashboard_admin.php
│ ├── kelola_teknisi.php
│ ├── kelola_pelanggan.php
│ ├── laporan_service.php
│ ├── export_laporan.php
│ ├── dashboard_teknisi.php
│ ├── daftar_service.php
│ ├── detail_service.php
│ ├── tambah_tindakan.php
│ ├── riwayat_teknisi.php
│ ├── dashboard_pelanggan.php
│ ├── tambah_service.php
│ ├── riwayat_pelanggan.php
│ ├── bukti_pembayaran.php
│ ├── login.php
│ ├── register.php
│ ├── logout.php
│ │
│ ├── 📁 assets/
│ │ ├── style.css
│
└── README.md

