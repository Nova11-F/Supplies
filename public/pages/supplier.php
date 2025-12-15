<?php
include __DIR__ . '/../../config/database.php';

//  INSERT DATA
if (isset($_POST['create'])) {

    $name = $_POST['supplier_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    $quantity = 0;

    // Cek apakah nama kategori sudah ada
    $check = mysqli_query($conn, "SELECT id FROM suppliers WHERE name='$name'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama Supplier sudah ada!'); location.href='index.php?page=supplier';</script>";
        exit;
    }

       // Ambil nama customer dari input
    $name = $_POST['supplier_name'];

    // Hapus semua spasi
    $name_no_space = str_replace(' ', '', $name);

    // Ambil 3 huruf pertama (uppercase)
    $prefix = strtoupper(substr($name_no_space, 0, 4));

    // Buat prefix code
    $base_code = "SUP-$prefix";

    // Cek di database apakah sudah ada ID dengan prefix ini
    $sql = "SELECT supplier_code 
        FROM suppliers 
        WHERE supplier_code LIKE '$base_code-%' 
        ORDER BY supplier_code DESC 
        LIMIT 1";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Ambil kode terakhir
        $row = $result->fetch_assoc();
        $last_code = $row['supplier_code'];

        // Ambil angka di belakang
        $last_number = (int)substr($last_code, strrpos($last_code, '-') + 1);

        // Tambah 1
        $new_number = $last_number + 1;
    } else {
        // Kalau belum ada, mulai dari 1
        $new_number = 1;
    }

    $code = "$base_code-$new_number";

    mysqli_query($conn, "INSERT INTO suppliers (supplier_code, name, email, phone, address, status)
        VALUES ('$code', '$name', '$email', '$phone', '$address', '$status')");

    echo "<script>alert('Supplier berhasil ditambahkan!'); location.href='index.php?page=supplier';</script>";
    exit;
}

//  UPDATE DATA
if (isset($_POST['update'])) {

    $id      = $_POST['id'];
    $name    = trim($_POST['supplier_name']);
    $phone   = $_POST['phone'];
    $email   = $_POST['email'];
    $address = $_POST['address'];
    $status  = $_POST['status'];

    $check = mysqli_query($conn, "SELECT id FROM suppliers WHERE name='$name' AND id <> '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama Supplier sudah ada!'); location.href='index.php?page=supplier';</script>";
        exit;
    }

    mysqli_query($conn, "UPDATE suppliers SET 
        name='$name',
        phone='$phone',
        email='$email',
        address='$address',
        status='$status'
        WHERE id='$id'");

    echo "<script>alert('Supplier berhasil diupdate!'); location.href='index.php?page=supplier';</script>";
    exit;


    // Ambil data lama dari database
    $result = mysqli_query($conn, "SELECT name, supplier_code FROM suppliers WHERE id='$id'");
    $old = mysqli_fetch_assoc($result);

    // Jika nama berubah, buat code baru
    if ($name !== $old['name']) {
        $name_no_space = str_replace(' ', '', $name);
        $new_code = "SUP-" . strtoupper(substr($name_no_space, 0, 4));
    } else {
        $new_code = $old['supplier_code']; // tetap kode lama
    }

    mysqli_query($conn, "UPDATE suppliers SET 
                        supplier_code='$new_code',
                        name='$name',
                        phone='$phone',
                        email='$email',
                        address='$address',
                        status='$status'
                        WHERE id='$id'");

    echo "<script>alert('Supplier berhasil diupdate!'); location.href='index.php?page=supplier';</script>";
    exit;
}

//  DELETE DATA
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Cek apakah supplier sudah dipakai di transaksi (purchase_orders)
    $check = mysqli_query(
        $conn,
        "SELECT id FROM purchase_orders WHERE supplier_id = '$id' LIMIT 1"
    );

    if (mysqli_num_rows($check) > 0) {
        // SUDAH ADA TRANSAKSI → TIDAK BOLEH DIHAPUS
        echo "<script>
            alert('Supplier tidak bisa dihapus karena sudah memiliki transaksi!');
            location.href='index.php?page=supplier';
        </script>";
        exit;
    }

    // BELUM ADA TRANSAKSI → BOLEH DIHAPUS
    mysqli_query($conn, "DELETE FROM suppliers WHERE id='$id'");

    echo "<script>
        alert('Supplier berhasil dihapus!');
        location.href='index.php?page=supplier';
    </script>";
    exit;
}

// Ambil search keyword dari GET parameter
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query untuk search customers
$where_clause = '';

if (!empty($search_keyword)) {
    $search = mysqli_real_escape_string($conn, $search_keyword);
    $where_clause = "WHERE name LIKE '%$search%'";
}

// GET ALL DATA
$query = "SELECT * FROM suppliers $where_clause ORDER BY name ASC";
$result = mysqli_query($conn, $query);
?>

<div class="px-8 py-2">
    <!-- Create Supplier -->
    <div class="flex items-center justify-end gap-4 mb-4">
        <div class="flex items-center gap-3 bg-white px-3 py-2 rounded-lg shadow-lg w-72">
            <i class='bx bx-search text-xl text-gray-500'></i>
            <input type="text"
                   id="searchSupplier" 
                   placeholder="Search supplier..."
                   value="<?= htmlspecialchars($search_keyword) ?>"
                   class="w-full ml-2 focus:outline-none">
        </div>
        <?php if ($isAdmin): ?>
            <button
                onclick="openModal('supplierModal')"
                class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
                <i class='bx bx-plus text-lg'></i>
                <span>Tambah Supplier</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Table Supplier -->
    <div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
        <table class="min-w-full">
            <thead class="bg-[#092363] text-white">
                <tr>
                    <th class="px-4 py-3 text-center">No</th>
                    <th class="px-4 py-3 text-center">Supplier ID</th>
                    <th class="px-4 py-3 text-center">Supplier Name</th>
                    <th class="px-4 py-3 text-center">Phone</th>
                    <th class="px-4 py-3 text-center">Email</th>
                    <th class="px-4 py-3 text-center">Address</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <?php if ($isAdmin): ?>
                        <th class="px-4 py-3 text-center">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $no = 1; ?>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <!-- Contoh supplier -->
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-center text-gray-600"><?= $no++ ?></td>
                            <td class="px-4 py-3 text-center text-gray-600"><?= $row['supplier_code'] ?></td>
                            <td class="px-4 py-3 text-center font-bold text-gray-800"><?= $row['name'] ?></td>
                            <td class="px-4 py-3 text-center text-gray-600"><?= $row['phone'] ?></td>
                            <td class="px-4 py-3 text-center text-gray-600"><?= $row['email'] ?></td>
                            <td class="px-4 py-3 text-center text-gray-600 truncate max-w-xs"><?= $row['address'] ?></td>

                            <td class="px-4 py-3 text-center">
                                <?php if ($row['status'] == "active"): ?>
                                    <span class="bg-green-100 text-green-700 text-xs px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm">Active</span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <?php if ($isAdmin): ?>
                                <td class="px-4 py-2 flex gap-2 items-center justify-center">
                                    <button
                                        onclick="openEditModal('editSupplierModal', this)"
                                        data-id="<?= $row['id'] ?>"
                                        data-supplier_code="<?= $row['supplier_code'] ?>"
                                        data-supplier_name="<?= $row['name'] ?>"
                                        data-phone="<?= $row["phone"] ?>"
                                        data-email="<?= $row['email'] ?>"
                                        data-address="<?= $row['address'] ?>"
                                        data-quantity="<?= $row['total_products'] ?>"
                                        data-status="<?= $row['status'] ?>"
                                        class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                        <i class='bx bxs-edit'></i>
                                    </button>
                                    <button onclick="if(confirm('Are you sure you want to delete this supplier?')) { 
                                    window.location.href='index.php?page=supplier&delete=<?= $row['id'] ?>'; }"
                                        class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                        <i class='bx bxs-trash'></i>
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 9 : 8 ?>" class="px-4 py-8 text-center text-gray-500">
                            <i class='bx bx-buildings text-5xl mb-2 opacity-50'></i>
                            <p class="font-semibold">Tidak ada Supplier yang tersedia</p>
                            <p class="text-sm mt-1">Buat Supplier terlebih dahulu</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <!-- Loop supplier dari database -->
            </tbody>
        </table>
    </div>


    <!-- Next menu -->
    <div class="flex justify-between items-center px-6 mb-6">
        <span class="text-sm text-gray-500">Showing 1 to 10 of 150 entries</span>
        <div class="flex gap-1">
            <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Prev</button>
            <button class="px-3 py-1 rounded border border-[#092363] bg-[#092363] text-white text-sm">1</button>
            <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white text-sm">2</button>
            <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Next</button>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchSupplier');

    // Search saat tekan Enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    // Clear search saat tekan ESC
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('search');
            currentUrl.searchParams.set('page', 'supplier');
            window.location.href = currentUrl.toString();
        }
    });

    function performSearch() {
        const searchValue = searchInput.value.trim();
        
        // Redirect ke halaman dengan parameter search
        const currentUrl = new URL(window.location.href);
        
        if (searchValue !== '') {
            currentUrl.searchParams.set('search', searchValue);
        } else {
            currentUrl.searchParams.delete('search');
        }
        
        // Tetap di halaman customer
        currentUrl.searchParams.set('page', 'supplier');
        
        window.location.href = currentUrl.toString();
    }
});
</script>

