<?php
include __DIR__ . '/../../../config/database.php';

// Helper: generate a unique transfer code based on base string
function generate_unique_transfer_code($conn, $base)
{
    $like = mysqli_real_escape_string($conn, $base) . '%';
    $res = mysqli_query($conn, "SELECT transfer_code FROM stock_transfers WHERE transfer_code LIKE '$like'");
    $max = 0;
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $code = $r['transfer_code'];
            if ($code === $base) {
                $max = max($max, 1);
                continue;
            }
            $parts = explode('-', $code);
            $last = end($parts);
            if (is_numeric($last)) {
                $num = (int)$last;
                $max = max($max, $num);
            }
        }
    }
    if ($max === 0) return $base;
    return $base . '-' . ($max + 1);
}

// Helper: Check available stock
function check_available_stock($conn, $product_id, $location_id)
{
    $sql = "SELECT current_stock FROM stock WHERE product_id = $product_id AND location_id = $location_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return (int)$row['current_stock'];
    }
    return 0;
}

// Server-side (non-AJAX) stock check for modal: read GET params and compute available stock
$modal_available_stock = null;
$open_modal = false;
if (isset($_GET['modal_product']) && isset($_GET['modal_location'])) {
    $mp = (int)$_GET['modal_product'];
    $ml = (int)$_GET['modal_location'];
    $res = mysqli_query($conn, "SELECT current_stock FROM stock WHERE product_id = $mp AND location_id = $ml");
    if ($res && mysqli_num_rows($res) > 0) {
        $modal_available_stock = (int)mysqli_fetch_assoc($res)['current_stock'];
    } else {
        $modal_available_stock = 0;
    }
}
// Preserve modal-selected ids for rendering selects
$modal_product_id = isset($mp) ? $mp : null;
$modal_location_id = isset($ml) ? $ml : null;
if (isset($_GET['open']) && $_GET['open'] === 'transferModal') {
    $open_modal = true;
}

// CREATE STOCK TRANSFER
if (isset($_POST['create'])) {
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : NULL;
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $from_location_id = !empty($_POST['from_location_id']) ? (int)$_POST['from_location_id'] : NULL;
    $to_location_id = !empty($_POST['to_location_id']) ? (int)$_POST['to_location_id'] : NULL;
    $quantity = !empty($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $transfer_date = !empty($_POST['transfer_date']) ? $_POST['transfer_date'] : date('Y-m-d');
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, trim($_POST['reason'])) : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, trim($_POST['notes'])) : '';

    if (!$product_id || !$from_location_id || !$to_location_id || $quantity <= 0) {
        echo "<script>alert('Field wajib harus diisi!'); history.back();</script>";
        exit;
    }

    if ($from_location_id === $to_location_id) {
        echo "<script>alert('Source dan Destination tidak boleh sama!'); history.back();</script>";
        exit;
    }

    // VALIDASI STOCK: Cek apakah produk ada di gudang asal
    $available_stock = check_available_stock($conn, $product_id, $from_location_id);

    if ($available_stock <= 0) {
        echo "<script>alert('Produk tidak tersedia di gudang asal! Stok saat ini: 0'); history.back();</script>";
        exit;
    }

    if ($quantity > $available_stock) {
        echo "<script>alert('Jumlah transfer melebihi stok yang tersedia! Stok tersedia: $available_stock'); history.back();</script>";
        exit;
    }

    // Generate transfer_code
    $res_product = mysqli_query($conn, "SELECT product_code FROM products WHERE id=$product_id");
    $product_code_suffix = 'XXXX';
    if ($res_product && mysqli_num_rows($res_product) > 0) {
        $product_row = mysqli_fetch_assoc($res_product);
        $product_code_suffix = substr($product_row['product_code'], -4);
    }

    $res_from = mysqli_query($conn, "SELECT location_code FROM locations WHERE id=$from_location_id");
    $from_code = 'XXX';
    if ($res_from && mysqli_num_rows($res_from) > 0) {
        $from_row = mysqli_fetch_assoc($res_from);
        $from_code = substr($from_row['location_code'], -3);
    }

    $res_to = mysqli_query($conn, "SELECT location_code FROM locations WHERE id=$to_location_id");
    $to_code = 'XXX';
    if ($res_to && mysqli_num_rows($res_to) > 0) {
        $to_row = mysqli_fetch_assoc($res_to);
        $to_code = substr($to_row['location_code'], -3);
    }

    $base_code = "TRF-$product_code_suffix-$from_code-$to_code";
    $transfer_code = generate_unique_transfer_code($conn, $base_code);
    $category_sql = $category_id ? $category_id : 'NULL';

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert transfer record
        $sql = "INSERT INTO stock_transfers 
            (transfer_code, product_id, category_id, from_location_id, to_location_id, quantity, reason, notes, status, transfer_date)
            VALUES ('$transfer_code', $product_id, $category_sql, $from_location_id, $to_location_id, $quantity, '$reason', '$notes', 'pending', '$transfer_date')";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_commit($conn);
        echo "<script>alert('Transfer berhasil dibuat!'); location.href='index.php?page=inventory&sub=transfer';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . $e->getMessage() . "'); history.back();</script>";
    }
    exit;
}

