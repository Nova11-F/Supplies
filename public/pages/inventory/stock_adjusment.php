<?php
include __DIR__ . '/../../../config/database.php';

// Detect available columns in stock_adjustments to avoid referencing missing columns
$sa_cols = [];
$saColsRes = mysqli_query($conn, "SHOW COLUMNS FROM stock_adjustments");
if ($saColsRes) {
    while ($c = mysqli_fetch_assoc($saColsRes)) {
        $sa_cols[] = $c['Field'];
    }
}
$has_sa_status = in_array('status', $sa_cols);

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

// CREATE Stock Adjustment
if (isset($_POST['create'])) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $adjustment_type = isset($_POST['adjustment_type']) ? mysqli_real_escape_string($conn, $_POST['adjustment_type']) : '';
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    $adjustment_date = isset($_POST['adjustment_date']) ? mysqli_real_escape_string($conn, $_POST['adjustment_date']) : date('Y-m-d');

    if (!$product_id || !$location_id || $quantity <= 0 || !$adjustment_type || !$reason) {
        echo "<script>alert('Semua field wajib harus diisi!'); history.back();</script>";
        exit;
    }

    // Validasi stock untuk decrease
    if ($adjustment_type === 'subtract') {
        $available_stock = check_available_stock($conn, $product_id, $location_id);

        if ($available_stock <= 0) {
            echo "<script>alert('Produk tidak tersedia di lokasi ini! Stok saat ini: 0'); history.back();</script>";
            exit;
        }

        if ($quantity > $available_stock) {
            echo "<script>alert('Jumlah pengurangan melebihi stok tersedia! Stok tersedia: $available_stock'); history.back();</script>";
            exit;
        }
    }

    // Get current stock
    $current_stock = check_available_stock($conn, $product_id, $location_id);

    // Calculate new stock
    $new_stock = $adjustment_type === 'add' ? $current_stock + $quantity : $current_stock - $quantity;

    // Generate adjustment_code
    $adjustment_code = 'ADJ-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    mysqli_begin_transaction($conn);

    try {
        // Insert adjustment record
        $sql = "INSERT INTO stock_adjustments 
            (adjustment_code, product_id, location_id, adjustment_type, quantity, old_stock, new_stock, reason, notes, adjustment_date)
            VALUES ('$adjustment_code', $product_id, $location_id, '$adjustment_type', $quantity, $current_stock, $new_stock, '$reason', '$notes', '$adjustment_date')";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error creating adjustment: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        echo "<script>alert('Stock Adjustment berhasil dibuat!'); location.href='index.php?page=inventory&sub=adjusment';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); history.back();</script>";
    }
    exit;
}