<!-- Modal Tambah Supplier -->
<div id="supplierModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('supplierModal')"></div>
    <div class="modal-flex-container">

        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Tambah Supplier Baru</h3>
                <button onclick="closeModal('supplierModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Perusahaan / Supplier</label>
                    <input type="text" name="supplier_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all" placeholder="PT. Contoh Sejahtera" required>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Email Bisnis</label>
                        <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all" placeholder="email@company.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">No. Telepon</label>
                        <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all" placeholder="0812...">
                    </div>
                </div>

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Alamat Lengkap</label>
                    <textarea name="address" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all resize-none" placeholder="Jl. Nama Jalan No. X, Kota..."></textarea>
                </div>

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status Kerjasama</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="active">Active (Aktif)</option>
                        <option value="inactive">Inactive (Tidak Aktif)</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('supplierModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Supplier -->
<div id="editSupplierModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editSupplierModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Supplier</h3>
                <button onclick="closeModal('editSupplierModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Supplier ID</label>
                    <input type="text" name="supplier_code" id="edit_supplier_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 outline-none bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Perusahaan / Supplier</label>
                    <input type="text" name="supplier_name" id="edit_supplier_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none transition-all" required>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Email Bisnis</label>
                        <input type="email" name="email" id="edit_email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">No. Telepon</label>
                        <input type="text" name="phone" id="edit_phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none transition-all">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Alamat Lengkap</label>
                    <textarea name="address" id="edit_address" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none transition-all resize-none"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status Kerja Sama</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('editSupplierModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View Supplier -->
<div id="viewSupplierModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('viewSupplierModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide uppercase">Supplier Detail</h3>
                <button onclick="closeModal('viewSupplierModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <div class="p-6 space-y-4">

                <div class="grid grid-cols-[180px_1fr] gap-y-2 text-xs font-bold text-gray-500 uppercase tracking-wide">

                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Nama Perusahaan / Supplier</span>
                    <p name="supplier_name" id="view_supplier_name" class="text-xs font-bold text-gray-500 uppercase tracking-wide">:</p>

                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Phone</span>
                    <p name="supplier_phone" id="view_supplier_phone" class="text-xs font-bold text-gray-500 uppercase tracking-wide">:</p>

                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Email</span>
                    <p name="supplier_email" id="view_supplier_email" class="text-xs font-bold text-gray-500 uppercase tracking-wide">:</p>

                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Addres</span>
                    <p name="supplier_address" id="view_supplier_address" class="text-xs font-bold text-gray-500 uppercase tracking-wide">:</p>

                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Status</span>
                    <p name="supplier_status" id="view_supplier_status" class="text-xs font-bold text-gray-500 uppercase tracking-wide">:</p>
                </div>

                <div class="border-b-2 border-t-2 border-gray-200 py-2">
                    <h1 class="flex justify-center items-center uppercase tracking-wide text-xl font-bold text-gray-500">Product Provided By Supplier</h1>
                </div>

                <div class="overflow-x-auto bg-white shadow-md rounded-lg mb-6">
                    <table class="min-w-full">
                        <thead class="bg-[#092363] text-white">
                            <tr>
                                <th class="px-4 py-3 text-center">Product</th>
                                <th class="px-4 py-3 text-center">Category</th>
                                <th class="px-4 py-3 text-center">Units Price</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-center text-gray-800">Laptop</td>
                                <td class="px-4 py-3 text-center text-gray-600">Elektronik</td>
                                <td class="px-4 py-3 text-center text-red-600">$2500.0</td>
                                <td class="px-4 py-3 text-center text-green-600">Paid</td>
                            </tr>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-center text-gray-800">Laptop</td>
                                <td class="px-4 py-3 text-center text-gray-600">Elektronik</td>
                                <td class="px-4 py-3 text-center text-red-600">$2500.0</td>
                                <td class="px-4 py-3 text-center text-green-600">Paid</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('viewSupplierModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Ok</button>
                </div>
            </div>
        </div>
    </div>
</div>