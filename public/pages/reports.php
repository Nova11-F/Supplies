<?php
include __DIR__ . '/../../config/database.php';

// Get date range (default last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. TOTAL ASSET VALUE (Total dari semua products * stock_quantity * purchase_price)
$asset_query = "
    SELECT SUM(stock_quantity * purchase_price) AS total_asset
    FROM products
    WHERE status = 'active'
";
$asset_result = mysqli_query($conn, $asset_query);
$asset_data = mysqli_fetch_assoc($asset_result);
$total_asset = $asset_data['total_asset'] ?? 0;

// 2. TOTAL STOCK IN (dari payments yang berhasil dalam periode)
$stock_in_query = "
    SELECT SUM(ii.quantity) AS total_stock_in
    FROM payments p
    JOIN invoices i ON i.id = p.invoice_id
    JOIN invoice_items ii ON ii.invoice_id = i.id
    WHERE i.status = 'paid'
    AND DATE(i.invoice_date) BETWEEN ? AND ?
";
$stmt = $conn->prepare($stock_in_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$stock_in_result = $stmt->get_result();
$stock_in_data = $stock_in_result->fetch_assoc();
$total_stock_in = $stock_in_data['total_stock_in'] ?? 0;

// 3. TOTAL STOCK OUT (belum ada modul stock out, nanti bisa ditambahkan)
// Untuk sekarang kita set 0 atau pakai dummy data
$total_stock_out = 0;

// 4. MONTHLY STOCK MOVEMENT (6 bulan terakhir)
$monthly_query = "
    SELECT 
        DATE_FORMAT(i.invoice_date, '%Y-%m') AS month,
        DATE_FORMAT(i.invoice_date, '%b') AS month_name,
        SUM(ii.quantity) AS stock_in
    FROM payments p
    JOIN invoices i ON i.id = p.invoice_id
    JOIN invoice_items ii ON ii.invoice_id = i.id
    WHERE i.status = 'paid'
    AND i.invoice_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m'), DATE_FORMAT(i.invoice_date, '%b')
    ORDER BY month ASC
";
$monthly_result = mysqli_query($conn, $monthly_query);
$monthly_data = [];
while ($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_data[] = $row;
}

// 5. TOP MOVING ITEMS (products dengan quantity terbanyak dari invoice_items)
$top_items_query = "
    SELECT 
        p.id,
        p.name,
        c.name AS category_name,
        SUM(ii.quantity) AS total_quantity
    FROM invoice_items ii
    JOIN products p ON p.id = ii.product_id
    LEFT JOIN categories c ON c.id = p.category_id
    JOIN invoices i ON i.id = ii.invoice_id
    WHERE i.status = 'paid'
    GROUP BY p.id
    ORDER BY total_quantity DESC
    LIMIT 5
";
$top_items_result = mysqli_query($conn, $top_items_query);

// 6. DETAILED STOCK REPORT
$detailed_query = "
    SELECT 
        p.id,
        p.product_code,
        p.name AS product_name,
        c.name AS category_name,
        l.name AS location_name,
        p.stock_quantity,
        p.min_stock,
        COALESCE(SUM(ii.quantity), 0) AS total_in,
        0 AS total_out,
        CASE 
            WHEN p.stock_quantity = 0 THEN 'Out of Stock'
            WHEN p.stock_quantity <= p.min_stock THEN 'Low Stock'
            ELSE 'In Stock'
        END AS stock_status
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN po_items poi ON poi.product_id = p.id
    LEFT JOIN locations l ON l.id = poi.location_id
    LEFT JOIN invoice_items ii ON ii.product_id = p.id
    LEFT JOIN invoices i ON i.id = ii.invoice_id AND i.status = 'paid'
    WHERE p.status = 'active'
    GROUP BY p.id
    ORDER BY p.name ASC
";

// Apply filters
$where_conditions = ["p.status = 'active'"];
$params = [];
$types = "";

if (isset($_GET['category_filter']) && $_GET['category_filter'] !== '') {
    $where_conditions[] = "c.id = ?";
    $params[] = $_GET['category_filter'];
    $types .= "i";
}

if (isset($_GET['location_filter']) && $_GET['location_filter'] !== '') {
    $where_conditions[] = "l.id = ?";
    $params[] = $_GET['location_filter'];
    $types .= "i";
}

if (isset($_GET['status_filter']) && $_GET['status_filter'] !== '') {
    $status_filter = $_GET['status_filter'];
    if ($status_filter == 'in_stock') {
        $where_conditions[] = "p.stock_quantity > p.min_stock";
    } elseif ($status_filter == 'low_stock') {
        $where_conditions[] = "p.stock_quantity <= p.min_stock AND p.stock_quantity > 0";
    } elseif ($status_filter == 'out_of_stock') {
        $where_conditions[] = "p.stock_quantity = 0";
    }
}

$detailed_query = "
    SELECT 
        p.id,
        p.product_code,
        p.name AS product_name,
        c.name AS category_name,
        GROUP_CONCAT(DISTINCT l.name SEPARATOR ', ') AS location_name,
        p.stock_quantity,
        p.min_stock,
        COALESCE(SUM(ii.quantity), 0) AS total_in,
        0 AS total_out,
        CASE 
            WHEN p.stock_quantity = 0 THEN 'Out of Stock'
            WHEN p.stock_quantity <= p.min_stock THEN 'Low Stock'
            ELSE 'In Stock'
        END AS stock_status
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN po_items poi ON poi.product_id = p.id
    LEFT JOIN locations l ON l.id = poi.location_id
    LEFT JOIN invoice_items ii ON ii.product_id = p.id
    LEFT JOIN invoices i ON i.id = ii.invoice_id AND i.status = 'paid'
    WHERE " . implode(" AND ", $where_conditions) . "
    GROUP BY p.id
    ORDER BY p.name ASC
";

if (count($params) > 0) {
    $stmt = $conn->prepare($detailed_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $detailed_result = $stmt->get_result();
} else {
    $detailed_result = mysqli_query($conn, $detailed_query);
}

// Get categories for filter
$categories = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");

// Get locations for filter
$locations = mysqli_query($conn, "SELECT id, name FROM locations ORDER BY name ASC");

// Icons mapping
$category_icons = [
    'Electronics' => 'bx-laptop',
    'Furniture' => 'bx-chair',
    'Accessories' => 'bx-mouse',
    'Audio' => 'bx-headphone',
    'Office' => 'bx-briefcase',
];

$category_colors = [
    'Electronics' => 'bg-blue-100 text-blue-600',
    'Furniture' => 'bg-yellow-100 text-yellow-600',
    'Accessories' => 'bg-purple-100 text-purple-600',
    'Audio' => 'bg-red-100 text-red-600',
    'Office' => 'bg-green-100 text-green-600',
];
?>

<div class="px-8 mb-10">

    <div class="flex justify-end mb-8">

        <div class="flex gap-3 mt-4 md:mt-0">
            <div class="bg-white border border-gray-200 rounded-lg px-4 py-2 flex items-center gap-2 shadow-sm">
                <i class='bx bx-calendar text-gray-500'></i>
                <span class="text-sm text-gray-700 font-medium"><?= date('M d', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></span>
                <i class='bx bx-chevron-down text-gray-400 ml-2'></i>
            </div>

            <button onclick="openModal('exportModal')"
                class="bg-[#092363] text-white px-4 py-2 rounded-lg hover:bg-[#e6b949] hover:text-[#092363] font-semibold text-sm shadow-md flex items-center gap-2 transform duration-300 hover:scale-102 transition-all">
                <i class='bx bxs-file-export'></i> Export Data
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-6 mb-8">

        <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transform duration-500 hover:scale-105 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Asset Value</p>
                    <p class="text-2xl font-bold text-gray-800">Rp <?= number_format($total_asset, 0, ',', '.') ?></p>
                    <p class="text-xs text-green-600 mt-2">
                        <i class='bx bx-info-circle'></i> Based on purchase price
                    </p>
                </div>
                <div class="bg-blue-100 p-4 rounded-xl">
                    <i class='bx bxs-wallet text-4xl text-blue-600'></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transform duration-500 hover:scale-105 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Stock In</p>
                    <p class="text-2xl font-bold text-gray-800"><?= number_format($total_stock_in, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-400">Units</span></p>
                    <p class="text-xs text-green-600 mt-2">
                        <i class='bx bx-up-arrow-alt'></i> From paid invoices
                    </p>
                </div>
                <div class="bg-green-100 p-4 rounded-xl">
                    <i class='bx bxs-down-arrow-circle text-4xl text-green-600'></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transform duration-500 hover:scale-105 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Stock Out</p>
                    <p class="text-2xl font-bold text-gray-800"><?= number_format($total_stock_out, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-400">Units</span></p>
                    <p class="text-xs text-gray-600 mt-2">
                        <i class='bx bx-info-circle'></i> Coming soon
                    </p>
                </div>
                <div class="bg-red-100 p-4 rounded-xl">
                    <i class='bx bxs-up-arrow-circle text-4xl text-red-600'></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-3 gap-6 mb-8">

        <!-- Monthly Stock Movement Chart -->
        <div class="col-span-2 bg-white rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Monthly Stock Movement</h3>
                    <p class="text-sm text-gray-500">Last 6 months comparison</p>
                </div>
                <div class="flex gap-3">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-[#092363]"></div>
                        <span class="text-xs text-gray-600">Stock In</span>
                    </div>
                </div>
            </div>
            <!-- Simple Bar Chart -->
            <div class="flex items-end justify-around h-64 gap-3 mb-4">
                <?php if (count($monthly_data) > 0): ?>
                    <?php foreach ($monthly_data as $month): ?>
                        <?php
                        $height = ($month['stock_in'] / max(array_column($monthly_data, 'stock_in'))) * 220;
                        ?>
                        <div class="flex flex-col items-center flex-1">
                            <div class="relative w-full group">
                                <div class="bg-[#092363] w-full rounded-t-lg hover:bg-[#e6b949] transition-all duration-200 cursor-pointer"
                                    style="height: <?= $height ?>px"
                                    title="<?= $month['stock_in'] ?> units">
                                </div>
                                <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs py-1 px-2 rounded">
                                    <?= $month['stock_in'] ?>
                                </div>
                            </div>
                            <span class="text-sm text-gray-600 mt-2 font-medium"><?= $month['month_name'] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center w-full py-8">
                        <i class='bx bx-chart text-5xl text-gray-300'></i>
                        <p class="text-gray-500 mt-2">No data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Moving Items -->
        <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col">
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-800">Top Moving Items</h3>
                <p class="text-sm text-gray-500">Most purchased items</p>
            </div>

            <div class="flex-1 overflow-y-auto pr-2">
                <div class="space-y-4">
                    <?php if (mysqli_num_rows($top_items_result) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($top_items_result)): ?>
                            <?php
                            $icon = $category_icons[$item['category_name']] ?? 'bx-box';
                            $color = $category_colors[$item['category_name']] ?? 'bg-gray-100 text-gray-600';
                            ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="<?= $color ?> p-2 rounded-lg">
                                        <i class='bx <?= $icon ?> text-xl'></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-sm text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></p>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-[#092363]"><?= $item['total_quantity'] ?> Units</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class='bx bx-package text-5xl text-gray-300'></i>
                            <p class="text-gray-500 mt-2 text-sm">No items yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                <a href="index.php?page=inventory" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                    View Full Inventory <i class='bx bx-right-arrow-alt'></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Detailed Stock Report -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Detailed Stock Report</h3>
                <p class="text-sm text-gray-500">All items stock status</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="page" value="reports">

                <select name="category_filter" onchange="this.form.submit()" class="bg-gray-50 border-0 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-[#092363] text-gray-600 font-medium">
                    <option value="">All Categories</option>
                    <?php mysqli_data_seek($categories, 0); ?>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= $cat['id'] ?>" <?= (isset($_GET['category_filter']) && $_GET['category_filter'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= $cat['name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="location_filter" onchange="this.form.submit()" class="bg-gray-50 border-0 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-[#092363] text-gray-600 font-medium">
                    <option value="">All Location</option>
                    <?php mysqli_data_seek($locations, 0); ?>
                    <?php while ($loc = mysqli_fetch_assoc($locations)): ?>
                        <option value="<?= $loc['id'] ?>" <?= (isset($_GET['location_filter']) && $_GET['location_filter'] == $loc['id']) ? 'selected' : '' ?>>
                            <?= $loc['name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="status_filter" onchange="this.form.submit()" class="bg-gray-50 border-0 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-[#092363] text-gray-600 font-medium">
                    <option value="">All Status</option>
                    <option value="in_stock" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'in_stock') ? 'selected' : '' ?>>In Stock</option>
                    <option value="low_stock" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'low_stock') ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out_of_stock" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-[#092363] rounded-lg text-white">
                    <tr >
                        <th class="px-6 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Item Code</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Item Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Stock In</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Stock Out</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Current</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($detailed_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($detailed_result)): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($row['product_code']) ?></td>
                                <td class="px-6 py-4 text-sm font-bold text-gray-800"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-center text-green-600 font-medium">+<?= $row['total_in'] ?></td>
                                <td class="px-6 py-4 text-center text-red-600 font-medium">-<?= $row['total_out'] ?></td>
                                <td class="px-6 py-4 text-center font-bold text-[#092363]"><?= $row['stock_quantity'] ?></td>
                                <td class="px-6 py-4 text-center text-sm"><?= htmlspecialchars($row['location_name'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $status_classes = [
                                        'In Stock' => 'bg-green-100 text-green-700',
                                        'Low Stock' => 'bg-yellow-100 text-yellow-700',
                                        'Out of Stock' => 'bg-red-100 text-red-700'
                                    ];
                                    $status_class = $status_classes[$row['stock_status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm text-xs <?= $status_class ?>">
                                        <?= $row['stock_status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                <i class='bx bx-package text-5xl mb-2 opacity-50'></i>
                                <p class="font-semibold">No products found</p>
                                <p class="text-sm">Try changing your filters</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('exportModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide flex items-center gap-2">
                    <i class='bx bxs-download'></i> Export Data
                </h3>
                <button onclick="closeModal('exportModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="export_report.php" method="POST" class="p-6 space-y-5">

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Report Type</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="report_type" value="stock" class="peer sr-only" checked>
                            <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-[#092363] peer-checked:bg-blue-50 transition-all text-center hover:border-blue-300">
                                <i class='bx bxs-box text-3xl text-gray-400 mb-1'></i>
                                <p class="text-sm font-bold text-gray-600">Stock Report</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="report_type" value="transaction" class="peer sr-only">
                            <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-[#092363] peer-checked:bg-blue-50 transition-all text-center hover:border-blue-300">
                                <i class='bx bx-transfer text-3xl text-gray-400 mb-1'></i>
                                <p class="text-sm font-bold text-gray-600">Transactions</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Format File</label>
                    <select name="format" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="pdf">PDF Document (.pdf)</option>
                        <option value="excel">Microsoft Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Start Date</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('exportModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Cancel</button>
                    <button type="submit" style="width: 140px;" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all flex justify-center items-center gap-2">
                        <i class='bx bxs-download'></i> Download
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>