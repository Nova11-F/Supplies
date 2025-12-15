<?php
include __DIR__ . '/../../../config/database.php';

// UPDATE Stock (Min Stock & Current Stock)
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $min_stock = (int)$_POST['min_stock'];
    $current_stock = (int)$_POST['current_stock'];
    
    $sql = "UPDATE stock SET 
            min_stock = $min_stock,
            current_stock = $current_stock,
            last_updated = NOW()
            WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Stock berhasil diupdate!'); window.location.href='?page=inventory&sub=list';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// DELETE Stock
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM stock WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data berhasil dihapus!'); window.location.href='?page=inventory&sub=list';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

/* ======================
   FILTER & SEARCH
====================== */
$where = [];

if (!empty($_GET['supplier_id'])) {
    $where[] = "p.supplier_id = " . (int)$_GET['supplier_id'];
}

if (!empty($_GET['category_id'])) {
    $where[] = "p.category_id = " . (int)$_GET['category_id'];
}

if (!empty($_GET['location_id'])) {
    $where[] = "s.location_id = " . (int)$_GET['location_id'];
}

if (!empty($_GET['stock_status'])) {
    $status = $_GET['stock_status'];
    if ($status == 'out_of_stock') {
        $where[] = "s.current_stock = 0";
    } elseif ($status == 'low_stock') {
        $where[] = "s.current_stock < s.min_stock AND s.current_stock > 0";
    } elseif ($status == 'in_stock') {
        $where[] = "s.current_stock >= s.min_stock";
    }
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where[] = "(p.name LIKE '%$search%' OR p.product_code LIKE '%$search%')";
}

$whereSQL = '';
if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

// Pagination
$limit = 10;
$page_num = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$offset = ($page_num - 1) * $limit;

// Count total records
$countSql = "SELECT COUNT(*) as total FROM stock s
             JOIN products p ON p.id = s.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN locations l ON l.id = s.location_id
             $whereSQL";
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $limit);

// Calculate showing range
$showingStart = $totalRecords > 0 ? $offset + 1 : 0;
$showingEnd = min($offset + $limit, $totalRecords);

