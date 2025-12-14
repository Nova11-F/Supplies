<?php
include __DIR__ . '/../config/database.php';

// Ambil invoice_id dari parameter GET
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($invoice_id <= 0) {
    die('Invalid invoice id.');
}

// Ambil data invoice
$sql = "SELECT i.*, s.name AS supplier_name
        FROM invoices i
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) die("Invoice not found.");
$invoice = $result->fetch_assoc();

// Ambil item invoice
$sql_items = "SELECT ii.*, p.name AS product_name
              FROM invoice_items ii
              LEFT JOIN products p ON ii.product_id = p.id
              WHERE ii.invoice_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $invoice_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$order_id = $invoice['order_id'] ?? 'N/A'; // contoh jika ada field order_id

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= htmlspecialchars($invoice['invoice_code']) ?></title>
    <script>
        function closeModal() {
            window.history.back();
        }
    </script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    <style>
/* Tailwind like utility for simplicity (optional for printing) */
body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
.bg-white { background: #fff; }
.rounded-lg { border-radius: 0.5rem; }
.shadow-xl { box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
.p-4 { padding: 1rem; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-2 { gap: 0.5rem; }
.text-white { color: #fff; }
.bg-[#092363] { background-color: #092363; }
.text-gray-800 { color: #1f2937; }
.font-bold { font-weight: bold; }
.text-sm { font-size: 0.875rem; }
.bg-gray-50 { background-color: #f9fafb; }
.rounded { border-radius: 0.25rem; }
.border-b { border-bottom: 1px solid #e5e7eb; }
.bg-gradient-to-r { background: linear-gradient(to right, #fee2e2, #fef3c7); }
.text-red-600 { color: #dc2626; }
.text-center { text-align: center; }
a { text-decoration: none; color: #092363; }
a:hover { color: #e6b949; }
</style>
</head>

<body>

    <!-- Modal Card -->
    <div class="bg-white w-full max-w-md rounded-lg shadow-xl overflow-hidden mx-auto">

        <!-- Header -->
        <div class="bg-[#092363] text-white p-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i class='bx bx-credit-card text-3xl'></i>
                Payment Gateway
            </h1>
            <button onclick="closeModal()" class="text-white text-xl hover:text-[#e6b949]">&times;</button>
        </div>

        <!-- Invoice Card -->
        <div class="p-4 space-y-4">

            <!-- Invoice Info -->
            <div class="border-b pb-3">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-bold text-gray-800">Invoice Details</h2>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-lg font-semibold text-xs">
                        <i class='bx bx-time-five'></i> <?= ucfirst($invoice['status']) ?>
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
                    <div class="bg-gray-50 p-2 rounded">
                        <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($invoice['invoice_code']) ?></p>
                        <p>Invoice Code</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <p class="font-semibold text-gray-800 text-sm"><?= date('d M Y', strtotime($invoice['invoice_date'])) ?></p>
                        <p>Invoice Date</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <p class="font-mono text-sm text-gray-800"><?= htmlspecialchars($order_id) ?></p>
                        <p>Order ID</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($invoice['supplier_name']) ?></p>
                        <p>Supplier</p>
                    </div>
                </div>
            </div>

            <!-- Items List -->
            <div class="border-b pb-3 bg-gray-50">
                <h3 class="text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <i class='bx bx-package'></i> Items in this Invoice
                </h3>
                <div class="space-y-1 max-h-32 overflow-y-auto">
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <div class="flex justify-between items-center bg-white p-2 rounded">
                            <span class="text-gray-700 text-sm">
                                <i class='bx bx-box text-blue-500'></i>
                                <?= htmlspecialchars($item['product_name']) ?>
                                <span class="text-gray-400">(×<?= $item['quantity'] ?>)</span>
                            </span>
                            <span class="font-semibold text-gray-800 text-sm">$<?= number_format($item['total_amount'], 2) ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Total Amount -->
            <div class="bg-gradient-to-r from-red-50 to-orange-50 p-3 rounded">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-700">Total Amount</span>
                    <span class="text-xl font-bold text-red-600">$<?= number_format($invoice['total_amount'], 2) ?></span>
                </div>
            </div>

            <!-- Back Link -->
            <div class="text-center mt-2">
                <a href="index.php?page=transactions&sub=invoice"
                    class="text-gray-500 hover:text-[#092363] text-sm font-semibold inline-flex items-center gap-1">
                    <i class='bx bx-arrow-back'></i> Back to Invoice List
                </a>
            </div>

        </div>
    </div>

</body>

</html>