// UPDATE Stock Adjustment
if (isset($_POST['update'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $adjustment_type = isset($_POST['adjustment_type']) ? mysqli_real_escape_string($conn, $_POST['adjustment_type']) : '';
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    $adjustment_date = isset($_POST['adjustment_date']) ? mysqli_real_escape_string($conn, $_POST['adjustment_date']) : date('Y-m-d');
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'pending';

    if (!$product_id || !$location_id || $quantity <= 0 || !$adjustment_type || !$reason) {
        echo "<script>alert('Semua field wajib harus diisi!'); history.back();</script>";
        exit;
    }

    // Get old data
    $res_old = mysqli_query($conn, "SELECT * FROM stock_adjustments WHERE id=$id");
    $old = mysqli_fetch_assoc($res_old);

    // Jika status berubah ke approved atau completed, validasi stock
    if (in_array($old['status'] ?? 'pending', ['pending', 'cancelled']) && in_array($status, ['approved', 'completed'])) {
        if ($adjustment_type === 'subtract') {
            $available_stock = check_available_stock($conn, $product_id, $location_id);

            if ($available_stock <= 0) {
                echo "<script>alert('Produk tidak tersedia di lokasi ini! Tidak dapat menyetujui adjustment.'); history.back();</script>";
                exit;
            }

            if ($quantity > $available_stock) {
                echo "<script>alert('Jumlah pengurangan melebihi stok tersedia! Stok tersedia: $available_stock'); history.back();</script>";
                exit;
            }
        }
    }

    // Get current stock
    $current_stock = check_available_stock($conn, $product_id, $location_id);

    // Calculate new stock
    $new_stock = $adjustment_type === 'add' ? $current_stock + $quantity : $current_stock - $quantity;

    mysqli_begin_transaction($conn);

    try {
        // Update adjustment
        $sql = "UPDATE stock_adjustments SET
            product_id = $product_id,
            location_id = $location_id,
            adjustment_type = '$adjustment_type',
            quantity = $quantity,
            old_stock = $current_stock,
            new_stock = $new_stock,
            reason = '$reason',
            notes = '$notes',
            adjustment_date = '$adjustment_date'
            WHERE id = $id";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception(mysqli_error($conn));
        }

        // Handle status transitions and update/rollback stock accordingly
        $old_status = $old['status'] ?? 'pending';

        // Transition: non-approved -> approved/completed  (apply stock changes)
        if (in_array($old_status, ['pending', 'cancelled']) && in_array($status, ['approved', 'completed'])) {
            // Check if stock record exists
            $check_stock = mysqli_query($conn, "SELECT id FROM stock WHERE product_id = $product_id AND location_id = $location_id");

            if (mysqli_num_rows($check_stock) > 0) {
                // Update existing stock
                if ($adjustment_type === 'add') {
                    $update_stock = "UPDATE stock SET current_stock = current_stock + $quantity, last_updated = NOW() 
                                   WHERE product_id = $product_id AND location_id = $location_id";
                } else {
                    $update_stock = "UPDATE stock SET current_stock = current_stock - $quantity, last_updated = NOW() 
                                   WHERE product_id = $product_id AND location_id = $location_id";
                }
            } else {
                // Insert new stock record (only for 'add' type)
                if ($adjustment_type === 'add') {
                    $update_stock = "INSERT INTO stock (product_id, location_id, current_stock, min_stock) 
                                   VALUES ($product_id, $location_id, $quantity, 10)";
                } else {
                    throw new Exception("Stock record tidak ditemukan untuk pengurangan!");
                }
            }

            if (!mysqli_query($conn, $update_stock)) {
                throw new Exception("Error updating stock: " . mysqli_error($conn));
            }

            // Update status to approved/completed
            if (!$has_sa_status) {
                throw new Exception("Database missing 'status' column on stock_adjustments. Cannot update status.");
            }
            $update_status = "UPDATE stock_adjustments SET status = '$status' WHERE id = $id";
            if (!mysqli_query($conn, $update_status)) {
                throw new Exception("Error updating status: " . mysqli_error($conn));
            }

            // Transition: approved/completed -> pending/cancelled (rollback stock changes)
        } elseif (in_array($old_status, ['approved', 'completed']) && in_array($status, ['pending', 'cancelled'])) {
            // We need to revert the previously applied stock change
            $check_stock = mysqli_query($conn, "SELECT id, current_stock FROM stock WHERE product_id = $product_id AND location_id = $location_id");

            if (mysqli_num_rows($check_stock) > 0) {
                // Reverse the change
                if ($adjustment_type === 'add') {
                    // previously added stock, now subtract
                    $update_stock = "UPDATE stock SET current_stock = current_stock - $quantity, last_updated = NOW() 
                                   WHERE product_id = $product_id AND location_id = $location_id";
                } else {
                    // previously subtracted stock, now add back
                    $update_stock = "UPDATE stock SET current_stock = current_stock + $quantity, last_updated = NOW() 
                                   WHERE product_id = $product_id AND location_id = $location_id";
                }
            } else {
                // No stock row exists — for reverting a subtract we can insert, for reverting an add there's nothing to decrement
                if ($adjustment_type === 'subtract') {
                    $update_stock = "INSERT INTO stock (product_id, location_id, current_stock, min_stock) 
                                   VALUES ($product_id, $location_id, $quantity, 10)";
                } else {
                    $update_stock = null; // nothing to do
                }
            }

            if ($update_stock) {
                if (!mysqli_query($conn, $update_stock)) {
                    throw new Exception("Error rolling back stock: " . mysqli_error($conn));
                }
            }

            // Update status to pending/cancelled
            if (!$has_sa_status) {
                throw new Exception("Database missing 'status' column on stock_adjustments. Cannot update status.");
            }
            $update_status = "UPDATE stock_adjustments SET status = '$status' WHERE id = $id";
            if (!mysqli_query($conn, $update_status)) {
                throw new Exception("Error updating status: " . mysqli_error($conn));
            }
        } else {
            // Other transitions: only update status if changed
            if ($status !== $old_status) {
                if (!$has_sa_status) {
                    throw new Exception("Database missing 'status' column on stock_adjustments. Cannot update status.");
                }
                $update_status = "UPDATE stock_adjustments SET status = '$status' WHERE id = $id";
                if (!mysqli_query($conn, $update_status)) {
                    throw new Exception("Error updating status: " . mysqli_error($conn));
                }
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('Stock Adjustment berhasil diupdate!'); location.href='index.php?page=inventory&sub=adjusment';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); history.back();</script>";
    }
    exit;
}

// DELETE Stock Adjustment
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Cek status (jika kolom 'status' ada)
    if ($has_sa_status) {
        $check = mysqli_query($conn, "SELECT status FROM stock_adjustments WHERE id=$id");
        if ($check && mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            if (in_array($row['status'], ['approved', 'completed'])) {
                echo "<script>alert('Tidak dapat menghapus adjustment yang sudah approved/completed!'); location.href='index.php?page=inventory&sub=adjusment';</script>";
                exit;
            }
        }
    }

    $sql = "DELETE FROM stock_adjustments WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data berhasil dihapus!'); location.href='index.php?page=inventory&sub=adjusment';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "'); location.href='index.php?page=inventory&sub=adjusment';</script>";
    }
    exit;
}

