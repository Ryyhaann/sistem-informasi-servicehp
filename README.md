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

