<?php
include __DIR__ . '/../../../config/database.php';


// Detect available date column name in `stock_out` table to avoid
// referencing a non-existing column (fixes Unknown column errors).
$date_field = null;
$stock_out_cols = [];
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM stock_out");
if ($colsRes) {
    while ($col = mysqli_fetch_assoc($colsRes)) {
        $field = $col['Field'];
        $stock_out_cols[] = $field;
        if (in_array($field, ['out_date', 'stock_out_date', 'date', 'created_at', 'created'])) {
            $date_field = $field;
        }
    }
}

// Flags for optional columns
$has_category = in_array('category_id', $stock_out_cols);
$has_location = in_array('location_id', $stock_out_cols);
$has_product = in_array('product_id', $stock_out_cols);
$has_stock_out_code = in_array('stock_out_code', $stock_out_cols);
$has_reason = in_array('reason', $stock_out_cols);
$has_notes = in_array('notes', $stock_out_cols);
$has_status = in_array('status', $stock_out_cols);
$has_quantity = in_array('quantity', $stock_out_cols);

// Helper: Check available stock for a product at a location
function check_available_stock_so($conn, $product_id, $location_id)
{
    $product_id = (int)$product_id;
    $location_id = (int)$location_id;
    $sql = "SELECT current_stock FROM stock WHERE product_id = $product_id AND location_id = $location_id";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return (int)$row['current_stock'];
    }
    return 0;
}

// ======================
// HANDLE FORM SUBMISSIONS
// ======================

// CREATE Stock Out
// Add this to the column detection section (after SHOW COLUMNS query)
$has_old_stock = in_array('old_stock', $stock_out_cols);
$has_stock_out_date = in_array('stock_out_date', $stock_out_cols);

// Then in the CREATE handler, update the INSERT building section:

if (isset($_POST['create'])) {
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $out_date = isset($_POST['out_date']) ? mysqli_real_escape_string($conn, $_POST['out_date']) : null;
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    $product_ids = isset($_POST['product_id']) && is_array($_POST['product_id']) ? $_POST['product_id'] : [];
    $quantities = isset($_POST['quantity']) && is_array($_POST['quantity']) ? $_POST['quantity'] : [];

    // Generate stock_out_code
    $stock_out_code = 'OUT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Respect optional status from the form: default to pending
        $requested_status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : null;
        $appliedStates = ['completed', 'approved'];

        // Insert each product as separate stock_out record. Do NOT change stock when status is pending.
        foreach ($product_ids as $index => $product_id) {
            if (empty($product_id) || empty($quantities[$index])) continue;

            $quantity = (int)$quantities[$index];

            // Capture current stock for historical reference but do NOT deduct unless requested_status is applied
            $available = check_available_stock_so($conn, $product_id, $location_id);

            // Block creation if requested quantity exceeds available stock at the location
            if ($available < $quantity) {
                throw new Exception("Produk (ID $product_id) stok tidak mencukupi di lokasi ini (tersedia: $available).");
            }

            // Get category_id (if present)
            $category_id = null;
            if ($has_category) {
                $productQuery = mysqli_query($conn, "SELECT category_id FROM products WHERE id = " . (int)$product_id);
                if ($productQuery) {
                    $productData = mysqli_fetch_assoc($productQuery);
                    $category_id = $productData['category_id'] ?? null;
                }
            }

            // Build INSERT SQL dynamically
            $insertCols = [];
            $insertVals = [];

            if ($has_stock_out_code) {
                $insertCols[] = 'stock_out_code';
                $insertVals[] = "'$stock_out_code'";
            }
            if ($has_product) {
                $insertCols[] = 'product_id';
                $insertVals[] = (int)$product_id;
            }
            if ($has_category) {
                $insertCols[] = 'category_id';
                $insertVals[] = ($category_id !== null) ? $category_id : 'NULL';
            }
            if ($has_location) {
                $insertCols[] = 'location_id';
                $insertVals[] = ($location_id !== null) ? $location_id : 'NULL';
            }
            if ($has_quantity) {
                $insertCols[] = 'quantity';
                $insertVals[] = $quantity;
            }
            if ($has_old_stock) {
                $insertCols[] = 'old_stock';
                $insertVals[] = (int)$available;
            }
            if ($has_reason) {
                $insertCols[] = 'reason';
                $insertVals[] = "'" . $reason . "'";
            }
            if ($has_notes) {
                $insertCols[] = 'notes';
                $insertVals[] = "'" . $notes . "'";
            }
            if ($has_stock_out_date && $out_date) {
                $insertCols[] = 'stock_out_date';
                $insertVals[] = "'" . $out_date . "'";
            } elseif ($date_field && $out_date) {
                $insertCols[] = $date_field;
                $insertVals[] = "'" . $out_date . "'";
            }
            if ($has_status) {
                $insertCols[] = 'status';
                $insertVals[] = ($requested_status !== null) ? ("'" . $requested_status . "'") : "'pending'";
            }

            $sql = "INSERT INTO stock_out (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertVals) . ")";

            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error inserting stock out: " . mysqli_error($conn));
            }

            // If the create explicitly requested an applied status, subtract now
            if (in_array($requested_status, $appliedStates, true)) {
                $check = mysqli_query($conn, "SELECT id FROM stock WHERE product_id = " . (int)$product_id . " AND location_id = " . (int)$location_id);
                if (mysqli_num_rows($check) > 0) {
                    $update_stock = "UPDATE stock SET current_stock = current_stock - $quantity, last_updated = NOW() WHERE product_id = " . (int)$product_id . " AND location_id = " . (int)$location_id;
                    if (!mysqli_query($conn, $update_stock)) {
                        throw new Exception("Error updating stock: " . mysqli_error($conn));
                    }
                    // applied stock deduction
                } else {
                    throw new Exception("Stock record tidak ditemukan untuk product_id=$product_id di lokasi $location_id");
                }
            } else {
                // deferred stock deduction for pending status
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('Stock Out berhasil ditambahkan!'); location.href='index.php?page=inventory&sub=out';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}