// GET ALL DATA with pagination
$result = mysqli_query($conn, "
SELECT 
    s.id,
    s.product_id,
    s.location_id,
    p.product_code,
    p.name AS product_name,
    p.category_id,
    c.name AS category_name,
    l.name AS location_name,
    s.min_stock,
    s.current_stock,
    s.last_updated
FROM stock s
JOIN products p ON p.id = s.product_id
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN locations l ON l.id = s.location_id
$whereSQL
ORDER BY p.name ASC
LIMIT $limit OFFSET $offset
");

// Build query string for pagination
$query_params = $_GET;
unset($query_params['page_num']);
unset($query_params['delete']); // Remove action params
$query_string = http_build_query($query_params);
?>

<div class="flex items-center justify-between mb-4 mt-3">
    <div>
        <h1 class="text-2xl px-4 font-bold text-[#092363]">Stock List</h1>
        <p class="text-sm text-gray-500 px-4 mt-1">Monitor and manage your inventory levels</p>
    </div>
</div>

<!-- Search box & Filter Group -->
<div class="w-full flex justify-between gap-2 mb-6">
    <!-- Search box -->
    <form action="" method="GET" class="flex items-center gap-3 bg-white px-3 py-2 rounded-md shadow-md w-full">
        <input type="hidden" name="page" value="inventory">
        <input type="hidden" name="sub" value="list">
        <i class='bx bx-search text-xl text-gray-500'></i>
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
               placeholder="Search by product name or code..."
               class="w-full ml-2 focus:outline-none">
        <?php if (!empty($_GET['supplier_id'])): ?>
            <input type="hidden" name="supplier_id" value="<?= $_GET['supplier_id'] ?>">
        <?php endif; ?>
        <?php if (!empty($_GET['category_id'])): ?>
            <input type="hidden" name="category_id" value="<?= $_GET['category_id'] ?>">
        <?php endif; ?>
        <?php if (!empty($_GET['location_id'])): ?>
            <input type="hidden" name="location_id" value="<?= $_GET['location_id'] ?>">
        <?php endif; ?>
        <?php if (!empty($_GET['stock_status'])): ?>
            <input type="hidden" name="stock_status" value="<?= $_GET['stock_status'] ?>">
        <?php endif; ?>
    </form>

    <!-- Filter Group -->
    <div>
        <form action="" method="GET" class="flex gap-2 justify-end">
            <input type="hidden" name="page" value="inventory">
            <input type="hidden" name="sub" value="list">
            <?php if (!empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
            <?php endif; ?>

            <!-- Filter Stock Status -->
            <select name="stock_status"
                class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer"
                onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="in_stock" <?= ($_GET['stock_status'] ?? '') == 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                <option value="low_stock" <?= ($_GET['stock_status'] ?? '') == 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                <option value="out_of_stock" <?= ($_GET['stock_status'] ?? '') == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
            </select>

            <!-- Filter Category -->
            <select name="category_id"
                class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer"
                onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php
                $categoryResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
                while ($cat = mysqli_fetch_assoc($categoryResult)) {
                    $selected = (!empty($_GET['category_id']) && $_GET['category_id'] == $cat['id']) ? 'selected' : '';
                    echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                }
                ?>
            </select>

            <!-- Filter Location -->
            <select name="location_id"
                class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer"
                onchange="this.form.submit()">
                <option value="">All Locations</option>
                <?php
                $locationResult = mysqli_query($conn, "SELECT id, name FROM locations ORDER BY name ASC");
                while ($loc = mysqli_fetch_assoc($locationResult)) {
                    $selected = (!empty($_GET['location_id']) && $_GET['location_id'] == $loc['id']) ? 'selected' : '';
                    echo '<option value="' . $loc['id'] . '" ' . $selected . '>' . htmlspecialchars($loc['name']) . '</option>';
                }
                ?>
            </select>

            <?php if (!empty($_GET['search']) || !empty($_GET['stock_status']) || !empty($_GET['category_id']) || !empty($_GET['location_id'])): ?>
                <a href="?page=inventory&sub=list" class="px-4 py-2 bg-red-500 text-white rounded-md shadow-md hover:bg-red-600 flex items-center gap-2">
                    <i class='bx bx-x'></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <?php
    $summary = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_items,
            SUM(CASE WHEN current_stock >= min_stock THEN 1 ELSE 0 END) as in_stock_count,
            SUM(CASE WHEN current_stock < min_stock AND current_stock > 0 THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock_count
        FROM stock
    ");
    $summary_data = mysqli_fetch_assoc($summary);
    ?>
    <div class="bg-blue-500 text-white p-4 rounded-lg shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">Total Items</p>
                <p class="text-3xl font-bold"><?= $summary_data['total_items'] ?></p>
            </div>
            <i class='bx bx-package text-4xl opacity-80'></i>
        </div>
    </div>
    <div class="bg-green-500 text-white p-4 rounded-lg shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">In Stock</p>
                <p class="text-3xl font-bold"><?= $summary_data['in_stock_count'] ?></p>
            </div>
            <i class='bx bx-check-circle text-4xl opacity-80'></i>
        </div>
    </div>
    <div class="bg-yellow-400 text-white p-4 rounded-lg shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">Low Stock</p>
                <p class="text-3xl font-bold"><?= $summary_data['low_stock_count'] ?></p>
            </div>
            <i class='bx bx-error text-4xl opacity-80'></i>
        </div>
    </div>
    <div class="bg-red-500 text-white p-4 rounded-lg shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">Out of Stock</p>
                <p class="text-3xl font-bold"><?= $summary_data['out_of_stock_count'] ?></p>
            </div>
            <i class='bx bx-x-circle text-4xl opacity-80'></i>
        </div>
    </div>
</div>

<!-- Table Stock List -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center">No</th>
                <th class="px-4 py-3 text-center">Product Code</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3 text-center">Location</th>
                <th class="px-4 py-3 text-center">Min Stock</th>
                <th class="px-4 py-3 text-center">Current Stock</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-center">Last Updated</th>
                <?php if ($isStaff): ?>
                    <th class="px-4 py-3 text-center">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php 
                $no = $offset + 1;
                while ($row = mysqli_fetch_assoc($result)): 
                    // Determine status
                    if ($row['current_stock'] == 0) {
                        $status = 'out_of_stock';
                        $status_label = 'Out of Stock';
                        $status_class = 'bg-red-100 text-red-700';
                        $stock_class = 'text-red-600';
                    } elseif ($row['current_stock'] < $row['min_stock']) {
                        $status = 'low_stock';
                        $status_label = 'Low Stock';
                        $status_class = 'bg-yellow-100 text-yellow-700';
                        $stock_class = 'text-yellow-600';
                    } else {
                        $status = 'in_stock';
                        $status_label = 'In Stock';
                        $status_class = 'bg-green-100 text-green-700';
                        $stock_class = 'text-green-600';
                    }
                ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500"><?= $no++ ?></td>
                        <td class="px-4 py-3 text-center text-gray-600 font-semibold">
                            <?= htmlspecialchars($row['product_code']) ?>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                            <?= htmlspecialchars($row['product_name']) ?>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">
                            <?= htmlspecialchars($row['location_name']) ?>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600 font-semibold">
                            <?= $row['min_stock'] ?>
                        </td>
                        <td class="px-4 py-3 text-center font-bold <?= $stock_class ?>">
                            <?= $row['current_stock'] ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-3 py-1 text-xs rounded-full font-bold <?= $status_class ?>">
                                <?= $status_label ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 text-sm">
                            <?= date('d M Y H:i', strtotime($row['last_updated'])) ?>
                        </td>
                        <?php if ($isStaff): ?>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 justify-center items-center">
                                    <!-- Edit Stock -->
                                    <button
                                        onclick="openEditModal('editStockModal', this)"
                                        data-id="<?= $row['id'] ?>"
                                        data-product_code="<?= htmlspecialchars($row['product_code']) ?>"
                                        data-product_name="<?= htmlspecialchars($row['product_name']) ?>"
                                        data-location_name="<?= htmlspecialchars($row['location_name']) ?>"
                                        data-min_stock="<?= $row['min_stock'] ?>"
                                        data-current_stock="<?= $row['current_stock'] ?>"
                                        title="Edit Stock"
                                        class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-105 transition-all cursor-pointer">
                                        <i class='bx bxs-edit text-lg'></i>
                                    </button>
                                    
                                    <!-- Delete -->
                                    <button
                                        onclick="if(confirm('Delete this stock record?')) location.href='?page=inventory&sub=list&delete=<?= $row['id'] ?>'"
                                        title="Delete"
                                        class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-105 transition-all">
                                        <i class='bx bxs-trash text-lg'></i>
                                    </button>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $isStaff ? 10 : 9 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-package text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada stok yang tersedia</p>
                        <p class="text-sm mt-1">Buat produk terlebih dahulu atau sesuaikan filter</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination - FIXED VERSION -->
<div class="flex justify-between items-center px-6 mb-10">
    <span class="text-sm text-gray-500">
        Showing <?= $showingStart ?> to <?= $showingEnd ?> of <?= $totalRecords ?> entries
    </span>
    <div class="flex gap-1">
        <?php if ($page_num > 1): ?>
            <a href="?page=inventory&sub=list&page_num=<?= $page_num - 1 ?><?= $query_string ? '&' . $query_string : '' ?>"
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
        $startPage = max(1, $page_num - 2);
        $endPage = min($totalPages, $page_num + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++): 
            if ($i == $page_num): ?>
                <span class="px-3 py-1 rounded border border-[#092363] bg-[#092363] text-white text-sm">
                    <?= $i ?>
                </span>
            <?php else: ?>
                <a href="?page=inventory&sub=list&page_num=<?= $i ?><?= $query_string ? '&' . $query_string : '' ?>"
                    class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white text-sm">
                    <?= $i ?>
                </a>
            <?php endif; 
        endfor; ?>

        <?php if ($page_num < $totalPages): ?>
            <a href="?page=inventory&sub=list&page_num=<?= $page_num + 1 ?><?= $query_string ? '&' . $query_string : '' ?>"
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

<!-- Modal Edit Stock -->
<div id="editStockModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editStockModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold">Edit Stock</h3>
                <button onclick="closeModal('editStockModal')" class="text-white hover:text-[#e6b949] text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Product</label>
                    <input type="text" id="edit_product_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Location</label>
                    <input type="text" id="edit_location_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Min Stock *</label>
                        <input type="number" name="min_stock" id="edit_min_stock" required min="0" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Stock </label>
                        <input type="number" name="current_stock" id="edit_current_stock" required min="0" readonly
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-gray-100  cursor-not-allowed">
                    </div>
                </div>
                
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('editStockModal')" 
                            class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">
                        Batal
                    </button>
                    <button type="submit" name="update" 
                            class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363]">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>