/* Filter & Search */
$where = [];

if (!empty($_GET['start_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
    $where[] = "sa.adjustment_date >= '$start_date'";
}

if (!empty($_GET['end_date'])) {
    $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
    $where[] = "sa.adjustment_date <= '$end_date'";
}

if (!empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    if ($has_sa_status) {
        $where[] = "sa.status = '$status'";
    } else {
        // status column not present in DB; ignore filter to avoid SQL error
    }
}

if (!empty($_GET['type'])) {
    $type = mysqli_real_escape_string($conn, $_GET['type']);
    $where[] = "sa.adjustment_type = '$type'";
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where[] = "(p.name LIKE '%$search%' OR sa.adjustment_code LIKE '%$search%' OR sa.reason LIKE '%$search%')";
}

$whereSQL = '';
if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

// Pagination
$limit = 10;
$page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$offset = ($page - 1) * $limit;

// Count total records
$countSql = "SELECT COUNT(*) as total 
             FROM stock_adjustments sa 
             LEFT JOIN products p ON sa.product_id = p.id 
             LEFT JOIN locations l ON sa.location_id = l.id 
             " . $whereSQL;
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $limit);

// Get data with stock info
$sql = "SELECT sa.*, 
        p.name AS product_name, 
        p.product_code,
        l.name AS location_name,
        COALESCE(s.current_stock, 0) AS available_stock
        FROM stock_adjustments sa
        LEFT JOIN products p ON sa.product_id = p.id
        LEFT JOIN locations l ON sa.location_id = l.id
        LEFT JOIN stock s ON s.product_id = sa.product_id AND s.location_id = sa.location_id
        " . $whereSQL . "
        ORDER BY sa.adjustment_date DESC, sa.id DESC
        LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $sql);
?>

