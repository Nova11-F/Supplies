<?php
include __DIR__ . "/../../../config/database.php";

//  INSERT DATA
if (isset($_POST['create'])) {
    $name = $_POST['brands_name'];
    $desc = $_POST['description'];
    $status = $_POST['status'];

    // Cek apakah nama kategori sudah ada
    $check = mysqli_query($conn, "SELECT id FROM brands WHERE name='$name' AND id <> '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama Brands sudah ada!'); location.href='index.php?page=catalog&sub=brands';</script>";
        exit;
    }

// Ambil nama brand dari input
$name   = trim($_POST['brands_name']);
$desc   = $_POST['description'];
$status = $_POST['status'];

// Hapus semua spasi
$name_no_space = str_replace(' ', '', $name);

// Ambil 4 huruf pertama
$prefix = strtoupper(substr($name_no_space, 0, 4));

// Prefix dasar
$base_code = "BRN-$prefix";

// Ambil brand_code terakhir dengan prefix sama
$sql = "SELECT brand_code 
        FROM brands 
        WHERE brand_code LIKE '$base_code-%'
        ORDER BY brand_code DESC
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    // Ambil angka terakhir
    $last_number = (int) substr($row['brand_code'], strrpos($row['brand_code'], '-') + 1);
    $new_number = $last_number + 1;
} else {
    // Prefix belum ada
    $new_number = 1;
}

// Buat brand_code
$code = "$base_code-$new_number";

// Insert ke database
mysqli_query($conn, "INSERT INTO brands 
    (brand_code, name, description, status)
    VALUES 
    ('$code', '$name', '$desc', '$status')");

echo "<script>
    alert('Brand berhasil ditambahkan!');
    location.href='index.php?page=catalog&sub=brands';
</script>";
exit;
}

//  UPDATE DATA
if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['brands_name'];
    $desc = $_POST['description'];
    $status = $_POST['status'];

    $name = trim($_POST['brands_name']);

    // Cek apakah nama kategori sudah ada selain id ini
    $check = mysqli_query($conn, "SELECT id FROM brands WHERE name='$name' AND id <> '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama Brands sudah ada!'); location.href='index.php?page=catalog&sub=brands';</script>";
        exit;
    }

    // Ambil data lama dari database
    $result = mysqli_query($conn, "SELECT name, brand_code FROM brands WHERE id='$id'");
    $old = mysqli_fetch_assoc($result);

    // Jika nama berubah, buat code baru
    if ($name !== $old['name']) {
        $name_no_space = str_replace(' ', '', $name);
        $new_code = "BRN-" . strtoupper(substr($name_no_space, 0, 4));
    } else {
        $new_code = $old['brand_code']; // tetap kode lama
    }

    mysqli_query($conn, "UPDATE brands SET 
                        brand_code='$new_code',
                        name='$name',
                        description='$desc',
                        status='$status'
                        WHERE id='$id'");

    echo "<script>alert('Brands berhasil diupdate!'); location.href='index.php?page=catalog&sub=brands';</script>";
    exit;
}

//  DELETE DATA
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM brands WHERE id='$id'");
    echo "<script>alert('Brands berhasil dihapus!'); location.href='index.php?page=catalog&sub=brands';</script>";
    exit;
}

//  GET ALL DATA
$result = mysqli_query($conn, "SELECT * FROM brands ORDER BY id ASC");
?>

<!-- Create Brands -->
<div class="flex items-center justify-between mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">Brands Management</h1>
    <?php if ($isAdmin): ?>
        <button
            onclick="openModal('brandsModal')"
            class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
            <i class='bx bx-plus text-lg'></i>
            <span>Tambah Brands</span>
        </button>
    <?php endif; ?>
</div>

<!-- Table Brands -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center w-16">No</th>
                <th class="px-4 py-3 text-center">Brands ID</th>
                <th class="px-4 py-3 text-center">Brands</th>
                <th class="px-4 py-3 text-center">Description</th>
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
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center text-gray-500"><?= $no++ ?></td>
                    <td class="px-4 py-3 text-gray-600 font-medium"><?= $row['brand_code'] ?></td>
                    <td class="px-4 py-3 font-bold text-gray-800"><?= $row['name'] ?></td>
                    <td class="px-4 py-3 text-gray-600 text-sm"><?= $row['description'] ?></td>

                    <td class="px-4 py-3 text-center">
                        <?php if ($row['status'] == "active"): ?>
                            <span class="bg-green-100 text-green-700 text-xs px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm">Active</span>
                        <?php else: ?>
                            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm">Inactive</span>
                        <?php endif; ?>
                    </td>

                    <?php if ($isAdmin): ?>
                        <td class="px-4 py-3 flex gap-2 justify-center">
                            <button
                                onclick="openEditModal('editBrandsModal', this)"
                                data-id="<?= $row['id'] ?>"
                                data-brands_code="<?= $row['brand_code'] ?>"
                                data-brands_name="<?= $row['name'] ?>"
                                data-description="<?= $row['description'] ?>"
                                data-status="<?= $row['status'] ?>"
                                class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                <i class='bx bxs-edit text-lg'></i></button>
                            <button onclick="if(confirm('Are you sure you want to delete this brand?')) { 
                                    window.location.href='index.php?page=catalog&sub=brands&delete=<?= $row['id'] ?>'; 
                                    } "
                                class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                <i class='bx bxs-trash text-lg'></i></button>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
                        <?php else: ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-purchase-tag text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada Brands yang tersedia</p>
                        <p class="text-sm mt-1">Buat Brands terlebih dahulu</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Next menu -->
<div class="flex justify-between items-center px-6 mb-10">
    <span class="text-sm text-gray-500">Showing 1 to 10 of 150 entries</span>
    <div class="flex gap-1">
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Prev</button>
        <button class="px-3 py-1 rounded border border-[#092363] bg-[#092363] text-white text-sm">1</button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white text-sm">2</button>
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Next</button>
    </div>
</div>

<!-- Modal Tambah Brands -->
<div id="brandsModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('brandsModal')"></div>
    <div class="modal-flex-container">

        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Tambah Brands Baru</h3>
                <button onclick="closeModal('brandsModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Brands</label>
                    <input type="text" name="brands_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all" placeholder="Contoh: Logitech" required>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all resize-none" placeholder="Penjelasan singkat Brands..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="active">Active (Aktif)</option>
                        <option value="inactive">Inactive (Tidak Aktif)</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('brandsModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        style="width: 100px;" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="editBrandsModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editBrandsModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Brands</h3>
                <button onclick="closeModal('editBrandsModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Brands ID</label>
                    <input type="text" name="brands_code" id="edit_brands_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Brands</label>
                    <input type="text" name="brands_name" id="edit_brands_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none">
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi</label>
                    <textarea name="description" id="edit_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none resize-none"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('editBrandsModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                    <button type="submit" name="update"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>