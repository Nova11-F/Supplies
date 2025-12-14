<?php
// Include Database Configuration
require_once __DIR__ . '/../../config/database.php';

// Query untuk Total Items (Products)
$result = $conn->query("SELECT COUNT(*) as total FROM products");
$totalItems = $result->fetch_assoc()['total'];

// Query untuk Total Suppliers
$result = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE status = 'active'");
$totalSuppliers = $result->fetch_assoc()['total'];

// Query untuk Total Categories
$result = $conn->query("SELECT COUNT(*) as total FROM categories WHERE status = 'active'");
$totalCategories = $result->fetch_assoc()['total'];

// Query untuk Total Purchase Orders
$result = $conn->query("SELECT COUNT(*) as total FROM purchase_orders");
$totalPurchases = $result->fetch_assoc()['total'];

// Query untuk Low Stock Items
$result = $conn->query("
    SELECT p.name, p.product_code, c.name as category_name, s.current_stock, s.min_stock, s.location_id
    FROM stock s
    JOIN products p ON s.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE s.current_stock < s.min_stock
    ORDER BY (s.current_stock - s.min_stock) ASC
    LIMIT 10
");
$lowStockItems = [];
while($row = $result->fetch_assoc()) {
    $lowStockItems[] = $row;
}

// Query untuk Recent Activity Logs
$result = $conn->query("
    SELECT al.*, u.full_name
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$activityLogs = [];
while($row = $result->fetch_assoc()) {
    $activityLogs[] = $row;
}

// Query untuk Recent Purchase Orders
$result = $conn->query("
    SELECT po.*, s.name as supplier_name, 
           (SELECT COUNT(*) FROM po_items WHERE po_id = po.id) as item_count,
           (SELECT l.name FROM po_items poi 
            LEFT JOIN locations l ON poi.location_id = l.id 
            WHERE poi.po_id = po.id LIMIT 1) as location_name
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.id
    ORDER BY po.created_at DESC
    LIMIT 10
");
$recentPurchases = [];
while($row = $result->fetch_assoc()) {
    $recentPurchases[] = $row;
}

// Query untuk Daily Transactions (7 hari terakhir)
$result = $conn->query("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM purchase_orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailyTransactions = [];
while($row = $result->fetch_assoc()) {
    $dailyTransactions[] = $row;
}

// Prepare data untuk chart (7 hari)
$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$chartData = array_fill(0, 7, 0);
foreach($dailyTransactions as $trans) {
    $dayIndex = date('N', strtotime($trans['date'])) - 1;
    $chartData[$dayIndex] = $trans['count'];
}

// Function untuk format rupiah
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function untuk format tanggal
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Function untuk status badge color
function getStatusColor($status) {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-700',
        'approved' => 'bg-blue-100 text-blue-700',
        'received' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
        'invoiced' => 'bg-purple-100 text-purple-700'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-700';
}

// Function untuk action icon
function getActionIcon($action) {
    $icons = [
        'create' => ['icon' => 'bx-plus', 'color' => 'bg-blue-100 text-blue-600'],
        'update' => ['icon' => 'bx-edit', 'color' => 'bg-yellow-100 text-yellow-600'],
        'delete' => ['icon' => 'bx-trash', 'color' => 'bg-red-100 text-red-600'],
        'login' => ['icon' => 'bx-log-in', 'color' => 'bg-green-100 text-green-600'],
        'view' => ['icon' => 'bx-show', 'color' => 'bg-purple-100 text-purple-600']
    ];
    return $icons[$action] ?? ['icon' => 'bx-check', 'color' => 'bg-gray-100 text-gray-600'];
}

// Function untuk time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return floor($difference / 60) . ' minutes ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hours ago';
    return floor($difference / 86400) . ' days ago';
}
?>

<div class="px-8 py-6">

<!-- Stats Cards -->
<div class="flex justify-between items-center gap-4 mb-6">
    <!-- Total Items Card -->
    <div class="w-full bg-white rounded-2xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Total Items</p>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($totalItems) ?></p>
                <p class="text-xs text-gray-500 mt-2">
                    <i class='bx bx-package'></i> Products in system
                </p>
            </div>
            <div class="bg-blue-100 p-4 rounded-xl">
                <i class='bx bx-package text-4xl text-blue-600'></i>
            </div>
        </div>
    </div>

    <!-- Total Suppliers Card -->
    <div class="w-full bg-white rounded-2xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Total Suppliers</p>
                <p class="text-3xl font-bold text-gray-800"><?= $totalSuppliers ?></p>
                <p class="text-xs text-gray-500 mt-2">
                    <i class='bx bx-store'></i> Active suppliers
                </p>
            </div>
            <div class="bg-green-100 p-4 rounded-xl">
                <i class='bx bxs-truck text-4xl text-green-600'></i>
            </div>
        </div>
    </div>

    <!-- Total Categories Card -->
    <div class="w-full bg-white rounded-2xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Categories</p>
                <p class="text-3xl font-bold text-gray-800"><?= $totalCategories ?></p>
                <p class="text-xs text-gray-500 mt-2">
                    <i class='bx bx-category'></i> Product categories
                </p>
            </div>
            <div class="bg-yellow-100 p-4 rounded-xl">
                <i class='bx bxs-category text-4xl text-yellow-600'></i>
            </div>
        </div>
    </div>

    <!-- Total Purchases Card -->
    <div class="w-full bg-white rounded-2xl shadow-lg p-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Total Purchases</p>
                <p class="text-3xl font-bold text-gray-800"><?= $totalPurchases ?></p>
                <p class="text-xs text-gray-500 mt-2">
                    <i class='bx bx-receipt'></i> Purchase orders
                </p>
            </div>
            <div class="bg-red-100 p-4 rounded-xl">
                <i class='bx bxs-cart text-4xl text-red-600'></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Daily Transactions</h3>
                <p class="text-sm text-gray-500">Last 7 days overview</p>
            </div>
        </div>
        
        <!-- Simple Bar Chart -->
        <div class="flex items-end justify-around h-64 gap-3 mb-4">
            <?php foreach($days as $index => $day): 
                $count = $chartData[$index];
                $height = $count > 0 ? ($count * 20) + 50 : 40;
                $height = min($height, 240);
            ?>
            <div class="flex flex-col items-center flex-1">
                <div class="relative w-full group">
                    <div class="bg-blue-500 w-full rounded-t-lg hover:bg-blue-600 transition-all duration-200 cursor-pointer" style="height: <?= $height ?>px"></div>
                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-2 py-1 rounded text-xs opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                        <?= $count ?> orders
                    </div>
                </div>
                <span class="text-sm text-gray-600 mt-2 font-medium"><?= $day ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Low Stock & Staff Activity Section -->
<div class="grid grid-cols-2 gap-6 mb-8">
    <!-- Low Stock Alert -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="bg-red-100 p-2 rounded-lg">
                    <i class='bx bx-error text-2xl text-red-600'></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Low Stock Alert</h3>
                    <p class="text-sm text-gray-500"><?= count($lowStockItems) ?> items need reordering</p>
                </div>
            </div>
            <a href="index.php?page=inventory" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                View All <i class='bx bx-right-arrow-alt'></i>
            </a>
        </div>
        
        <div class="space-y-3 max-h-80 overflow-y-auto">
            <?php if(count($lowStockItems) > 0): ?>
                <?php foreach($lowStockItems as $item): 
                    $stockPercent = ($item['current_stock'] / $item['min_stock']) * 100;
                    $bgColor = $stockPercent < 30 ? 'bg-red-50 hover:bg-red-100' : 'bg-yellow-50 hover:bg-yellow-100';
                    $textColor = $stockPercent < 30 ? 'text-red-600' : 'text-yellow-600';
                ?>
                <div class="flex items-center justify-between p-4 <?= $bgColor ?> rounded-xl transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="bg-white p-2 rounded-lg">
                            <i class='bx bx-package text-2xl <?= $textColor ?>'></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                            <p class="text-xs text-gray-500">Category: <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold <?= $textColor ?>"><?= $item['current_stock'] ?> Units</p>
                        <p class="text-xs text-gray-500">Min: <?= $item['min_stock'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class='bx bx-check-circle text-4xl text-green-500'></i>
                    <p class="mt-2">All stock levels are healthy</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Staff Activity Log -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                    <i class='bx bx-user-circle text-2xl text-blue-600'></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Staff Activity</h3>
                    <p class="text-sm text-gray-500">Recent actions</p>
                </div>
            </div>
            <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-semibold">
                Live
            </span>
        </div>
        
        <div class="space-y-4 max-h-80 overflow-y-auto">
            <?php if(count($activityLogs) > 0): ?>
                <?php foreach($activityLogs as $log): 
                    $iconData = getActionIcon($log['action']);
                ?>
                <div class="flex items-start gap-4 pb-4 border-b border-gray-100">
                    <div class="<?= $iconData['color'] ?> p-2 rounded-full flex-shrink-0">
                        <i class='bx <?= $iconData['icon'] ?>'></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-800">
                            <span class="font-semibold"><?= htmlspecialchars($log['full_name']) ?></span> 
                            <?= htmlspecialchars($log['description'] ?? ucfirst($log['action']) . ' in ' . $log['module']) ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1"><?= timeAgo($log['created_at']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class='bx bx-info-circle text-4xl'></i>
                    <p class="mt-2">No recent activity</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Purchases Table -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-10">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-gray-800">Recent Purchases</h3>
            <p class="text-sm text-gray-500">Latest purchase orders</p>
        </div>
        <a href="index.php?page=transactions&sub=order" class="bg-[#092363] text-white px-4 py-2 rounded-lg hover:bg-[#e6b949] hover:text-[#092363] flex items-center gap-2 font-bold text-sm transform duration-300 hover:scale-102 transition-all">
            <i class='bx bx-plus text-lg'></i> New Purchase
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-[#092363] rounded-t-2xl text-white">
                <tr>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase">PO Number</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase">Supplier</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase">Items</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase">Location</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase">Total</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if(count($recentPurchases) > 0): ?>
                    <?php foreach($recentPurchases as $po): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-semibold text-blue-600"><?= htmlspecialchars($po['po_code']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-800"><?= htmlspecialchars($po['supplier_name']) ?></td>
                        <td class="px-6 py-4 text-center text-sm text-gray-600"><?= $po['item_count'] ?> Items</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-600"><?= htmlspecialchars($po['location_name'] ?? '-') ?></td>
                        <td class="px-6 py-4 text-center text-sm font-semibold text-gray-800"><?= formatRupiah($po['total_amount']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 flex items-center justify-center rounded-full font-bold shadow-sm text-xs <?= getStatusColor($po['status']) ?>">
                                <?= ucfirst($po['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-600"><?= formatDate($po['order_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class='bx bx-inbox text-4xl'></i>
                            <p class="mt-2">No purchase orders yet</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>