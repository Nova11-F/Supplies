# 📦 Supply Management System

Supply Management System adalah sebuah aplikasi berbasis web yang dikembangkan untuk membantu proses pengelolaan persediaan dan aktivitas rantai pasok (supply chain) secara terkomputerisasi dan terintegrasi. Sistem ini dirancang untuk menggantikan proses pencatatan manual yang rentan terhadap kesalahan, keterlambatan, serta inkonsistensi data, sehingga seluruh informasi terkait supply dapat dikelola dengan lebih akurat, cepat, dan aman.

Aplikasi ini memungkinkan pengguna untuk mengelola data produk, supplier, transaksi pembelian, pengguna sistem, serta laporan secara terpusat dalam satu platform. Dengan adanya sistem ini, perusahaan atau organisasi dapat memantau ketersediaan stok secara real-time, mengelola hubungan dengan supplier, serta mencatat seluruh aktivitas transaksi pembelian dengan rapi dan terdokumentasi. Setiap data yang masuk akan tersimpan di dalam database dan dapat diakses kembali sesuai dengan hak akses masing-masing pengguna.

Supply Management System juga menerapkan konsep role-based access control, di mana setiap pengguna memiliki peran dan hak akses yang berbeda, seperti admin dan staff. Hal ini bertujuan untuk menjaga keamanan data serta memastikan bahwa setiap pengguna hanya dapat mengakses fitur yang sesuai dengan tanggung jawabnya. Selain itu, sistem ini dilengkapi dengan fitur laporan yang dapat digunakan sebagai bahan evaluasi dan pendukung pengambilan keputusan manajemen.

Secara keseluruhan, aplikasi ini dikembangkan dengan fokus pada kemudahan penggunaan, keamanan sistem, serta fleksibilitas pengembangan. Struktur kode yang terorganisir dan penggunaan teknologi web yang umum memungkinkan sistem ini untuk dikembangkan lebih lanjut sesuai dengan kebutuhan di masa mendatang, baik dari sisi fitur, performa, maupun integrasi dengan sistem lain.

---

## 🎯 Tujuan Pengembangan Sistem

Tujuan utama dari pengembangan Supply Management System adalah:
- Membantu pengelolaan data persediaan secara terstruktur dan terorganisir
- Mengurangi kesalahan pencatatan data stok dan transaksi
- Mempermudah pemantauan supplier dan aktivitas pembelian
- Menyediakan laporan yang dapat digunakan sebagai dasar pengambilan keputusan
- Meningkatkan efisiensi dan transparansi dalam proses supply management

---

## ✨ Fitur Utama

### 🔐 Authentication & Authorization
- Sistem login menggunakan username dan password
- Manajemen session untuk menjaga keamanan akses
- Role-based access control:
  - Admin
  - Staff
- Proteksi halaman agar tidak dapat diakses tanpa autentikasi

### 📊 Dashboard
- Ringkasan jumlah produk, supplier, dan transaksi
- Informasi stok secara umum
- Tampilan statistik data sistem
- Navigasi cepat ke menu utama

### 📦 Inventory Management
- Manajemen data produk (tambah, ubah, hapus, dan lihat)
- Pengelompokan produk berdasarkan kategori
- Pengelolaan lokasi penyimpanan (warehouse, rack, store)
- Informasi stok produk secara real-time
- Status ketersediaan produk

### 🏢 Supplier Management
- Manajemen data supplier (CRUD)
- Generate otomatis kode supplier
- Penyimpanan informasi supplier secara lengkap
- Relasi supplier dengan produk yang disediakan

### 🛒 Transaction Management
- Pencatatan Purchase Order
- Manajemen invoice pembelian
- Pencatatan pembayaran
- Riwayat transaksi pembelian

### 👥 User Management
- Manajemen akun pengguna
- Pengaturan role dan hak akses
- Activity log pengguna

### ⚙️ Settings
- Pengaturan profil pengguna
- Perubahan password
- Konfigurasi dasar sistem

### 📑 Reports
- Laporan inventory
- Laporan transaksi pembelian
- Riwayat aktivitas sistem

---

## 🛠️ Teknologi yang Digunakan

### Frontend
- HTML5
- Tailwind CSS
- JavaScript
- Boxicons

### Backend
- PHP (Native)
- MySQL

---

## 🗄️ Konfigurasi Database

File konfigurasi database berada pada:
config/database.php

Contoh konfigurasi:
$host = "localhost";
$user = "root";
$pass = "";
$db   = "supply_management";

$conn = mysqli_connect($host, $user, $pass, $db);

---

## ▶️ Cara Menjalankan Aplikasi

1. Download atau clone repository
2. Simpan project ke folder web server (htdocs / www)
3. Jalankan Apache dan MySQL
4. Pastikan database telah dikonfigurasi
5. Akses aplikasi melalui browser:
   http://localhost/nama-folder-project
6. Login menggunakan akun yang tersedia pada database

---

## 🔐 Hak Akses User

Admin:
- Akses penuh terhadap seluruh fitur sistem

Staff:
- Akses terbatas sesuai peran

---

## 👨‍💻 Pengembang

Aplikasi ini dikembangkan sebagai proyek sistem informasi berbasis web  
untuk keperluan pembelajaran dan pengembangan sistem.
- Asyraf Almuzaki (24051204081)
- Jonathan (24051204087)
- Firman Nova Prayoga (24051204088)
- Fawwaz Baghiz Al Ghozy Dinullah (24051204094)
