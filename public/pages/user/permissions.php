<?php
include __DIR__ . '/../../../config/database.php';

// UPDATE PERMISSION
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $user_id = $_POST['user_id'];
    $module = $_POST['module'];

    $can_view = isset($_POST['can_view']) ? 1 : 0;
    $can_create = isset($_POST['can_create']) ? 1 : 0;
    $can_edit = isset($_POST['can_edit']) ? 1 : 0;
    $can_delete = isset($_POST['can_delete']) ? 1 : 0;

    mysqli_query($conn, "UPDATE permissions SET 
        can_view='$can_view',
        can_create='$can_create',
        can_edit='$can_edit',
        can_delete='$can_delete'
        WHERE id='$id'");

    echo "<script>alert('Permission berhasil diupdate!'); location.href='index.php?page=usermanagement&sub=permissions';</script>";
    exit;
}

// DELETE PERMISSION
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM permissions WHERE id='$id'");
    echo "<script>alert('Permission deleted successfully!'); location.href='index.php?page=usermanagement&sub=permissions';</script>";
    exit;
}

// GET ALL DATA
$result = mysqli_query($conn, "
    SELECT p.*, u.full_name, u.username, u.email, u.role
    FROM permissions p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.id ASC
");

if (!$isAdmin) {
    echo '<p class="text-red-500 font-semibold">Access denied. Only admin can manage permissions.</p>';
    return;
}
?>

<div class="mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">User Permissions</h1>
    <p class="px-4 text-gray-500 text-sm">
        Manage roles and their access to system modules.
    </p>
</div>


<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center text-white">No</th>
                <th class="px-4 py-3 text-center text-white">User</th>
                <th class="px-4 py-3 text-center text-white">Menu</th>
                <th class="px-4 py-3 text-center text-white">Can View</th>
                <th class="px-4 py-3 text-center text-white">Can Create</th>
                <th class="px-4 py-3 text-center text-white">Can Edit</th>
                <th class="px-4 py-3 text-center text-white">Can Delete</th>
                <th class="px-4 py-3 text-center text-white">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <!-- Contoh user -->
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)):
            ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center"><?= $no++ ?></td>
                    <td class="px-4 py-3 text-center"><?= $row['full_name'] ?></td>
                    <td class="px-4 py-3 text-center"><?= $row['module'] ?></td>

                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="h-4 w-4" <?= $row['can_view'] ? 'checked' : '' ?>>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="h-4 w-4" <?= $row['can_create'] ? 'checked' : '' ?>>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="h-4 w-4" <?= $row['can_edit'] ? 'checked' : '' ?>>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="h-4 w-4" <?= $row['can_delete'] ? 'checked' : '' ?>>
                    </td>

                    <td class="px-4 py-2 flex gap-3 justify-center">
                        <button onclick="openEditModal('editPermissionModal', this)"
                            data-id="<?= $row['id'] ?>"
                            data-full_name="<?= $row['full_name'] ?>"
                            data-module="<?= $row['module'] ?>"
                            data-can_view="<?= $row['can_view'] ? 'checked' : ''?>"
                            data-can_create="<?= $row['can_create'] ? 'checked' : ''?>"
                            data-can_edit="<?= $row['can_edit'] ? 'checked' : ''?>"
                            data-can_delete="<?= $row['can_delete'] ? 'checked' : ''?>"
                            class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                            <i class='bx bxs-edit'></i>
                        </button>
                        <button onclick="if(confirm('Apakah Anda yakin ingin menghapus permission ini?')) { 
                        window.location.href='index.php?page=usermanagement&sub=permissions&delete=<?= $row['id'] ?>'; 
                    }"
                            class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                            <i class='bx bxs-trash'></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
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

<!-- Modal Tambah User -->
<div id="permissionsModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('upermissionsModal')"></div>
    <div class="modal-flex-container">

        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Tambah User Baru</h3>
                <button onclick="closeModal('permissionsModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Lengkap</label>
                        <input type="text" name="fullname" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="Budi Santoso" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Username</label>
                        <input type="text" name="username" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="budisantoso123" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Email</label>
                        <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="email@domain.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">No. Telepon</label>
                        <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0812...">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Password Default</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="******" required>
                </div>

                <h3 class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Acces</h3>
                <div class="border border-gray-200 rounded-lg p-4 mb-4">

                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="products_view" class="w-4 h-4">
                            <span>View</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="products_create" class="w-4 h-4">
                            <span>Create</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="products_edit" class="w-4 h-4">
                            <span>Edit</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="products_export" class="w-4 h-4">
                            <span>Export</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Role / Jabatan</label>
                        <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="staff">Staff Gudang</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status Akun</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="active" class="text-green-600 font-bold">Active (Aktif)</option>
                            <option value="inactive" class="text-red-600 font-bold">Inactive (Nonaktif)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('permissionsModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div id="editPermissionModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editPermissionModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Permissions</h3>
                <button onclick="closeModal('editPermissionModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div>
                    <label class="block text-sm text-gray-600 px-2">User</label>
                    <input type="text" name=full_name id="edit_full_name" class="w-full mt-1 px-3 py-2 border rounded-lg bg-gray-100" readonly>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 px-2">Menu</label>
                    <input type="text" name=module id="edit_module" class="w-full mt-1 px-3 py-2 border rounded-lg bg-gray-100" readonly>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2">
                        <input name="can_view" id="edit_can_view" type="checkbox" class="h-4 w-4">
                        Can View
                    </label>

                    <label class="flex items-center gap-2">
                        <input name="can_create" id="edit_can_create" type="checkbox" class="h-4 w-4">
                        Can Create
                    </label>

                    <label class="flex items-center gap-2">
                        <input name="can_edit" id="edit_can_edit" type="checkbox" class="h-4 w-4">
                        Can Edit
                    </label>

                    <label class="flex items-center gap-2">
                        <input name="can_delete" id="edit_can_delete" type="checkbox" class="h-4 w-4">
                        Can Delete
                    </label>

                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2 mb-4">
                    <button type="button" onclick="closeModal('editPermissionModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>