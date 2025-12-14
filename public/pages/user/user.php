<?php
include __DIR__ . '/../../../config/database.php';

//  INSERT DATA
if (isset($_POST['create'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Cek apakah username sudah ada
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Username sudah ada!'); location.href='index.php?page=usermanagement&sub=user';</script>";
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    mysqli_query($conn, "INSERT INTO users (username, password, full_name, email, phone, role, status)
                        VALUES ('$username', '$hashed_password', '$full_name', '$email', '$phone', '$role', '$status')");

    echo "<script>alert('User berhasil ditambahkan!'); location.href='index.php?page=usermanagement&sub=user';</script>";
    exit;
}

//  UPDATE DATA
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Cek apakah username sudah ada selain id ini
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='{$_POST['username']}' AND id <> '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Username sudah ada!'); location.href='index.php?page=usermanagement&sub=user';</script>";
        exit;
    }

    mysqli_query($conn, "UPDATE users SET 
                        full_name='$full_name',
                        email='$email',
                        phone='$phone',
                        role='$role',
                        status='$status'
                        WHERE id='$id'");

    echo "<script>alert('User berhasil diupdate!'); location.href='index.php?page=usermanagement&sub=user';</script>";
    exit;
}

//  DELETE DATA
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    echo "<script>alert('User berhasil dihapus!'); location.href='index.php?page=usermanagement&sub=user';</script>";
    exit;
}

//  GET ALL DATA
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
?>

<!-- Create User -->
<div class="flex items-center justify-between mb-4 mt-3">
    <div>
        <h1 class="text-2xl px-4 font-bold text-[#092363]">Create Account</h1>
        <p class="px-4 text-gray-500 text-sm">
            Create account & Change password.
        </p>
    </div>

    <div>
        <button
            onclick="openModal('userModal')"
            class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
            <i class='bx bx-plus text-lg'></i>
            <span>Tambah User</span>
        </button>
    </div>
</div>

<!-- Table User -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center">No</th>
                <th class="px-4 py-3 text-center">Username</th>
                <th class="px-4 py-3 text-center">Full Name</th>
                <th class="px-4 py-3 text-center">Email</th>
                <th class="px-4 py-3 text-center">Phone</th>
                <th class="px-4 py-3 text-center">Role</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)):
            ?>
                <!-- Contoh user -->
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-600 text-sm"><?= $no++ ?></td>
                    <td class="px-4 py-3 font-bold text-gray-800"><?= $row['username'] ?></td>
                    <td class="px-4 py-3 text-gray-800"><?= $row['full_name'] ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= $row['email'] ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= $row['phone'] ?></td>

                    <td class="px-4 py-3 text-center">
                        <?php
                        $color = [
                            "admin" => "flex justify-center bg-blue-100 text-blue-700",
                            "staff" => "flex justify-center bg-green-100 text-green-700",
                        ];
                        ?>
                        <span class="px-3 py-1 text-xs rounded-full font-bold <?= $color[$row['role']] ?>">
                            <?= ucfirst($row['role']) ?>
                        </span>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <?php if ($row['status'] == "active"): ?>
                            <span class="bg-green-100 text-green-700 text-xs px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm">Active</span>
                        <?php else: ?>
                            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm">Inactive</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-4 py-2 flex gap-3 justify-center">
                        <button onclick="openEditModal('editUserModal', this)"
                            data-id="<?= $row['id'] ?>"
                            data-username="<?= $row['username'] ?>"
                            data-full_name="<?= $row['full_name'] ?>"
                            data-email="<?= $row['email'] ?>"
                            data-phone="<?= $row['phone'] ?>"
                            data-role="<?= $row['role'] ?>"
                            data-status="<?= $row['status'] ?>"
                            class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                            <i class='bx bxs-edit'></i>
                        </button>
                        <button onclick="if(confirm('Apakah Anda yakin ingin menghapus user ini?')) { 
                        window.location.href='index.php?page=usermanagement&sub=user&delete=<?= $row['id'] ?>'; 
                        }"
                            class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                            <i class='bx bxs-trash'></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
            <!-- Loop user dari database di PHP -->
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
<div id="userModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('userModal')"></div>
    <div class="modal-flex-container">

        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Tambah User Baru</h3>
                <button onclick="closeModal('userModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
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
                    <button type="button" onclick="closeModal('userModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div id="editUserModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editUserModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Data User</h3>
                <button onclick="closeModal('editUserModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-4">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Username (Tetap)</label>
                    <input type="text" name="username" id="edit_username" class="w-full bg-transparent text-gray-700 font-bold text-sm outline-none cursor-not-allowed" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Lengkap</label>
                    <input type="text" name="full_name" id="edit_full_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none transition-all" required>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Email</label>
                        <input type="email" name="email" id="edit_email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">No. Telepon</label>
                        <input type="text" name="phone" id="edit_phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Role / Jabatan</label>
                        <select name="role" id="edit_role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none cursor-pointer">
                            <option value="staff">Staff Gudang</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status Akun</label>
                        <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none cursor-pointer">
                            <option value="active">Active (Aktif)</option>
                            <option value="inactive">Inactive (Nonaktif)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2 mb-4">
                    <button type="button" onclick="closeModal('editUserModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>