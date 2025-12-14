<?php
include __DIR__ . "/../../../config/database.php";

// INSERT DATA
if (isset($_POST['create'])) {
    $name = trim($_POST['location_name']);
    $address = trim($_POST['address']);
    $type = $_POST['type']; // 'warehouse', 'shelf', atau 'store'
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    // Cek apakah nama location sudah ada
    $check = mysqli_query($conn, "SELECT id FROM locations WHERE name='$name' AND id <> '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama Location sudah ada!'); location.href='index.php?page=catalog&sub=locations';</script>";
        exit;
    }

    // Tentukan prefix berdasarkan type
    switch ($type) {
        case 'warehouse':
            $prefix = 'GUD';
            break;
        case 'shelf':
            $prefix = 'RAK';
            break;
        case 'store':
            $prefix = 'STR';
            break;
        default:
            $prefix = 'LOC';
    }

    // Buat location_code otomatis: LOC-PREFIX-angka
    $query = mysqli_query($conn, "SELECT location_code FROM locations WHERE location_code LIKE 'LOC-$prefix-%' ORDER BY id DESC LIMIT 1");
    if (mysqli_num_rows($query) > 0) {
        $last = mysqli_fetch_assoc($query);
        $parts = explode('-', $last['location_code']);
        $number = intval(end($parts)) + 1;
    } else {
        $number = 1;
    }
    $code = "LOC-$prefix-$number";

    // Insert ke database
    mysqli_query($conn, "INSERT INTO locations (location_code, name, address, type, capacity, status)
                        VALUES ('$code', '$name', '$address', '$type', '$capacity', '$status')");

    echo "<script>alert('Location berhasil ditambahkan!'); location.href='index.php?page=catalog&sub=locations';</script>";
    exit;
}

// UPDATE DATA
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = trim($_POST['location_name']);
    $address = trim($_POST['address']);
    $type = $_POST['type'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    // Cek apakah nama location sudah ada selain ID ini
    $check = mysqli_query($conn, "SELECT id FROM locations WHERE name='$name' AND id <> '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Nama Location sudah ada!'); location.href='index.php?page=catalog&sub=locations';</script>";
        exit;
    }

    // Ambil data lama
    $result = mysqli_query($conn, "SELECT location_code, type FROM locations WHERE id='$id'");
    $old = mysqli_fetch_assoc($result);

    // Tentukan prefix
    switch ($type) {
        case 'warehouse':
            $prefix = 'GUD';
            break;
        case 'shelf':
            $prefix = 'RAK';
            break;
        case 'store':
            $prefix = 'STR';
            break;
        default:
            $prefix = 'LOC';
    }

    // Jika nama berubah atau type berubah, buat kode baru
    $old_prefix = explode('-', $old['location_code'])[1]; // ambil prefix lama
    if ($prefix !== $old_prefix) {
        $query = mysqli_query($conn, "SELECT location_code FROM locations WHERE location_code LIKE 'LOC-$prefix-%' ORDER BY id DESC LIMIT 1");
        if (mysqli_num_rows($query) > 0) {
            $last = mysqli_fetch_assoc($query);
            $parts = explode('-', $last['location_code']);
            $number = intval(end($parts)) + 1;
        } else {
            $number = 1;
        }
        $new_code = "LOC-$prefix-$number";
    } else {
        $new_code = $old['location_code'];
    }

    // Update ke database
    mysqli_query($conn, "UPDATE locations SET 
                        location_code='$new_code',
                        name='$name',
                        address='$address',
                        type='$type',
                        capacity='$capacity',
                        status='$status'
                        WHERE id='$id'");

    echo "<script>alert('Location berhasil diupdate!'); location.href='index.php?page=catalog&sub=locations';</script>";
    exit;
}

//  DELETE DATA
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM locations WHERE id='$id'");
    echo "<script>alert('Location berhasil dihapus!'); location.href='index.php?page=catalog&sub=locations';</script>";
    exit;
}

// GET ALL DATA
$locations = mysqli_query($conn, "SELECT * FROM locations ORDER BY id ASC");
?>

<!-- Cretae Locations -->
<div class="flex items-center justify-between mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">Location Management</h1>
    <?php if ($isAdmin): ?>
        <button
            onclick="openModal('locationModal')"
            class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
            <i class='bx bx-plus text-lg'></i>
            <span>Tambah Lokasi</span>
        </button>
    <?php endif; ?>
</div>

<!-- Table Locations -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center w-16">No</th>
                <th class="px-4 py-3 text-center">Location ID</th>
                <th class="px-4 py-3 text-center">Location</th>
                <th class="px-4 py-3 text-center">Address / Detail</th>
                <th class="px-4 py-3 text-center">Capacity</th>
                <th class="px-4 py-3 text-center">Type</th>
                <th class="px-4 py-3 text-center">Status</th>
                <?php if ($isAdmin): ?>
                    <th class="px-4 py-3 text-center">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php $no = 1; ?>
            <?php if (mysqli_num_rows($locations) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($locations)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500"><?= $no++ ?></td>
                        <td class="px-4 py-3 text-gray-600 font-medium"><?= $row['location_code'] ?></td>
                        <td class="px-4 py-3 font-bold text-gray-800"><?= $row['name'] ?></td>
                        <td class="px-4 py-3 text-gray-600 text-sm truncate max-w-xs"><?= $row['address'] ?></td>
                        <td class="px-4 py-3 text-center font-bold text-[#092363]"><?= $row['capacity'] ?></td>

                        <td class="px-4 py-3 text-center">
                            <span class="bg-blue-50 text-blue-600 text-xs px-2 py-1 rounded border border-blue-100"><?= $row['type'] ?></span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php
                            $color = [
                                "active" => "flex justify-center bg-green-100 text-green-700",
                                "full" => "flex justify-center bg-yellow-100 text-yellow-700",
                                "maintenance" => "flex justify-center bg-red-100 text-red-700",
                                "inactive" => "flex justify-center bg-gray-200 text-gray-700"
                            ];
                            ?>
                            <span class="px-3 py-1 text-xs rounded-full font-bold <?= $color[$row['status']] ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>

                        <?php if ($isAdmin): ?>
                            <td class="px-4 py-3 flex gap-2 justify-center">
                                <button
                                    onclick="openEditModal('editLocationModal', this)"
                                    data-id="<?= $row['id'] ?>"
                                    data-location_code="<?= $row['location_code'] ?>"
                                    data-location_name="<?= $row['name'] ?>"
                                    data-address="<?= $row['address'] ?>"
                                    data-capacity="<?= $row['capacity'] ?>"
                                    data-type="<?= $row['type'] ?>"
                                    data-status="<?= $row['status'] ?>"
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-edit text-lg'></i>
                                </button>
                                <button onclick="if(confirm('Are you sure you want to delete this location?')) { 
                            window.location.href='index.php?page=catalog&sub=locations&delete=<?= $row['id'] ?>'; 
                            }"
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-trash text-lg'></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 8 : 7 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-map text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada lokasi yang tersedia</p>
                        <p class="text-sm mt-1">Buat lokasi terlebih dahulu</p>
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

<div id="locationModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('locationModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Tambah Lokasi Baru</h3>
                <button onclick="closeModal('locationModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Lokasi / Gudang</label>
                    <input type="text" name="location_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all" placeholder="Contoh: Gudang Pusat" required>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Tipe Penyimpanan</label>
                        <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="warehouse">Warehouse (Gudang)</option>
                            <option value="shelf">Shelf (Rak)</option>
                            <option value="store">Store (Toko)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Kapasitas Max</label>
                        <input type="number" name="capacity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Alamat / Detail Posisi</label>
                    <textarea name="address" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none transition-all resize-none" placeholder="Alamat lengkap atau posisi detail rak..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="active">Active (Aktif)</option>
                        <option value="full">Full (Penuh)</option>
                        <option value="maintance">Maintenance / Tutup</option>
                        <option value="inactive">Inactive (Non-aktif)</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('locationModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editLocationModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editLocationModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Lokasi</h3>
                <button onclick="closeModal('editLocationModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Location ID</label>
                    <input type="text" name="location_code" id="edit_location_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Nama Lokasi / Gudang</label>
                    <input type="text" name="location_name" id="edit_location_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none transition-all" required>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Tipe Penyimpanan</label>
                        <select name="type" id="edit_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none cursor-pointer">
                            <option value="warehouse">Warehouse (Gudang)</option>
                            <option value="shelf">Shelf (Rak)</option>
                            <option value="store">Store (Toko)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Kapasitas Max</label>
                        <input type="number" name="capacity" id="edit_capacity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Alamat / Detail Posisi</label>
                    <textarea name="address" id="edit_address" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none transition-all resize-none"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none cursor-pointer">
                        <option value="active">Active (Aktif)</option>
                        <option value="full">Full (Penuh)</option>
                        <option value="maintenance">Maintenance / Tutup</option>
                        <option value="inactive">Inactive (Non-aktif)</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('editLocationModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>