if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM stock_out WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data berhasil dihapus!'); location.href='index.php?page=inventory&sub=out';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// UPDATE Stock Out
if (isset($_POST['update'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $out_date = isset($_POST['out_date']) ? mysqli_real_escape_string($conn, $_POST['out_date']) : null;
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    $product_id = isset($_POST['product_id'][0]) ? (int)$_POST['product_id'][0] : null; // Single product for edit
    $quantity = isset($_POST['quantity'][0]) ? (int)$_POST['quantity'][0] : 0;
    $new_status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : null;

    // Fetch old record to detect status/quantity/product/location changes
    $oldRow = [];
    $oldRes = mysqli_query($conn, "SELECT * FROM stock_out WHERE id = " . (int)$id);
    if ($oldRes && mysqli_num_rows($oldRes) > 0) {
        $oldRow = mysqli_fetch_assoc($oldRes);
    }
    $old_status = $oldRow['status'] ?? null;
    $old_quantity = isset($oldRow['quantity']) ? (int)$oldRow['quantity'] : 0;
    $old_product = isset($oldRow['product_id']) ? (int)$oldRow['product_id'] : null;
    $old_location = isset($oldRow['location_id']) ? (int)$oldRow['location_id'] : null;

    // Get category_id from product (if present)
    $category_id = null;
    if ($has_category && $product_id) {
        $productQuery = mysqli_query($conn, "SELECT category_id FROM products WHERE id = " . (int)$product_id);
        if ($productQuery) {
            $productData = mysqli_fetch_assoc($productQuery);
            $category_id = $productData['category_id'] ?? null;
        }
    }

    // Build UPDATE dynamically depending on available columns
    $setParts = [];
    if ($has_product) {
        $setParts[] = "product_id = " . ($product_id !== null ? $product_id : 'NULL');
    }
    if ($has_category) {
        $setParts[] = "category_id = " . ($category_id !== null ? $category_id : 'NULL');
    }
    if ($has_location) {
        $setParts[] = "location_id = " . ($location_id !== null ? $location_id : 'NULL');
    }
    if ($has_quantity) {
        $setParts[] = "quantity = " . (int)$quantity;
    }
    if ($has_reason) {
        $setParts[] = "reason = '" . $reason . "'";
    }
    if ($has_notes) {
        $setParts[] = "notes = '" . $notes . "'";
    }
    if ($has_status && $new_status !== null) {
        $setParts[] = "status = '" . $new_status . "'";
    }
    if ($date_field) {
        $setParts[] = $date_field . " = " . ($out_date ? "'" . $out_date . "'" : 'NULL');
    }

    $sql = "UPDATE stock_out SET " . implode(",\n            ", $setParts) . " WHERE id = " . (int)$id;


    // Validate requested quantity against available stock (consider previous reservation if applied)
    $available_new = check_available_stock_so($conn, $product_id, $location_id);
    $effective_available = $available_new;
    if (in_array($old_status, $appliedStates, true)) {
        // If the record was applied previously and it's the same product/location, its old reservation frees up
        if ($old_product === $product_id && $old_location === $location_id) {
            $effective_available = $available_new + $old_quantity;
        }
    }
    if ($effective_available < $quantity) {
        echo "<script>alert('Error: Stok tidak mencukupi untuk perubahan ini. Tersedia: " . $effective_available . "');</script>";
        exit;
    }

    // Run update inside transaction and apply/rollback stock based on status transitions
    mysqli_begin_transaction($conn);
    try {
        if (!mysqli_query($conn, $sql)) {
            throw new Exception('Update error: ' . mysqli_error($conn));
        }

        // Handle status transition: apply (subtract) when moving to completed/approved,
        // rollback (add back) when moving from applied to pending/cancelled
        $appliedStates = ['completed', 'approved'];
        $notAppliedStates = ['pending', 'cancelled', null, ''];

        if ($has_status && $new_status !== null && $new_status !== $old_status) {
            // If moving from not-applied -> applied
            if (in_array($old_status, $notAppliedStates, true) && in_array($new_status, $appliedStates, true)) {
                // subtract from stock at the (possibly updated) product/location
                $available = check_available_stock_so($conn, $product_id, $location_id);
                if ($available < $quantity) {
                    throw new Exception('Stok tidak mencukupi untuk menerapkan perubahan status. Tersedia: ' . $available);
                }
                $check = mysqli_query($conn, "SELECT id FROM stock WHERE product_id = " . (int)$product_id . " AND location_id = " . (int)$location_id);
                if (mysqli_num_rows($check) > 0) {
                    $u = "UPDATE stock SET current_stock = current_stock - $quantity, last_updated = NOW() WHERE product_id = " . (int)$product_id . " AND location_id = " . (int)$location_id;
                    if (!mysqli_query($conn, $u)) throw new Exception('Error updating stock: ' . mysqli_error($conn));
                } else {
                    throw new Exception('Stock record tidak ditemukan untuk product saat menerapkan status.');
                }
            }

            // If moving from applied -> not-applied (rollback)
            if (in_array($old_status, $appliedStates, true) && in_array($new_status, $notAppliedStates, true)) {
                if ($old_product !== null) {
                    $check = mysqli_query($conn, "SELECT id FROM stock WHERE product_id = " . (int)$old_product . " AND location_id = " . (int)$old_location);
                    if (mysqli_num_rows($check) > 0) {
                        $u = "UPDATE stock SET current_stock = current_stock + $old_quantity, last_updated = NOW() WHERE product_id = " . (int)$old_product . " AND location_id = " . (int)$old_location;
                        if (!mysqli_query($conn, $u)) throw new Exception('Error restoring stock: ' . mysqli_error($conn));
                    } else {
                        // If stock row doesn't exist, attempt to create it
                        $ins = "INSERT INTO stock (product_id, location_id, current_stock, last_updated) VALUES (" . (int)$old_product . ", " . (int)$old_location . ", " . (int)$old_quantity . ", NOW())";
                        if (!mysqli_query($conn, $ins)) throw new Exception('Error creating stock record during rollback: ' . mysqli_error($conn));
                    }
                }
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('Stock Out berhasil diupdate!'); location.href='index.php?page=inventory&sub=out';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

/* ======================
   FILTER & SEARCH
====================== */
$where = [];

if (!empty($_GET['category_id']) && $has_category) {
    $where[] = "so.category_id = " . (int)$_GET['category_id'];
}

if (!empty($_GET['location_id']) && $has_location) {
    $where[] = "so.location_id = " . (int)$_GET['location_id'];
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $searchParts = [];
    if ($has_product) {
        $searchParts[] = "p.name LIKE '%$search%'";
    }
    if ($has_stock_out_code) {
        $searchParts[] = "so.stock_out_code LIKE '%$search%'";
    }
    if ($has_reason) {
        $searchParts[] = "so.reason LIKE '%$search%'";
    }
    if (!empty($searchParts)) {
        $where[] = '(' . implode(' OR ', $searchParts) . ')';
    }
}

$whereSQL = '';
if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

// Pagination - Fixed: Use 'p_num' to avoid conflict with main page navigation
$limit = 10;
$currentPage = isset($_GET['p_num']) ? max(1, (int)$_GET['p_num']) : 1;
$offset = ($currentPage - 1) * $limit;

$joins = '';
if ($has_product) {
    $joins .= " LEFT JOIN products p ON so.product_id = p.id ";
}
if ($has_category) {
    $joins .= " LEFT JOIN categories c ON so.category_id = c.id ";
}
if ($has_location) {
    $joins .= " LEFT JOIN locations l ON so.location_id = l.id ";
}

// Count total records
$countSql = "SELECT COUNT(*) as total FROM stock_out so " . $joins . " " . $whereSQL;
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $limit);

// Calculate showing range
$showingStart = $totalRecords > 0 ? $offset + 1 : 0;
$showingEnd = min($offset + $limit, $totalRecords);

// Prepare date selection and ordering depending on available column
$orderByDate = $date_field ? "so.$date_field DESC, " : "";

// Build SELECT list safely to avoid trailing commas when optional parts are missing
$selectParts = [];
$selectParts[] = 'so.id';
$selectParts[] = $has_stock_out_code ? 'so.stock_out_code' : 'NULL AS stock_out_code';
$selectParts[] = 'so.quantity';
$selectParts[] = $has_reason ? 'so.reason' : 'NULL AS reason';
$selectParts[] = 'so.notes';
$selectParts[] = 'so.status';
$selectParts[] = $date_field ? "so.$date_field AS out_date" : 'NULL AS out_date';

if ($has_product) {
    $selectParts[] = 'p.id AS product_id';
    $selectParts[] = 'p.name AS product_name';
} else {
    $selectParts[] = 'NULL AS product_id';
    $selectParts[] = 'NULL AS product_name';
}

if ($has_category) {
    $selectParts[] = 'c.name AS category_name';
} else {
    $selectParts[] = 'NULL AS category_name';
}

if ($has_location) {
    $selectParts[] = 'l.name AS location_name';
} else {
    $selectParts[] = 'NULL AS location_name';
}

$sql = "SELECT\n    " . implode(",\n    ", $selectParts) . "\nFROM stock_out so\n" . $joins . "\n" . $whereSQL . "\nORDER BY " . $orderByDate . "so.id DESC\nLIMIT $limit OFFSET $offset\n";

$result = mysqli_query($conn, $sql);

// Build pagination URL
function buildPaginationUrl($pageNum)
{
    $params = $_GET;
    $params['p_num'] = $pageNum;
    unset($params['delete']); // Remove delete param if present
    return 'index.php?' . http_build_query($params);
}
?>

<div class="flex items-center justify-between mb-4 mt-3">
    <div>
        <h1 class="text-2xl px-4 font-bold text-[#092363]">Stock Out</h1>
    </div>

    <?php if ($isStaff): ?>
        <div class="flex justify-end items-center">
            <button
                onclick="openModal('outModal')"
                class="bg-[#092363] font-bold text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
                <i class='bx bx-plus text-lg'></i>
                <span>Tambah Stock Out</span>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Search box & Filter Group -->
<div class="w-full flex justify-between gap-2 mb-6">
    <!-- Search box -->
    <form action="" method="GET" class="flex items-center gap-3 bg-white px-3 py-2 rounded-md shadow-md w-full">
        <i class='bx bx-search text-xl text-gray-500'></i>
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search by product, code, or reason..."
            class="w-full ml-2 focus:outline-none">
        <?php if (!empty($_GET['category_id'])): ?>
            <input type="hidden" name="category_id" value="<?= $_GET['category_id'] ?>">
        <?php endif; ?>
        <?php if (!empty($_GET['location_id'])): ?>
            <input type="hidden" name="location_id" value="<?= $_GET['location_id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="page" value="inventory">
        <input type="hidden" name="sub" value="out">
    </form>

    <!-- Filter Group -->
    <div>
        <form action="" method="GET" class="flex gap-2 justify-end">
            <input type="hidden" name="page" value="inventory">
            <input type="hidden" name="sub" value="out">
            <?php if (!empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
            <?php endif; ?>

            <!-- Filter Category -->
            <select name="category_id"
                class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer"
                onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php
                $query = "SELECT id, name FROM categories ORDER BY name ASC";
                $catResult = mysqli_query($conn, $query);
                while ($row = mysqli_fetch_assoc($catResult)) {
                    $selected = (!empty($_GET['category_id']) && $_GET['category_id'] == $row['id']) ? 'selected' : '';
                    echo '<option value="' . $row['id'] . '" ' . $selected . '>' . $row['name'] . '</option>';
                }
                ?>
            </select>

            <!-- Filter Location -->
            <select name="location_id"
                class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer"
                onchange="this.form.submit()">
                <option value="">All Locations</option>
                <?php
                $query = "SELECT id, name FROM locations ORDER BY name ASC";
                $locResult = mysqli_query($conn, $query);
                while ($row = mysqli_fetch_assoc($locResult)) {
                    $selected = (!empty($_GET['location_id']) && $_GET['location_id'] == $row['id']) ? 'selected' : '';
                    echo '<option value="' . $row['id'] . '" ' . $selected . '>' . $row['name'] . '</option>';
                }
                ?>
            </select>
        </form>
    </div>
</div>

<!-- Table Stock Out -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center">Date</th>
                <th class="px-4 py-3 text-center">Stock Out ID</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3 text-center">Location</th>
                <th class="px-4 py-3 text-center">Quantity</th>
                <th class="px-4 py-3 text-center">Reason</th>
                <th class="px-4 py-3 text-center">Notes</th>
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
                            <?= !empty($row['out_date']) ? date('d M Y', strtotime($row['out_date'])) : '-' ?>
                        </td>

                        <td class="px-4 py-3 text-center font-semibold">
                            <?= htmlspecialchars($row['stock_out_code']) ?>
                        </td>

                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                            <?= htmlspecialchars($row['product_name']) ?>
                        </td>

                        <td class="px-4 py-3 text-center text-gray-600">
                            <?= htmlspecialchars($row['location_name']) ?>
                        </td>

                        <td class="px-4 py-3 text-center font-bold text-yellow-600">
                            <?= $row['quantity'] ?>
                        </td>

                        <td class="px-4 py-3 text-center text-md font-bold text-gray-600">
                            <?= htmlspecialchars($row['reason']) ?>
                        </td>

                        <td class="px-4 py-3 text-center text-sm text-gray-600">
                            <?= htmlspecialchars($row['notes']) ?>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php
                            $statusColor = match ($row['status']) {
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'approved' => 'bg-blue-100 text-blue-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700'
                            };
                            ?>
                            <span class="<?= $statusColor ?> text-xs px-3 py-1 rounded-full font-bold">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>

                        <?php if ($isAdmin): ?>
                            <td class="px-4 py-3 flex gap-2 justify-center">
                                <button
                                    onclick="openEditModal('editOutModal', this)"
                                    data-id='<?= $row['id'] ?>'
                                    data-stock_out_code='<?= htmlspecialchars($row['stock_out_code']) ?>'
                                    data-product_id='<?= $row['product_id'] ?>'
                                    data-location_id='<?= $row['location_id'] ?>'
                                    data-quantity='<?= $row['quantity'] ?>'
                                    data-reason='<?= htmlspecialchars($row['reason']) ?>'
                                    data-notes='<?= htmlspecialchars($row['notes']) ?>'
                                    data-out_date='<?= $row['out_date'] ?>'
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500">
                                    <i class='bx bxs-edit'></i>
                                </button>

                                <button
                                    onclick="if(confirm('Are you sure you want to delete this stock out?')) { window.location.href='index.php?page=inventory&sub=out&delete=<?= $row['id'] ?>'; } return false;"
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">
                                    <i class='bx bxs-trash'></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 10 : 9 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-package text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada data Stock Out</p>
                        <p class="text-sm mt-1">Silakan tambah Stock Out terlebih dahulu</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center px-6 mb-10">
    <span class="text-sm text-gray-500">
        Showing <?= $showingStart ?> to <?= $showingEnd ?> of <?= $totalRecords ?> entries
    </span>
    <div class="flex gap-1">
        <?php if ($currentPage > 1): ?>
            <a href="<?= buildPaginationUrl($currentPage - 1) ?>"
                class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">
                Prev
            </a>
        <?php else: ?>
            <span class="px-3 py-1 rounded border bg-gray-100 border-gray-300 text-gray-400 text-sm font-bold cursor-not-allowed">
                Prev
            </span>
        <?php endif; ?>

        <?php
        // Show page numbers
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        for ($i = $startPage; $i <= $endPage; $i++):
            if ($i == $currentPage): ?>
                <span class="px-3 py-1 rounded border border-[#092363] bg-[#092363] text-white text-sm">
                    <?= $i ?>
                </span>
            <?php else: ?>
                <a href="<?= buildPaginationUrl($i) ?>"
                    class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white text-sm">
                    <?= $i ?>
                </a>
        <?php endif;
        endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= buildPaginationUrl($currentPage + 1) ?>"
                class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">
                Next
            </a>
        <?php else: ?>
            <span class="px-3 py-1 rounded border bg-gray-100 border-gray-300 text-gray-400 text-sm font-bold cursor-not-allowed">
                Next
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Create Stock Out - FIXED VERSION -->
<div id="outModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('outModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Buat Stock Out Baru</h3>
                <button onclick="closeModal('outModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <!-- FIXED: Added proper form action and hidden inputs -->
            <form action="" method="POST" class="p-6 space-y-4">

                <div id="product-wrapper">
                    <!-- ITEM -->
                    <div class="product-item grid grid-cols-3 gap-4 mb-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Produk </label>
                            <select name="product_id[]" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none">
                                <option value="">Pilih Produk</option>
                                <?php
                                $q = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name ASC");
                                while ($p = mysqli_fetch_assoc($q)) {
                                    echo "<option value='{$p['id']}'>" . htmlspecialchars($p['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah</label>
                            <input type="number" name="quantity[]" min="1" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none"
                                placeholder="Qty">
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Location (Stok Keluar Dari) </label>
                    <select name="location_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none">
                        <option value="">Pilih Location</option>
                        <?php
                        $query = "SELECT id, name FROM locations ORDER BY name ASC";
                        $locResult = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($locResult)) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Stock Out</label>
                    <input type="date" name="out_date" required value="<?= date('Y-m-d') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                </div>

                <!-- Reason -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason</label>
                    <select name="reason" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none">
                        <option value="">Pilih Alasan</option>
                        <option value="sale">Penjualan</option>
                        <option value="damage">Barang Rusak</option>
                        <option value="expired">Kadaluarsa</option>
                        <option value="lost">Hilang</option>
                        <option value="sample">Sample / Promo</option>
                        <option value="adjustment">Penyesuaian Stok</option>
                        <option value="transfer">Transfer Gudang</option>
                    </select>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Notes</label>
                    <input type="text" name="notes"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none"
                        placeholder="Keterangan tambahan">
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('outModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Stock Out -->
<div id="editOutModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editOutModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Stock Out</h3>
                <button onclick="closeModal('editOutModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Stock Out Code</label>
                    <input type="text" id="edit_stock_out_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Produk</label>
                        <select name="product_id[]" id="edit_product_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none">
                            <option value="">Pilih Produk</option>
                            <?php
                            $q = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name ASC");
                            while ($p = mysqli_fetch_assoc($q)) {
                                echo "<option value='{$p['id']}'>{$p['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah</label>
                        <input type="number" name="quantity[]" id="edit_quantity" min="1" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none"
                            placeholder="Qty">
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Location</label>
                    <select name="location_id" id="edit_location_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none">
                        <option value="">Pilih Location</option>
                        <?php
                        $query = "SELECT id, name FROM locations ORDER BY name ASC";
                        $locResult = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($locResult)) {
                            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Stock Out</label>
                    <input type="date" name="out_date" id="edit_out_date" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason</label>
                    <select name="reason" id="edit_reason" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none">
                        <option value="">Pilih Alasan</option>
                        <option value="sale">Penjualan</option>
                        <option value="damage">Barang Rusak</option>
                        <option value="expired">Kadaluarsa</option>
                        <option value="lost">Hilang</option>
                        <option value="sample">Sample / Promo</option>
                        <option value="adjustment">Penyesuaian Stok</option>
                        <option value="transfer">Transfer Gudang</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Notes</label>
                    <input type="text" name="notes" id="edit_notes"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none"
                        placeholder="Keterangan tambahan">
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('editOutModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>