<div class="flex items-center justify-between mb-4 mt-3">
    <div>
        <h1 class="text-2xl px-4 font-bold text-[#092363]">Stock Adjustment</h1>
    </div>

    <?php if ($isStaff): ?>
        <div class="flex justify-end items-center">
            <button
                onclick="openModal('adjusmentModal')"
                class="bg-[#092363] font-bold text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
                <i class='bx bx-plus text-lg'></i>
                <span>Tambah Adjustment</span>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Filter box -->
<div class="bg-white p-4 rounded-md shadow-md mb-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <input type="hidden" name="page" value="inventory">
        <input type="hidden" name="sub" value="adjusment">

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type</label>
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                <option value="">Semua Type</option>
                <option value="add" <?= ($_GET['type'] ?? '') == 'add' ? 'selected' : '' ?>>Add (Tambah)</option>
                <option value="subtract" <?= ($_GET['type'] ?? '') == 'subtract' ? 'selected' : '' ?>>Subtract (Kurangi)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                <option value="">Semua Status</option>
                <option value="pending" <?= ($_GET['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($_GET['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="completed" <?= ($_GET['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= ($_GET['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <div class="flex gap-2">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cari</label>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    placeholder="Produk / kode / alasan..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
            </div>
            <button type="submit" class="bg-[#092363] text-white px-4 py-2 rounded-lg hover:bg-[#e6b949] hover:text-[#092363] transition-colors h-[38px] mt-auto">
                <i class='bx bx-search'></i>
            </button>
        </div>
    </form>
</div>

<!-- Table Stock Adjustment -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center">Date</th>
                <th class="px-4 py-3 text-center">Adjustment ID</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3 text-center">Location</th>
                <th class="px-4 py-3 text-center">Type</th>
                <th class="px-4 py-3 text-center">Quantity</th>
                <th class="px-4 py-3 text-center">Stock Tersedia</th>
                <th class="px-4 py-3 text-center">Reason</th>
                <th class="px-4 py-3 text-center">Status</th>
                <?php if ($isAdmin): ?>
                    <th class="px-4 py-3 text-center">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500">
                            <?= !empty($row['adjustment_date']) ? date('d M Y', strtotime($row['adjustment_date'])) : '-' ?>
                        </td>

                        <td class="px-4 py-3 text-center font-semibold text-[#092363]">
                            <?= htmlspecialchars($row['adjustment_code']) ?>
                        </td>

                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                            <?= htmlspecialchars($row['product_name']) ?>
                        </td>

                        <td class="px-4 py-3 text-center text-gray-600">
                            <?= htmlspecialchars($row['location_name']) ?>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php
                            $type = $row['adjustment_type'];
                            $typeColor = $type == 'add' ? 'text-green-600' : 'text-red-600';
                            $typeIcon = $type == 'add' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt';
                            $typeText = $type == 'add' ? 'Add' : 'Subtract';
                            ?>
                            <span class="<?= $typeColor ?> font-bold flex items-center justify-center gap-1">
                                <i class='bx <?= $typeIcon ?>'></i>
                                <?= $typeText ?>
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center font-bold <?= $row['adjustment_type'] == 'add' ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $row['adjustment_type'] == 'add' ? '+' : '-' ?><?= $row['quantity'] ?>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php
                            $stock_class = $row['available_stock'] >= $row['quantity'] ? 'text-green-600' : 'text-red-600';
                            ?>
                            <span class="<?= $stock_class ?> font-bold"><?= $row['available_stock'] ?></span>
                        </td>

                        <td class="px-4 py-3 text-center text-sm text-gray-600">
                            <?= htmlspecialchars($row['reason']) ?>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php
                            $status = $row['status'] ?? 'pending';
                            $statusColor = match ($status) {
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'approved' => 'bg-blue-100 text-blue-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700'
                            };
                            ?>
                            <span class="<?= $statusColor ?> text-xs px-3 py-1 rounded-full font-bold">
                                <?= ucfirst($status) ?>
                            </span>
                        </td>

                        <?php if ($isAdmin): ?>
                            <td class="px-4 py-3 flex gap-2 justify-center">
                                <button
                                    onclick="openEditModal('editAdjusmentModal', this)"
                                    data-id="<?= $row['id'] ?>"
                                    data-adjustment_code="<?= htmlspecialchars($row['adjustment_code']) ?>"
                                    data-product_id="<?= $row['product_id'] ?>"
                                    data-location_id="<?= $row['location_id'] ?>"
                                    data-quantity="<?= $row['quantity'] ?>"
                                    data-adjustment_type="<?= $row['adjustment_type'] ?>"
                                    data-reason="<?= htmlspecialchars($row['reason']) ?>"
                                    data-notes="<?= htmlspecialchars($row['notes']) ?>"
                                    data-adjustment_date="<?= $row['adjustment_date'] ?>"
                                    data-status="<?= $row['status'] ?? 'pending' ?>"
                                    data-available_stock="<?= $row['available_stock'] ?>"
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-105 transition-all cursor-pointer">
                                    <i class='bx bxs-edit text-lg'></i>
                                </button>

                                <?php if (!in_array($row['status'] ?? 'pending', ['approved', 'completed'])): ?>
                                    <button
                                        onclick="if(confirm('Are you sure to delete this adjustment?')){ window.location.href='index.php?page=inventory&sub=adjusment&delete=<?= $row['id'] ?>' }"
                                        class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-105 transition-all cursor-pointer">
                                        <i class='bx bxs-trash text-lg'></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 10 : 9 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-adjust text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada data Stock Adjustment</p>
                        <p class="text-sm mt-1">Silakan tambah Stock Adjustment terlebih dahulu</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center px-6 mb-10">
    <span class="text-sm text-gray-500">
        Showing <?= min(($page - 1) * $limit + 1, $totalRecords) ?> to <?= min($page * $limit, $totalRecords) ?> of <?= $totalRecords ?> entries
    </span>
    <div class="flex gap-1">
        <?php if ($page > 1): ?>
            <a href="?page=inventory&sub=adjusment&page_num=<?= $page - 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= !empty($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= !empty($_GET['type']) ? '&type=' . $_GET['type'] : '' ?>"
                class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">
                Prev
            </a>
        <?php else: ?>
            <span class="px-3 py-1 rounded border bg-gray-100 border-gray-300 text-gray-400 text-sm font-bold cursor-not-allowed">Prev</span>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=inventory&sub=adjusment&page_num=<?= $i ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= !empty($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= !empty($_GET['type']) ? '&type=' . $_GET['type'] : '' ?>"
                class="px-3 py-1 rounded border <?= $i == $page ? 'border-[#092363] bg-[#092363] text-white' : 'border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white' ?> text-sm">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=inventory&sub=adjusment&page_num=<?= $page + 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= !empty($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= !empty($_GET['type']) ? '&type=' . $_GET['type'] : '' ?>"
                class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">
                Next
            </a>
        <?php else: ?>
            <span class="px-3 py-1 rounded border bg-gray-100 border-gray-300 text-gray-400 text-sm font-bold cursor-not-allowed">Next</span>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Create Stock Adjustment -->
<div id="adjusmentModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('adjusmentModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Buat Stock Adjustment Baru</h3>
                <button onclick="closeModal('adjusmentModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="index.php?page=inventory&sub=adjusment" method="POST" class="p-6 space-y-4">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Produk</label>
                        <select name="product_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Produk</option>
                            <?php
                            $q = mysqli_query($conn, "SELECT id, name, product_code FROM products ORDER BY name ASC");
                            while ($p = mysqli_fetch_assoc($q)) {
                                echo "<option value='{$p['id']}'>[{$p['product_code']}] {$p['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Location</label>
                        <select name="location_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Location</option>
                            <?php
                            $query = "SELECT id, name FROM locations ORDER BY name ASC";
                            $locationResult = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($locationResult)) {
                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type </label>
                        <select name="adjustment_type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Type</option>
                            <option value="add">Increase (Tambah)</option>
                            <option value="subtract">Decrease (Kurangi)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah</label>
                        <input type="number" name="quantity" min="1" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none"
                            placeholder="0">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal</label>
                        <input type="date" name="adjustment_date" required value="<?= date('Y-m-d') ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason </label>
                    <select name="reason" required id="reasonSelect"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">Pilih Alasan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none resize-none"
                        placeholder="Catatan tambahan..."></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('adjusmentModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Buat Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Stock Adjustment -->
<div id="editAdjusmentModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editAdjusmentModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Stock Adjustment</h3>
                <button onclick="closeModal('editAdjusmentModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="index.php?page=inventory&sub=adjusment" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="old_status" id="edit_old_status">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Adjustment ID</label>
                    <input type="text" id="edit_adjustment_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Produk</label>
                        <select name="product_id" required id="edit_product_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Produk</option>
                            <?php
                            $q = mysqli_query($conn, "SELECT id, name, product_code FROM products ORDER BY name ASC");
                            while ($p = mysqli_fetch_assoc($q)) {
                                echo "<option value='{$p['id']}'>[{$p['product_code']}] {$p['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Location</label>
                        <select name="location_id" required id="edit_location_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Location</option>
                            <?php
                            $query = "SELECT id, name FROM locations ORDER BY name ASC";
                            $locationResult = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($locationResult)) {
                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type </label>
                        <select name="adjustment_type" required id="edit_adjustment_type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Type</option>
                            <option value="add">Increase (Tambah)</option>
                            <option value="subtract">Decrease (Kurangi)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah </label>
                        <input type="number" name="quantity" min="1" required id="edit_quantity"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none"
                            placeholder="0">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal </label>
                        <input type="date" name="adjustment_date" required id="edit_adjustment_date"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason </label>
                    <select name="reason" required id="edit_reason"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">Pilih Alasan</option>
                        <option value="Damaged goods">Barang Rusak</option>
                        <option value="Expired">Kadaluarsa</option>
                        <option value="Lost">Hilang</option>
                        <option value="Found">Ditemukan</option>
                        <option value="Correction">Koreksi Stok</option>
                        <option value="Return">Retur</option>
                        <option value="Other">Lainnya</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status </label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none cursor-pointer">
                        <option value="pending">Pending (Menunggu)</option>
                        <option value="approved">Approved (Disetujui)</option>
                        <option value="completed">Completed (Selesai)</option>
                        <option value="cancelled">Cancelled (Dibatalkan)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Notes</label>
                    <textarea name="notes" rows="2" id="edit_notes"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none resize-none"
                        placeholder="Catatan tambahan..."></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('editAdjusmentModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const typeSelect = document.querySelector('select[name="adjustment_type"]');
    const reasonSelect = document.getElementById('reasonSelect');

    const reasons = {
        add: [
            {value: 'Found', text: 'Ditemukan'},
            {value: 'Correction', text: 'Koreksi Stok'},
            {value: 'Return', text: 'Retur'}
        ],
        subtract: [
            {value: 'Damaged goods', text: 'Barang Rusak'},
            {value: 'Expired', text: 'Kadaluarsa'},
            {value: 'Lost', text: 'Hilang'},
            {value: 'Other', text: 'Lainnya'}
        ]
    };

    function updateReasons() {
        const selectedType = typeSelect.value;
        reasonSelect.innerHTML = '<option value="">Pilih Alasan</option>'; // reset
        if (selectedType && reasons[selectedType]) {
            reasons[selectedType].forEach(r => {
                const option = document.createElement('option');
                option.value = r.value;
                option.text = r.text;
                reasonSelect.appendChild(option);
            });
        }
    }

    typeSelect.addEventListener('change', updateReasons);

    // Optional: load default if type sudah terisi (misal saat edit)
    updateReasons();
</script>