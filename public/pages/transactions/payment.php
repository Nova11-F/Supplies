<?php
// File: modules/transactions/payment.php
// Halaman untuk melakukan pembayaran invoice

// Include koneksi database
include __DIR__ . '/../../../config/database.php';

// Include konfigurasi Midtrans
require_once __DIR__ . '/../../../config/midtrans.php';

// Ambil invoice_id dari URL
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Validasi invoice_id
if ($invoice_id == 0) {
    echo "<script>
        alert('Invoice ID tidak valid!');
        window.location.href='index.php?page=transactions&sub=invoice';
    </script>";
    exit;
}

// Query untuk mengambil data invoice
$query = "
    SELECT 
        i.id,
        i.invoice_code,
        i.invoice_date,
        i.total_amount,
        i.status,
        s.name AS supplier_name,
        s.email AS supplier_email
    FROM invoices i
    LEFT JOIN suppliers s ON s.id = i.supplier_id
    WHERE i.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
if (!$invoice) {
    echo "<script>
        alert('Invoice tidak ditemukan!');
        window.location.href='index.php?page=transactions&sub=invoice';
    </script>";
    exit;
}

// Validasi: Cek apakah invoice sudah dibayar
if ($invoice['status'] == 'paid') {
    echo "<script>
        alert('Invoice ini sudah dibayar!');
        window.location.href='index.php?page=transactions&sub=invoice';
    </script>";
    exit;
}

// Generate unique order ID
$order_id = 'ORDER-' . $invoice['id'] . '-' . time();

// Siapkan transaction details untuk Midtrans
$transaction_details = array(
    'order_id' => $order_id,
    'gross_amount' => (int)$invoice['total_amount'], // Harus integer
);

// Ambil item-item dari invoice
$query_items = "
    SELECT 
        ii.product_id,
        ii.quantity,
        ii.unit_price,
        ii.total_amount,
        p.name AS product_name
    FROM invoice_items ii
    LEFT JOIN products p ON p.id = ii.product_id
    WHERE ii.invoice_id = ?
";

$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param("i", $invoice_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

// Siapkan item details
$item_details = array();
while ($item = $items_result->fetch_assoc()) {
    $item_details[] = array(
        'id' => 'PROD-' . $item['product_id'],
        'price' => (int)$item['unit_price'],
        'quantity' => (int)$item['quantity'],
        'name' => substr($item['product_name'], 0, 50)
    );
}

// Siapkan customer details
$customer_details = array(
    'first_name' => 'Customer',
    'email' => $invoice['supplier_email'] ?? 'customer@example.com',
);

// Metode pembayaran yang diaktifkan
// HANYA CREDIT CARD untuk testing tanpa webhook
$enabled_payments = array('credit_card');

// Gabungkan semua data
$transaction_data = array(
    'transaction_details' => $transaction_details,
    'item_details' => $item_details,
    'customer_details' => $customer_details,
    'enabled_payments' => $enabled_payments,
);

try {
    // Request Snap Token dari Midtrans
    $snapToken = \Midtrans\Snap::getSnapToken($transaction_data);

    // Simpan data payment ke database (gunakan variabel lokal untuk bind_param)
    $gross_amount = (float)$invoice['total_amount'];
    $stmt_payment = $conn->prepare(
        "INSERT INTO payments (invoice_id, order_id, gross_amount, transaction_status) VALUES (?, ?, ?, 'pending')"
    );
    $stmt_payment->bind_param("isd", $invoice_id, $order_id, $gross_amount);
    $stmt_payment->execute();
} catch (Exception $e) {
    echo "<script>
        alert('Error: " . addslashes($e->getMessage()) . "');
        window.location.href='index.php?page=transactions&sub=invoice';
    </script>";
    exit;
}


// Reset pointer untuk menampilkan items lagi
mysqli_data_seek($items_result, 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?= htmlspecialchars($invoice['invoice_code']) ?></title>

    <!-- Midtrans Snap.js -->
    <script
        src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="<?= \Midtrans\Config::$clientKey ?>">
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-40 flex items-start justify-center pt-20 z-50">

        <!-- Modal Card -->
        <div class="bg-white w-full max-w-md rounded-lg shadow-xl overflow-hidden">

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

                <!-- Payment Button -->
                <button
                    id="pay-button"
                    class="w-full bg-white text-[#092363] font-semibold py-3 rounded-lg shadow-md hover:from-[#e6b949] hover:to-yellow-500 hover:text-[#092363] transition-all duration-300 flex items-center justify-center gap-2 text-base">
                    <i class='bx bx-credit-card text-2xl'></i>
                    Pay Now - $<?= number_format($invoice['total_amount'], 2) ?>
                </button>

                <!-- Info & Security -->
                <div class="space-y-2 mt-2 text-xs">
                    <div class="bg-blue-50 border border-blue-200 p-2 rounded text-blue-800">
                        <strong>Payment Method:</strong> Credit Card Only<br>
                        <span>Testing mode - Use test credit card: 4811 1111 1111 1114</span>
                    </div>
                    <div class="bg-green-50 border border-green-200 p-2 rounded text-green-800 text-center">
                        <i class='bx bx-lock-alt'></i> Secure payment powered by Midtrans
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
    </div>

    <!-- JavaScript Midtrans Snap -->
    <script type="text/javascript">
        // Gunakan json_encode agar token ter-escape dengan aman
        var snapToken = <?= json_encode($snapToken) ?>;
        var orderId = <?= json_encode($order_id) ?>;

        document.getElementById('pay-button').addEventListener('click', function() {
            // Tampilkan popup Midtrans
            snap.pay(snapToken, {
                onSuccess: function(result) {
                    console.log('Payment success:', result);
                    // Redirect ke callback melalui index.php (include)
                    window.location.href = 'index.php?page=transactions&sub=payment_callback&order_id=' + encodeURIComponent(orderId) + '&status=success';
                },
                onPending: function(result) {
                    console.log('Payment pending:', result);
                    window.location.href = 'index.php?page=transactions&sub=payment_callback&order_id=' + encodeURIComponent(orderId) + '&status=pending';
                },
                onError: function(result) {
                    console.log('Payment error:', result);
                    alert('Payment failed! Please try again.');
                },
                onClose: function() {
                    console.log('Payment popup closed');
                    alert('You closed the payment popup before completing the transaction.');
                }
            });
        });
    </script>

    <script>
        function closeModal() {
            document.querySelector('.fixed.inset-0').style.display = 'none';
        }
    </script>
</body>

</html>