// UPDATE STOCK TRANSFER
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : NULL;
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $from_location_id = !empty($_POST['from_location_id']) ? (int)$_POST['from_location_id'] : NULL;
    $to_location_id = !empty($_POST['to_location_id']) ? (int)$_POST['to_location_id'] : NULL;
    $quantity = !empty($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $transfer_date = !empty($_POST['transfer_date']) ? $_POST['transfer_date'] : date('Y-m-d');
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, trim($_POST['reason'])) : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, trim($_POST['notes'])) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $old_status = isset($_POST['old_status']) ? $_POST['old_status'] : 'pending';

    if (!$product_id || !$from_location_id || !$to_location_id || $quantity <= 0) {
        echo "<script>alert('Field wajib harus diisi!'); history.back();</script>";
        exit;
    }

    if ($from_location_id === $to_location_id) {
        echo "<script>alert('Source dan Destination tidak boleh sama!'); history.back();</script>";
        exit;
    }

    // Get old data
    $res_old = mysqli_query($conn, "SELECT * FROM stock_transfers WHERE id=$id");
    $old = mysqli_fetch_assoc($res_old);

    // Jika status berubah dari pending ke completed, validasi stock
    if ($old['status'] === 'pending' && $status === 'completed') {
        $available_stock = check_available_stock($conn, $product_id, $from_location_id);

        if ($available_stock <= 0) {
            echo "<script>alert('Produk tidak tersedia di gudang asal! Tidak dapat menyelesaikan transfer.'); history.back();</script>";
            exit;
        }

        if ($quantity > $available_stock) {
            echo "<script>alert('Jumlah transfer melebihi stok tersedia! Stok tersedia: $available_stock'); history.back();</script>";
            exit;
        }
    }

    // Update transfer code if needed
    if ($product_id != $old['product_id'] || $from_location_id != $old['from_location_id'] || $to_location_id != $old['to_location_id']) {
        $res_product = mysqli_query($conn, "SELECT product_code FROM products WHERE id=$product_id");
        $product_code_suffix = 'XXXX';
        if ($res_product && mysqli_num_rows($res_product) > 0) {
            $product_row = mysqli_fetch_assoc($res_product);
            $product_code_suffix = substr($product_row['product_code'], -4);
        }

        $res_from = mysqli_query($conn, "SELECT location_code FROM locations WHERE id=$from_location_id");
        $from_code = 'XXX';
        if ($res_from && mysqli_num_rows($res_from) > 0) {
            $from_row = mysqli_fetch_assoc($res_from);
            $from_code = substr($from_row['location_code'], -3);
        }

        $res_to = mysqli_query($conn, "SELECT location_code FROM locations WHERE id=$to_location_id");
        $to_code = 'XXX';
        if ($res_to && mysqli_num_rows($res_to) > 0) {
            $to_row = mysqli_fetch_assoc($res_to);
            $to_code = substr($to_row['location_code'], -3);
        }

        $base_code = "TRF-$product_code_suffix-$from_code-$to_code";
        $transfer_code = generate_unique_transfer_code($conn, $base_code);
    } else {
        $transfer_code = $old['transfer_code'];
    }

    $category_sql = $category_id ? $category_id : 'NULL';

    mysqli_begin_transaction($conn);

    try {
        // Update transfer
        $sql = "UPDATE stock_transfers SET
            transfer_code='$transfer_code',
            product_id=$product_id,
            category_id=$category_sql,
            from_location_id=$from_location_id,
            to_location_id=$to_location_id,
            quantity=$quantity,
            reason='$reason',
            notes='$notes',
            status='$status',
            transfer_date='$transfer_date'
            WHERE id=$id";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception(mysqli_error($conn));
        }

        // Jika status completed, update stock
        if ($old['status'] === 'pending' && $status === 'completed') {
            // Kurangi stock dari gudang asal
            $update_from = "UPDATE stock SET current_stock = current_stock - $quantity 
                           WHERE product_id = $product_id AND location_id = $from_location_id";
            if (!mysqli_query($conn, $update_from)) {
                throw new Exception("Gagal mengurangi stock di gudang asal");
            }

            // Tambah stock ke gudang tujuan (atau insert jika belum ada)
            $check_to = mysqli_query($conn, "SELECT id FROM stock WHERE product_id = $product_id AND location_id = $to_location_id");

            if (mysqli_num_rows($check_to) > 0) {
                // Update existing stock
                $update_to = "UPDATE stock SET current_stock = current_stock + $quantity 
                             WHERE product_id = $product_id AND location_id = $to_location_id";
                if (!mysqli_query($conn, $update_to)) {
                    throw new Exception("Gagal menambah stock di gudang tujuan");
                }
            } else {
                // Insert new stock record
                $insert_to = "INSERT INTO stock (product_id, location_id, current_stock, min_stock) 
                             VALUES ($product_id, $to_location_id, $quantity, 10)";
                if (!mysqli_query($conn, $insert_to)) {
                    throw new Exception("Gagal membuat record stock di gudang tujuan");
                }
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('Transfer berhasil diupdate!'); location.href='index.php?page=inventory&sub=transfer';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . $e->getMessage() . "'); history.back();</script>";
    }
    exit;
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Cek apakah sudah completed
    $check = mysqli_query($conn, "SELECT status FROM stock_transfers WHERE id=$id");
    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        if ($row['status'] === 'completed') {
            echo "<script>alert('Tidak dapat menghapus transfer yang sudah completed!'); location.href='index.php?page=inventory&sub=transfer';</script>";
            exit;
        }
    }

    mysqli_query($conn, "DELETE FROM stock_transfers WHERE id=$id");
    echo "<script>alert('Transfer berhasil dihapus!'); location.href='index.php?page=inventory&sub=transfer';</script>";
    exit;
}

