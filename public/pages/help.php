<?php
// Include Database Configuration
require_once __DIR__ . '/../../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_message = '';
$error_message = '';

// Handle Bug Report Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bug'])) {
    $user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 if not set
    $subject = trim($_POST['subject']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    
    // Insert bug report to database (you can create a bug_reports table)
    // For now, we'll just show success message
    
    if (!empty($subject) && !empty($description)) {
        $success_message = "Laporan masalah berhasil dikirim! Tim IT akan segera menindaklanjuti.";
        
        // Optional: Insert to database
        // $stmt = $conn->prepare("INSERT INTO bug_reports (user_id, subject, category, description, created_at) VALUES (?, ?, ?, ?, NOW())");
        // $stmt->bind_param("isss", $user_id, $subject, $category, $description);
        // $stmt->execute();
    } else {
        $error_message = "Mohon lengkapi semua field!";
    }
}

?>

<!-- Success/Error Messages -->
<?php if ($success_message): ?>
<div id="successAlert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 animate-slide-down shadow-lg">
    <div class="flex items-center gap-3">
        <i class='bx bx-check-circle text-3xl'></i>
        <div>
            <p class="font-bold text-lg">Berhasil!</p>
            <p class="text-sm"><?= htmlspecialchars($success_message) ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div id="errorAlert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 animate-slide-down shadow-lg">
    <div class="flex items-center gap-3">
        <i class='bx bx-error-circle text-3xl'></i>
        <div>
            <p class="font-bold text-lg">Error!</p>
            <p class="text-sm"><?= htmlspecialchars($error_message) ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="px-8 py-2">

<div class="flex items-center justify-end">    
    <button 
        onclick="openModal('bugModal')" 
        class="bg-gray-200 text-red-600 border border-red-300 font-bold px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-red-600 hover:text-white transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-sm">
        <i class='bx bx-error-circle text-xl'></i>
        <span>Lapor Masalah</span>
    </button>
</div>

<div class="flex items-center">

<div class="w-full">
    <div class="space-y-4">
        <h3 class="font-bold text-gray-700 text-lg mb-4 border-b-2 pb-2">Pertanyaan Umum (FAQ)</h3>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all mb-3">
            <div class="flex items-start gap-4">
                <div class="bg-blue-50 p-3 rounded-lg text-[#092363]">
                    <i class='bx bxs-user-account text-2xl'></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Bagaimana jika saya lupa password?</h4>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                        Silakan hubungi Super Admin untuk melakukan reset password. Demi keamanan, fitur reset password mandiri dinonaktifkan untuk akun staff.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all mb-3">
            <div class="flex items-start gap-4">
                <div class="bg-green-50 p-3 rounded-lg text-green-600">
                    <i class='bx bxs-package text-2xl'></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Stok tidak bertambah setelah input Invoice Masuk?</h4>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                        Pastikan status invoice sudah diubah menjadi <span class="font-bold text-green-600">Paid/Completed</span>. Stok hanya akan bertambah otomatis jika transaksi dianggap selesai oleh Admin.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all mb-3">
            <div class="flex items-start gap-4">
                <div class="bg-amber-50 p-3 rounded-lg text-amber-600">
                    <i class='bx bxs-data text-2xl'></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Apakah data yang dihapus bisa dikembalikan?</h4>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                        Tidak. Data yang sudah dihapus (Hard Delete) akan hilang permanen dari database. Harap berhati-hati saat menghapus Item atau Supplier.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        
        <div class="bg-[#092363] text-white p-6 rounded-xl shadow-lg relative overflow-hidden">            
            <h3 class="font-bold text-lg mb-1 relative z-10">Butuh Bantuan Darurat?</h3>
            <p class="text-blue-200 text-sm mb-4 relative z-10">Hubungi Tim IT Support jika sistem mengalami error fatal.</p>
            
            <div class="space-y-3 relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center">
                        <i class='bx bxs-phone'></i>
                    </div>
                    <span class="font-medium">0812-9999-8888 (WhatsApp)</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center">
                        <i class='bx bxs-envelope'></i>
                    </div>
                    <span class="font-medium">support@inventory.com</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-700 mb-4">System Information</h3>
            <ul class="space-y-3 text-sm">
                <li class="flex justify-between">
                    <span class="text-gray-500">App Version</span>
                    <span class="font-bold text-[#092363]">v1.0.0 (Beta)</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-gray-500">Last Update</span>
                    <span class="font-bold text-[#092363]">21 Nov 2025</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-gray-500">Server Status</span>
                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold">Online</span>
                </li>
            </ul>
        </div>

        <a href="public/download_manual.php" class="block bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-[#092363] hover:bg-blue-50 transition-all group cursor-pointer mb-10">
            <i class='bx bxs-file-pdf text-3xl text-gray-400 group-hover:text-[#092363] mb-2'></i>
            <h4 class="font-bold text-gray-700 group-hover:text-[#092363]">Download User Manual</h4>
            <p class="text-xs text-gray-400">Format PDF (2.5 MB)</p>
        </a>
    </div>
</div>
</div>

<div id="bugModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('bugModal')"></div>
    <div class="modal-flex-container">
        
        <div class="modal-content-box border-t-4 border-red-500"> <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100">
            <div class="flex items-center gap-3">
                    <div class="bg-red-100 p-2 rounded-full text-red-600">
                        <i class='bx bxs-bug text-xl'></i>
                    </div>
                    <h3 class="text-[#092363] text-lg font-bold tracking-wide">Lapor Masalah / Bug</h3>
                </div>
                <button onclick="closeModal('bugModal')" class="text-gray-400 hover:text-red-500 transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4" id="bugReportForm">
                
                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Judul Masalah</label>
                    <input type="text" name="subject" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none" placeholder="Contoh: Tidak bisa export PDF" required>
                </div>

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Kategori Error</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-red-500 outline-none cursor-pointer">
                        <option value="login">Masalah Login / Akun</option>
                        <option value="data">Data Tidak Sesuai</option>
                        <option value="feature">Fitur Tidak Berjalan</option>
                        <option value="ui">Tampilan Berantakan</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Deskripsi Detail</label>
                    <textarea name="description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none resize-none" placeholder="Jelaskan langkah-langkah yang menyebabkan error..." required></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('bugModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-red-500 hover:bg-gray-300 hover:text-red-500 transform hover:-translate-y-0.5 transition-all">Batal</button>
                    <button type="submit" name=""
                    class="px-5 py-2 rounded-lg text-sm font-bold text-red-600 bg-white hover:bg-red-500 hover:text-white shadow-lg transform hover:-translate-y-0.5 transition-all">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>