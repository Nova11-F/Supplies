<?php
include __DIR__ . '/../../../config/database.php';

// FUNGSI UNTUK MENAMBAH STOCK OTOMATIS SETELAH PAYMENT
function addToStock($conn, $invoice_id) {
    // Get invoice items
    $stmt = $conn->prepare("
        SELECT 
            ii.product_id,
            ii.quantity,
            poi.location_id
        FROM invoice_items ii
        JOIN invoices i ON i.id = ii.invoice_id
        JOIN po_items poi ON poi.product_id = ii.product_id AND poi.po_id = i.po_id
        WHERE ii.invoice_id = ?
    ");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    while ($item = $items->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $location_id = $item['location_id'];
        
        // Check if stock record exists for this product and location
        $check_stmt = $conn->prepare("
            SELECT id, current_stock 
            FROM stock 
            WHERE product_id = ? AND location_id = ?
        ");
        $check_stmt->bind_param("ii", $product_id, $location_id);
        $check_stmt->execute();
        $stock_result = $check_stmt->get_result();
        
        if ($stock_result->num_rows > 0) {
            // Update existing stock
            $stock_row = $stock_result->fetch_assoc();
            $new_stock = $stock_row['current_stock'] + $quantity;
            
            $update_stmt = $conn->prepare("
                UPDATE stock 
                SET current_stock = ?, 
                    last_updated = NOW() 
                WHERE id = ?
            ");
            $update_stmt->bind_param("ii", $new_stock, $stock_row['id']);
            $update_stmt->execute();
        } else {
            // Create new stock record
            // Get min_stock from product or set default
            $min_stock = 10; // Default minimum stock
            
            $insert_stmt = $conn->prepare("
                INSERT INTO stock (product_id, location_id, min_stock, current_stock, last_updated) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insert_stmt->bind_param("iiii", $product_id, $location_id, $min_stock, $quantity);
            $insert_stmt->execute();
        }
    }
    
    return true;
}

// UPDATE INVOICE STATUS TO PAID (dipanggil dari payment.php setelah payment sukses)
if (isset($_POST['update_payment_status'])) {
    $invoice_id = (int)$_POST['invoice_id'];
    $payment_method = $_POST['payment_method'] ?? 'manual';
    $transaction_id = $_POST['transaction_id'] ?? null;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update invoice status to paid
        $stmt = $conn->prepare("
            UPDATE invoices 
            SET status = 'paid',
                payment_date = NOW(),
                payment_method = ?,
                transaction_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $payment_method, $transaction_id, $invoice_id);
        $stmt->execute();
        
        // Add products to stock
        addToStock($conn, $invoice_id);
        
        // Commit transaction
        $conn->commit();
        
        echo "<script>
            alert('Payment berhasil! Stock telah diupdate.');
            location.href='index.php?page=transactions&sub=invoice';
        </script>";
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo "<script>
            alert('Error: " . $e->getMessage() . "');
            history.back();
        </script>";
        exit;
    }
}

// GENERATE INVOICE
if (isset($_POST['generate_invoice'])) {
    $invoice_date = $_POST['invoice_date'];
    $selected_po_ids = $_POST['po_ids'] ?? [];

    if (empty($selected_po_ids)) {
        echo "<script>alert('Pilih minimal 1 PO untuk generate invoice!'); history.back();</script>";
        exit;
    }

    // Generate Invoice Code
    $invoice_code = "INV-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Calculate total amount from selected POs
    $total_amount = 0;
    $supplier_id = null;
    $po_id = null; // Untuk foreign key

    foreach ($selected_po_ids as $po_id_item) {
        $stmt = $conn->prepare("
            SELECT total_amount, supplier_id 
            FROM purchase_orders 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $po_id_item);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $total_amount += $row['total_amount'];
        if ($supplier_id === null) {
            $supplier_id = $row['supplier_id'];
            $po_id = $po_id_item; // Simpan PO pertama sebagai referensi
        }
    }

    // Insert invoice
    // Ambil bagian kode PO untuk invoice
    $letters = '';
    foreach ($selected_po_ids as $po_id_item) {
        $stmt = $conn->prepare("SELECT po_code FROM purchase_orders WHERE id = ?");
        $stmt->bind_param("i", $po_id_item);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Hapus prefix "PO-" (case insensitive)
            $code = preg_replace("/^PO-/i", "", $row['po_code']);
            $letters .= $code . "-"; // tambahkan separator untuk beberapa PO
        }
    }

    // Hapus trailing "-"
    $letters = rtrim($letters, "-");

    // Nomor urut invoice
    $stmt = $conn->prepare("SELECT invoice_code FROM invoices WHERE invoice_code LIKE ?");
    $like = "INV-$letters-%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $last_number = 0;
    while ($row = $result->fetch_assoc()) {
        if (preg_match("/-(\d+)$/", $row['invoice_code'], $matches)) {
            $num = (int)$matches[1];
            if ($num > $last_number) $last_number = $num;
        }
    }

    $next_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);

    // Buat invoice_code final
    $invoice_code = "INV-$letters-$next_number";

    $status = 'unpaid';
    $created_by = (int)($_SESSION['user_id'] ?? 1);

    $stmt = $conn->prepare("
        INSERT INTO invoices (invoice_code, po_id, invoice_date, supplier_id, total_amount, status, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sissisi", $invoice_code, $po_id, $invoice_date, $supplier_id, $total_amount, $status, $created_by);

    if ($stmt->execute()) {
        $invoice_id = $conn->insert_id;

        // Insert invoice items from all selected POs
        foreach ($selected_po_ids as $po_id_item) {
            // Get PO items
            $stmt = $conn->prepare("
                SELECT product_id, quantity, unit_price, total_amount
                FROM po_items
                WHERE po_id = ?
            ");
            $stmt->bind_param("i", $po_id_item);
            $stmt->execute();
            $po_items = $stmt->get_result();

            while ($po_item = $po_items->fetch_assoc()) {
                // Insert to invoice_items
                $stmt = $conn->prepare("
                    INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, total_amount) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "iiddd",
                    $invoice_id,
                    $po_item['product_id'],
                    $po_item['quantity'],
                    $po_item['unit_price'],
                    $po_item['total_amount']
                );
                $stmt->execute();
            }

            // Update purchase order status to 'invoiced'
            $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'invoiced' WHERE id = ?");
            $stmt->bind_param("i", $po_id_item);
            $stmt->execute();
        }

        echo "<script>alert('Invoice berhasil dibuat dengan kode $invoice_code'); location.href='index.php?page=transactions&sub=invoice';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal membuat invoice'); history.back();</script>";
        exit;
    }
}

// PAY INVOICE - Redirect ke halaman payment dengan Midtrans
if (isset($_POST['pay_invoice'])) {
    $invoice_id = (int)$_POST['invoice_id'];

    // Redirect ke halaman payment melalui index.php (include pattern)
    echo "<script>
        window.location.href = 'index.php?page=transactions&sub=payment&invoice_id=$invoice_id';
    </script>";
    exit;
}

// DELETE INVOICE
if (isset($_GET['delete'])) {
    $invoice_id = (int)$_GET['delete'];

    // Get PO IDs associated with this invoice to revert status
    $stmt = $conn->prepare("SELECT po_id FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice_data = $result->fetch_assoc();

    // Delete invoice items first
    $stmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();

    // Delete invoice
    $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();

    // Revert PO status back to 'approved'
    if ($invoice_data['po_id']) {
        $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $invoice_data['po_id']);
        $stmt->execute();
    }

    echo "<script>alert('Invoice berhasil dihapus!'); location.href='index.php?page=transactions&sub=invoice';</script>";
    exit;
}

// GET INVOICES WITH FILTERS
$where_clauses = [];
$params = [];
$types = "";

if (isset($_GET['start_date']) && $_GET['start_date'] !== '') {
    $where_clauses[] = "i.invoice_date >= ?";
    $params[] = $_GET['start_date'];
    $types .= "s";
}

if (isset($_GET['end_date']) && $_GET['end_date'] !== '') {
    $where_clauses[] = "i.invoice_date <= ?";
    $params[] = $_GET['end_date'];
    $types .= "s";
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where_clauses[] = "i.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$query = "
SELECT 
    i.id,
    i.invoice_code,
    i.invoice_date,
    i.total_amount,
    i.status,
    po.po_code,
    s.id AS supplier_id,
    s.name AS supplier_name,
    GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') AS products,
    SUM(ii.quantity) AS total_quantity,
    GROUP_CONCAT(DISTINCT l.name SEPARATOR ', ') AS locations
FROM invoices i
LEFT JOIN purchase_orders po ON po.id = i.po_id
LEFT JOIN suppliers s ON s.id = i.supplier_id
LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
LEFT JOIN products p ON p.id = ii.product_id
LEFT JOIN po_items poi ON poi.product_id = ii.product_id AND poi.po_id = po.id
LEFT JOIN locations l ON l.id = poi.location_id
$where_sql
GROUP BY i.id
ORDER BY i.id DESC
";

if (count($params) > 0) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($conn, $query);
}

// GET APPROVED POs FOR INVOICE GENERATION
$po_query = "
SELECT 
    po.id,
    po.po_code,
    po.total_amount,
    po.status,
    s.name AS supplier_name,
    GROUP_CONCAT(CONCAT(p.name, ' (', poi.quantity, ')') SEPARATOR ', ') AS items
FROM purchase_orders po
JOIN suppliers s ON s.id = po.supplier_id
LEFT JOIN po_items poi ON poi.po_id = po.id
LEFT JOIN products p ON p.id = poi.product_id
WHERE po.status = 'approved'
GROUP BY po.id
ORDER BY po.id DESC
";
$po_result = mysqli_query($conn, $po_query);

?>

<!-- Create Invoice -->
<div class="flex items-center justify-between mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">Invoice</h1>
    <?php if ($isAdmin): ?>
        <div class="flex justify-between items-center">
            <button
                onclick="openModal('invoiceModal')"
                class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-102 transition-all">
                <i class="bx bx-receipt text-lg"></i>
                Generate New Invoice
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
        <input type="hidden" name="page" value="transactions">
        <input type="hidden" name="sub" value="invoice">

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                <option value="">Semua Status</option>
                <option value="paid" <?= (isset($_GET['status']) && $_GET['status'] == 'paid') ? 'selected' : '' ?>>Paid</option>
                <option value="unpaid" <?= (isset($_GET['status']) && $_GET['status'] == 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
                <option value="draft" <?= (isset($_GET['status']) && $_GET['status'] == 'draft') ? 'selected' : '' ?>>Draft</option>
                <option value="cancelled" <?= (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <button type="submit" class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex justify-center items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-102 transition-all">
            <i class='bx bx-filter-alt'></i> Filter
        </button>
    </form>
</div>


<!-- Table Invoice -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Invoice ID</th>
                <th class="px-4 py-3">PO ID</th>
                <th class="px-4 py-3">Supplier</th>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Quantity</th>
                <th class="px-4 py-3">Locations</th>
                <th class="px-4 py-3">Total Price</th>
                <th class="px-4 py-3">Status</th>
                <?php if ($isAdmin): ?>
                    <th class="px-4 py-3">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-center"><?= date('d-m-Y', strtotime($row['invoice_date'])) ?></td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800"><?= htmlspecialchars($row['invoice_code']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-blue-600 font-semibold"><?= htmlspecialchars($row['po_code'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['supplier_name']) ?></td>
                        <td class="px-4 py-3 text-center text-sm"><?= htmlspecialchars($row['products'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center font-semibold"><?= $row['total_quantity'] ?></td>
                        <td class="px-4 py-3 text-center text-sm"><?= htmlspecialchars($row['locations'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center font-bold text-red-600">$<?= number_format($row['total_amount'], 2) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $status_colors = [
                                'unpaid' => 'text-yellow-600',
                                'paid' => 'text-green-600',
                                'cancelled' => 'text-red-600',
                                'draft' => 'text-gray-600'
                            ];
                            $status_class = $status_colors[$row['status']] ?? 'text-gray-600';
                            ?>
                            <span class="font-bold <?= $status_class ?>"><?= ucfirst($row['status']) ?></span>
                        </td>
                        <?php if ($isAdmin): ?>
                            <td class="py-2 px-4">
                                <div class="flex justify-center gap-2">
                                    <?php if ($row['status'] == 'unpaid'): ?>
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="invoice_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="pay_invoice" class="bg-green-500 font-bold text-white px-2 py-1 rounded hover:bg-green-600 transform duration-300 hover:scale-102 transition-all">
                                                <i class="bx bx-credit-card"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($row['status'] == 'paid'): ?>
                                        <a href="public/print_invoice.php?id=<?= $row['id'] ?>" target="_blank" class="bg-[#092363] font-bold text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-102 transition-all">
                                            <i class='bx bxs-file-pdf'></i> Print
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($row['status'] != 'paid'): ?>
                                        <button onclick="if(confirm('Yakin hapus invoice ini?')){window.location.href='?page=transactions&sub=invoice&delete=<?= $row['id'] ?>';}" class="bg-red-500 font-bold text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all">
                                            <i class='bx bxs-trash'></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-receipt text-6xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Belum ada invoice</p>
                        <p class="text-sm">Generate invoice dari PO yang sudah approved</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center px-6 mb-10">
    <span class="text-sm text-gray-500">Showing <?= mysqli_num_rows($result) ?> entries</span>
    <div class="flex gap-1">
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Prev</button>
        <button class="px-3 py-1 rounded border border-[#092363] bg-[#092363] text-white text-sm">1</button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white text-sm">2</button>
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Next</button>
    </div>
</div>

<!-- Modal Generate Invoice -->
<div id="invoiceModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('invoiceModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Generate Invoice</h3>
                <button onclick="closeModal('invoiceModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Invoice Date</label>
                    <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" required>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-xs text-blue-800"><i class='bx bx-info-circle'></i> <strong>Info:</strong> Hanya PO dengan status "Approved" yang bisa di-invoice</p>
                </div>

                <div class="overflow-x-auto bg-white shadow-md rounded-lg mb-6 max-h-96">
                    <table class="min-w-full">
                        <thead class="bg-[#092363] text-white sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-center">PO Code</th>
                                <th class="px-4 py-3 text-center">Supplier</th>
                                <th class="px-4 py-3 text-center">Items</th>
                                <th class="px-4 py-3 text-center">Total</th>
                                <th class="px-4 py-3 text-center">Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (mysqli_num_rows($po_result) > 0): ?>
                                <?php while ($po = mysqli_fetch_assoc($po_result)): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-center text-sm text-gray-800 font-semibold"><?= htmlspecialchars($po['po_code']) ?></td>
                                        <td class="px-4 py-3 text-center text-gray-600"><?= htmlspecialchars($po['supplier_name']) ?></td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600"><?= htmlspecialchars($po['items']) ?></td>
                                        <td class="px-4 py-3 text-center text-red-600 font-semibold">$<?= number_format($po['total_amount'], 2) ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <input type="checkbox" name="po_ids[]" value="<?= $po['id'] ?>" class="po-checkbox h-4 w-4" data-amount="<?= $po['total_amount'] ?>">
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        <i class='bx bx-package text-5xl mb-2 opacity-50'></i>
                                        <p class="font-semibold">Tidak ada PO yang siap di-invoice</p>
                                        <p class="text-sm mt-1">PO harus berstatus "Approved" terlebih dahulu</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-100 font-semibold sticky bottom-0">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-gray-700">Total Selected:</td>
                                <td class="px-4 py-3 text-red-600 font-bold" id="totalAmount">$0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="pt-4 flex justify-end gap-3  border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('invoiceModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="generate_invoice" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Generate Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Calculate total amount when checkboxes are selected
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.po-checkbox');
        const totalAmountEl = document.getElementById('totalAmount');
        const selectAllCheckbox = document.getElementById('selectAll');

        function updateTotal() {
            let total = 0;
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    total += parseFloat(checkbox.dataset.amount);
                }
            });
            totalAmountEl.textContent = '$' + total.toFixed(2);
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateTotal);
        });

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateTotal();
            });
        }
    });
</script>