// GET ALL DATA dengan informasi stock
$transfers = mysqli_query($conn, "
    SELECT st.*, p.name AS product_name, p.product_code, c.name AS category_name,
           l1.name AS from_location_name, l2.name AS to_location_name,
           COALESCE(s.current_stock, 0) AS available_stock
    FROM stock_transfers st
    LEFT JOIN products p ON p.id = st.product_id
    LEFT JOIN categories c ON c.id = st.category_id
    LEFT JOIN locations l1 ON l1.id = st.from_location_id
    LEFT JOIN locations l2 ON l2.id = st.to_location_id
    LEFT JOIN stock s ON s.product_id = st.product_id AND s.location_id = st.from_location_id
    ORDER BY st.transfer_date DESC, st.id DESC
");
?>

<div class="flex items-center justify-between mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">Stock Transfer</h1>
    <?php if ($isStaff): ?>
        <button onclick="openModal('transferModal')"
            class="bg-[#092363] font-bold text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
            <i class='bx bx-plus text-lg'></i><span>Buat Transfer Baru</span>
        </button>
    <?php endif; ?>
</div>

<div class="w-full flex justify-between gap-2 mb-6">
    <div class="flex items-center gap-3 bg-white px-3 py-2 rounded-md shadow-md w-full">
        <i class='bx bx-search text-xl text-gray-500'></i>
        <input type="text" placeholder="Search..." class="w-full ml-2 focus:outline-none">
    </div>
    <div class="flex gap-2 justify-end">
        <select class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
</div>

<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center">Date</th>
                <th class="px-4 py-3 text-center">Transfer ID</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3 text-center">Source</th>
                <th class="px-4 py-3 text-center">Destination</th>
                <th class="px-4 py-3 text-center">Qty</th>
                <th class="px-4 py-3 text-center">Stock Tersedia</th>
                <th class="px-4 py-3 text-center">Notes</th>
                <th class="px-4 py-3 text-center">Status</th>
                <?php if ($isAdmin): ?>
                    <th class="px-4 py-3 text-center">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (mysqli_num_rows($transfers) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($transfers)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500"><?= date('d M Y', strtotime($row['transfer_date'])) ?></td>
                        <td class="px-4 py-3 text-gray-600 font-medium"><?= htmlspecialchars($row['transfer_code']) ?></td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800"><?= htmlspecialchars($row['product_name']) ?></td>
                        <td class="px-4 py-3 text-center text-gray-600 text-sm"><?= htmlspecialchars($row['from_location_name']) ?></td>
                        <td class="px-4 py-3 text-center text-gray-600 text-sm"><?= htmlspecialchars($row['to_location_name']) ?></td>
                        <td class="px-4 py-3 text-center text-gray-600 font-bold"><?= $row['quantity'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $stock_class = $row['available_stock'] >= $row['quantity'] ? 'text-green-600' : 'text-red-600';
                            ?>
                            <span class="<?= $stock_class ?> font-bold"><?= $row['available_stock'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 italic"><?= htmlspecialchars($row['reason']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $status_map = ['pending' => 'bg-yellow-100 text-yellow-700', 'completed' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700'];
                            $class = $status_map[$row['status']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="<?= $class ?> text-xs px-3 py-1 rounded-full font-bold"><?= ucfirst($row['status']) ?></span>
                        </td>
                        <?php if ($isAdmin): ?>
                            <td class="px-4 py-3 flex gap-2 justify-center">
                                <button onclick="openEditModal('editTransferModal', this)"
                                    data-id="<?= $row['id'] ?>"
                                    data-transfer_code="<?= htmlspecialchars($row['transfer_code']) ?>"
                                    data-product_id="<?= $row['product_id'] ?>"
                                    data-from_location_id="<?= $row['from_location_id'] ?>"
                                    data-to_location_id="<?= $row['to_location_id'] ?>"
                                    data-quantity="<?= $row['quantity'] ?>"
                                    data-transfer_date="<?= $row['transfer_date'] ?>"
                                    data-reason="<?= htmlspecialchars($row['reason']) ?>"
                                    data-notes="<?= htmlspecialchars($row['notes']) ?>"
                                    data-status="<?= $row['status'] ?>"
                                    data-available_stock="<?= $row['available_stock'] ?>"
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500"><i class='bx bxs-edit text-lg'></i></button>
                                <?php if ($row['status'] !== 'completed'): ?>
                                    <button onclick="if(confirm('Are you sure to delete this transfer?')){ 
                                    window.location.href='index.php?page=inventory&sub=transfer&delete=<?= $row['id'] ?>' 
                                }"
                                        class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600"><i class='bx bxs-trash text-lg'></i></button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 11 : 10 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-transfer text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada Transfer</p>
                        <p class="text-sm">Buat transfer stock baru dengan menekan tombol "Buat Transfer Baru"</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Create -->
<div id="transferModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('transferModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold">Buat Stock Transfer</h3>
                <button onclick="closeModal('transferModal')" class="text-white hover:text-[#e6b949] text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="p-6 space-y-4" onsubmit="return validateTransfer()">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Produk</label>
                        <select name="product_id" id="product_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="updateStockInfo()">
                            <option value="">Pilih Produk</option>
                            <?php
                            $prods = mysqli_query($conn, "SELECT id, product_code, name FROM products ORDER BY name");
                            while ($p = mysqli_fetch_assoc($prods)) {
                                $sel = ($modal_product_id !== null && $modal_product_id == $p['id']) ? ' selected' : '';
                                echo '<option value="' . $p['id'] . '"' . $sel . '>' . htmlspecialchars($p['name']) . ' (' . $p['product_code'] . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah</label>
                        <input type="number" name="quantity" id="quantity" required min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="0" onchange="validateQuantity()">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Source Location</label>
                        <select name="from_location_id" id="from_location_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Pilih</option>
                            <?php
                            $locs1 = mysqli_query($conn, "SELECT id, location_code, name FROM locations ORDER BY name");
                            while ($l = mysqli_fetch_assoc($locs1)) {
                                $sel = ($modal_location_id !== null && $modal_location_id == $l['id']) ? ' selected' : '';
                                echo '<option value="' . $l['id'] . '"' . $sel . '>' . htmlspecialchars($l['name']) . ' (' . $l['location_code'] . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Destination</label>
                        <select name="to_location_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Pilih</option>
                            <?php
                            $locs2 = mysqli_query($conn, "SELECT id, location_code, name FROM locations ORDER BY name");
                            while ($l = mysqli_fetch_assoc($locs2)) echo '<option value="' . $l['id'] . '">' . htmlspecialchars($l['name']) . ' (' . $l['location_code'] . ')</option>';
                            ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal</label>
                    <input type="date" name="transfer_date" value="<?= date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('transferModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                    <button type="submit" name="create" class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363]">Buat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="editTransferModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editTransferModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold">Edit Stock Transfer</h3>
                <button onclick="closeModal('editTransferModal')" class="text-white hover:text-[#e6b949] text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="old_status" id="edit_old_status">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Transfer ID</label>
                    <input type="text" id="edit_transfer_code" class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Produk</label>
                        <select name="product_id" id="edit_product_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Pilih</option>
                            <?php
                            $prods_e = mysqli_query($conn, "SELECT id, product_code, name FROM products ORDER BY name");
                            while ($p = mysqli_fetch_assoc($prods_e)) echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['name']) . ' (' . $p['product_code'] . ')</option>';
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah</label>
                        <input type="number" name="quantity" id="edit_quantity" required min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="0">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Source</label>
                        <select name="from_location_id" id="edit_from_location_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Pilih</option>
                            <?php
                            $locs1_e = mysqli_query($conn, "SELECT id, location_code, name FROM locations ORDER BY name");
                            while ($l = mysqli_fetch_assoc($locs1_e)) echo '<option value="' . $l['id'] . '">' . htmlspecialchars($l['name']) . ' (' . $l['location_code'] . ')</option>';
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Destination</label>
                        <select name="to_location_id" id="edit_to_location_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Pilih</option>
                            <?php
                            $locs2_e = mysqli_query($conn, "SELECT id, location_code, name FROM locations ORDER BY name");
                            while ($l = mysqli_fetch_assoc($locs2_e)) echo '<option value="' . $l['id'] . '">' . htmlspecialchars($l['name']) . ' (' . $l['location_code'] . ')</option>';
                            ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal</label>
                    <input type="date" name="transfer_date" id="edit_transfer_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan</label>
                    <textarea name="notes" id="edit_notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('editTransferModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                    <button type="submit" name="update" class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363]">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Function untuk update stock info secara realtime via AJAX
    // Non-AJAX: submit hidden GET form with current selects to compute stock server-side
    function doStockCheck() {
        const productId = document.getElementById('product_id').value;
        const locationId = document.getElementById('from_location_id').value;
        if (!productId || !locationId) {
            alert('Pilih produk dan source location terlebih dahulu');
            return;
        }
        document.getElementById('stockCheck_modal_product').value = productId;
        document.getElementById('stockCheck_modal_location').value = locationId;
        document.getElementById('stockCheckForm').submit();
    }

    function validateQuantity() {
        const quantity = parseInt(document.getElementById('quantity').value) || 0;
        const stockDisplay = document.getElementById('available_stock_display').textContent;
        const availableStock = parseInt(stockDisplay) || 0;

        if (quantity > availableStock) {
            alert(`Jumlah melebihi stock tersedia! Stock tersedia: ${availableStock}`);
            document.getElementById('quantity').value = availableStock;
        }
    }

    function validateTransfer() {
        const productId = document.getElementById('product_id').value;
        const fromLocationId = document.getElementById('from_location_id').value;
        const toLocationId = document.getElementById('to_location_id').value;
        const quantity = parseInt(document.getElementById('quantity').value) || 0;

        if (!productId || !fromLocationId || !toLocationId) {
            alert('Mohon lengkapi semua field yang wajib!');
            return false;
        }

        if (fromLocationId === toLocationId) {
            alert('Source dan Destination tidak boleh sama!');
            return false;
        }

        if (quantity <= 0) {
            alert('Jumlah harus lebih dari 0!');
            return false;
        }

        return true;
    }
</script>
<?php if ($open_modal): ?>
    <script>
        // Auto-open modal after page load when requested via GET
        setTimeout(function() {
            if (typeof openModal === 'function') openModal('transferModal');
        }, 150);
    </script>
<?php endif; ?>
</script>