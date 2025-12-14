<?php
include __DIR__ . "/../../../config/database.php";

//  INSERT DATA
if (isset($_POST['create'])) {

    $name = $_POST['category_name'];
    $desc = $_POST['description'];
    $status = $_POST['status'];

    // Cek apakah nama kategori sudah ada
    $check = mysqli_query($conn, "SELECT id FROM categories WHERE name='$name'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama kategori sudah ada!'); location.href='index.php?page=catalog&sub=categories';</script>";
        exit;
    }

    // Ambil nama category dari input
    $name = $_POST['category_name'];

    // Hapus semua spasi
    $name_no_space = str_replace(' ', '', $name);

    // Ambil 3 huruf pertama (uppercase)
    $prefix = strtoupper(substr($name_no_space, 0, 3));

    // Buat brand_code
    $code = "CAT-$prefix";

    mysqli_query($conn, "INSERT INTO categories (category_code, name, description, status)
        VALUES ('$code', '$name', '$desc', '$status')");

    echo "<script>alert('Brands berhasil ditambahkan!'); location.href='index.php?page=catalog&sub=categories';</script>";
    exit;
}

//  UPDATE DATA
if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['category_name'];
    $desc = $_POST['description'];
    $status = $_POST['status'];

    $name = trim($_POST['category_name']);

    // Cek apakah nama kategori sudah ada selain id ini
    $check = mysqli_query($conn, "SELECT id FROM categories WHERE name='$name' AND id <> '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama kategori sudah ada!'); location.href='index.php?page=catalog&sub=categories';</script>";
        exit;
    }

    // Ambil data lama dari database
    $result = mysqli_query($conn, "SELECT name, category_code FROM categories WHERE id='$id'");
    $old = mysqli_fetch_assoc($result);

    // Jika nama berubah, buat code baru
    if ($name !== $old['name']) {
        $name_no_space = str_replace(' ', '', $name);
        $new_code = "CAT-" . strtoupper(substr($name_no_space, 0, 3));
    } else {
        $new_code = $old['category_code']; // tetap kode lama
    }

    mysqli_query($conn, "UPDATE categories SET
            category_code='$new_code',
            name='$name',
            description='$desc',
            status='$status'
            WHERE id='$id'
        ");

    echo "<script>alert('Category berhasil diperbarui!'); location.href='index.php?page=catalog&sub=categories';</script>";
    exit;
}

//  DELETE DATA
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM categories WHERE id='$id'");
    echo "<script>alert('Category berhasil dihapus!'); location.href='index.php?page=catalog&sub=categories';</script>";
    exit;
}

//  GET ALL DATA
$result = mysqli_query($conn, "SELECT * FROM categories ORDER BY id ASC");
?>

<!-- Create Kategori -->
<div class="flex items-center justify-between mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">Category Management</h1>
    <?php if ($isAdmin): ?>
        <button
            onclick="openModal('categoryModal')"
            class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
            <i class='bx bx-plus text-lg'></i>
            <span>Tambah Kategori</span>
        </button>
    <?php endif; ?>
</div>

<!-- Table Kategori -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center w-16">No</th>
                <th class="px-4 py-3 text-center">Category ID</th>
                <th class="px-4 py-3 text-center">Category</th>
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
                        <td class="px-4 py-3 text-gray-600 font-medium"><?= $row['category_code'] ?></td>
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
                                    onclick="openEditModal('editCategoryModal', this)"
                                    data-id="<?= $row['id'] ?>"
                                    data-category_code="<?= $row['category_code'] ?>"
                                    data-category_name="<?= $row['name'] ?>"
                                    data-description="<?= $row['description'] ?>"
                                    data-status="<?= $row['status'] ?>"
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-edit text-lg'></i></button>
                                <button onclick="if(confirm('Are you sure you want to delete this category?')){ 
                            window.location.href='index.php?page=catalog&sub=categories&delete=<?= $row['id'] ?>'; 
                            }"
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-trash text-lg'></i></button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-category text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada kategory yang tersedia</p>
                        <p class="text-sm mt-1">Buat kategori terlebih dahulu</p>
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

<!-- Modal Tambah Kategori -->
<div id="categoryModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('categoryModal')"></div>
    <div class="modal-flex-container">

        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Tambah Kategori Baru</h3>
                <button onclick="closeModal('categoryModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Kategori</label>
                    <input type="text" name="category_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all" placeholder="Contoh: Elektronik" required>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all resize-none" placeholder="Penjelasan singkat kategori..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="active">Active (Aktif)</option>
                        <option value="inactive">Inactive (Tidak Aktif)</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('categoryModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="editCategoryModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editCategoryModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Kategori</h3>
                <button onclick="closeModal('editCategoryModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Category ID</label>
                    <input type="text" name="category_code" id="edit_category_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Kategori</label>
                    <input type="text" name="category_name" id="edit_category_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none">
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
                    <button type="button" onclick="closeModal('editCategoryModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                    <button type="submit" name="update"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>