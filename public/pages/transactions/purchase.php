<?php
include __DIR__ . '/../../../config/database.php';

// INSERT DATA
if (isset($_POST['create'])) {
    $supplier_id = (int)$_POST['supplier_id'];
    $product_id = (int)$_POST['product_id'];
    $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : NULL;
    $location_id = isset($_POST['location_id']) && $_POST['location_id'] !== '' ? (int)$_POST['location_id'] : NULL;
    $quantity = (float)$_POST['quantity'];
    $unit_price = (float)$_POST['units_price'];
    $total_amount = $quantity * $unit_price;
    $order_date = $_POST['order_date'];
    $created_by = (int)($_SESSION['user_id'] ?? 1);
    $status = 'pending';

    // Ambil 4 huruf dari nama supplier
    $stmt = $conn->prepare("SELECT name FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $supplier_row = $stmt->get_result()->fetch_assoc();
    $supplier_prefix = strtoupper(substr(preg_replace('/\s+/', '', $supplier_row['name']), 0, 4));

    // Ambil product_code dari produk
    $stmt = $conn->prepare("SELECT product_code FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_row = $stmt->get_result()->fetch_assoc();
    $product_code = substr($product_row['product_code'], -4);

    // Buat PO code
    $po_base = "PO-$supplier_prefix-$product_code";
    // ensure unique po_code by appending sequence if needed
    $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM purchase_orders WHERE po_code LIKE CONCAT(?, '%')");
    $count_stmt->bind_param("s", $po_base);
    $count_stmt->execute();
    $cnt = $count_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    if ($cnt > 0) {
        $po_code = $po_base . '-' . ($cnt + 1);
    } else {
        $po_code = $po_base;
    }

    // Insert ke purchase_orders
    $stmt = $conn->prepare("INSERT INTO purchase_orders (po_code, supplier_id, order_date, status, total_amount, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissdi", $po_code, $supplier_id, $order_date, $status, $total_amount, $created_by);

    if ($stmt->execute()) {
        $po_id = $conn->insert_id;

        // Insert ke po_items
        $stmt = $conn->prepare("INSERT INTO po_items (po_id, product_id, location_id, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiddd", $po_id, $product_id, $location_id, $quantity, $unit_price, $total_amount);
        $stmt->execute();

        echo "<script>alert('PO berhasil dibuat dengan kode $po_code'); location.href='index.php?page=transactions&sub=purchase';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal membuat PO'); history.back();</script>";
        exit;
    }
}

// NOTE: Approve action moved into the Edit flow (status field).

// UPDATE DATA
if (isset($_POST['update'])) {
    $po_item_id = (int)$_POST['id'];
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);
    $category_id = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : NULL;
    $location_id = $_POST['location_id'] !== '' ? (int)$_POST['location_id'] : NULL;
    $quantity = (float)($_POST['quantity'] ?? 0);
    $unit_price = (float)($_POST['units_price'] ?? 0);
    $total_amount = $quantity * $unit_price;
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'pending';

    // Get po_id from po_items
    $stmt = $conn->prepare("SELECT po_id FROM po_items WHERE id = ?");
    $stmt->bind_param("i", $po_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $po_row = $result->fetch_assoc();
    $po_id = $po_row['po_id'];

    // Ambil 4 huruf dari nama supplier
    $stmt = $conn->prepare("SELECT name FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $supplier_row = $stmt->get_result()->fetch_assoc();
    $supplier_prefix = strtoupper(substr(preg_replace('/\s+/', '', $supplier_row['name']), 0, 4));

    // Ambil product_code
    $stmt = $conn->prepare("SELECT product_code FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_row = $stmt->get_result()->fetch_assoc();
    $product_code = substr($product_row['product_code'], -4);

    // PO code baru
    $po_base = "PO-$supplier_prefix-$product_code";
    // ensure unique po_code when updating (exclude current PO id)
    $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM purchase_orders WHERE po_code LIKE CONCAT(?, '%') AND id != ?");
    $count_stmt->bind_param("si", $po_base, $po_id);
    $count_stmt->execute();
    $cnt = $count_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    if ($cnt > 0) {
        $po_code = $po_base . '-' . ($cnt + 1);
    } else {
        $po_code = $po_base;
    }

    // Update purchase_orders
    $stmt = $conn->prepare("UPDATE purchase_orders SET po_code=?, supplier_id=?, order_date=?, status=?, total_amount=? WHERE id=?");
    $stmt->bind_param("sissdi", $po_code, $supplier_id, $order_date, $status, $total_amount, $po_id);
    $stmt->execute();

    // Update po_items
    $stmt = $conn->prepare("UPDATE po_items SET product_id=?, location_id=?, quantity=?, unit_price=?, total_amount=? WHERE id=?");
    $stmt->bind_param("iidddi", $product_id, $location_id, $quantity, $unit_price, $total_amount, $po_item_id);
    $stmt->execute();
    // If status is approved, set approved_by and approved_at; otherwise clear approval info
    if ($status === 'approved') {
        $approved_by = (int)($_SESSION['user_id'] ?? 1);
        $stmt2 = $conn->prepare("UPDATE purchase_orders SET approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt2->bind_param("ii", $approved_by, $po_id);
        $stmt2->execute();
    } else {
        $stmt2 = $conn->prepare("UPDATE purchase_orders SET approved_by = NULL, approved_at = NULL WHERE id = ?");
        $stmt2->bind_param("i", $po_id);
        $stmt2->execute();
    }

    echo "<script>alert('PO berhasil diupdate dengan kode $po_code'); location.href='index.php?page=transactions&sub=purchase';</script>";
    exit;
}

// DELETE DATA 
if (isset($_GET['delete'])) {
    $po_item_id = (int)$_GET['delete'];

    // Get po_id first
    $stmt = $conn->prepare("SELECT po_id FROM po_items WHERE id = ?");
    $stmt->bind_param("i", $po_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $po_id = $row['po_id'];

    // Delete po_items first
    $stmt = $conn->prepare("DELETE FROM po_items WHERE id = ?");
    $stmt->bind_param("i", $po_item_id);
    $stmt->execute();

    // Check if there are other items for this PO
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM po_items WHERE po_id = ?");
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count_row = $result->fetch_assoc();

    // If no more items, delete the purchase order too
    if ($count_row['count'] == 0) {
        $stmt = $conn->prepare("DELETE FROM purchase_orders WHERE id = ?");
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
    }

    echo "<script>alert('PO berhasil dihapus!'); location.href='index.php?page=transactions&sub=purchase';</script>";
    exit;
}

// GET ALL DATA
$result = mysqli_query($conn, "
SELECT 
    poi.id,
    po.id AS po_id,
    po.po_code,
    po.status,
    po.order_date AS date,
    p.id AS product_id,
    p.name AS product_name,
    s.id AS supplier_id,
    s.name AS supplier_name,
    c.id AS category_id,
    c.name AS category_name,
    l.id AS location_id,
    l.name AS location_name,
    poi.quantity,
    poi.unit_price,
    po.total_amount
FROM po_items poi
LEFT JOIN purchase_orders po ON po.id = poi.po_id
LEFT JOIN products p ON p.id = poi.product_id
LEFT JOIN suppliers s ON s.id = po.supplier_id
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN locations l ON l.id = poi.location_id
ORDER BY po.id DESC
");

?>
<!-- Create Order -->
<div class="flex items-center justify-between mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">Purchase Orders</h1>
    <?php if ($isStaff): ?>
        <button
            onclick="openModal('purchaseModal')"
            class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
            <i class='bx bx-plus text-lg'></i>
            <span>Tambah Order</span>
        </button>
    <?php endif; ?>
</div>

<!-- Table Order -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center">Date</th>
                <th class="px-4 py-3 text-center">PO ID</th>
                <th class="px-4 py-3 text-center">Supplier</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3 text-center">Location</th>
                <th class="px-4 py-3 text-center">Quantity</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-center">Total Amount</th>
                <?php if ($isAdmin): ?>
                    <th class="px-4 py-3 text-center">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-center"><?= $row['date'] ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500 font-bold"><?= $row['po_code'] ?></td>
                        <td class="px-4 py-3 text-center"><?= $row['supplier_name'] ?></td>
                        <td class="px-4 py-3 text-center"><?= $row['product_name'] ?></td>
                        <td class="px-4 py-3 text-center"><?= $row['location_name'] ?></td>
                        <td class="px-4 py-3 text-center"><?= $row['quantity'] ?></td>

                        <td class="px-4 py-3 text-center">
                            <?php
                            $color = [
                                "pending" => "text-yellow-500",
                                "cancelled" => "text-red-500",
                                "received" => "text-blue-500",
                                "approved" => "text-green-500",
                                "invoiced" => "text-purple-500"
                            ];

                            $status_class = $color[$row['status']] ?? "text-gray-500";
                            ?>
                            <span class="px-3 py-1 font-bold <?= $status_class ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center font-bold text-red-500"><?= number_format($row['total_amount'], 2) ?></td>

                        <?php if ($isAdmin): ?>
                            <td class="px-4 py-2 flex gap-2 items-center justify-center">

                                <!-- Receive action removed — handled automatically when status is approved -->

                                <button onclick="openEditModal('editPurchaseModal', this)"
                                    data-id="<?= $row['id'] ?>"
                                    data-po_id="<?= $row['po_code'] ?>"
                                    data-supplier_id="<?= $row['supplier_id'] ?>"
                                    data-product_id="<?= $row['product_id'] ?>"
                                    data-location_id="<?= $row['location_id'] ?>"
                                    data-quantity="<?= $row['quantity'] ?>"
                                    data-order_date="<?= $row['date'] ?>"
                                    data-units_price="<?= $row['unit_price'] ?>"
                                    data-total_amount="<?= $row['total_amount'] ?>"
                                    data-status="<?= $row['status'] ?>"
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-edit'></i>
                                </button>
                                <button onclick="if(confirm('Are you sure you want to delete this order?')){
                            window.location.href='?page=transactions&sub=purchase&delete=<?= $row['id'] ?>';}"
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-trash'></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-package text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada PO yang tersedia</p>
                        <p class="text-sm mt-1">Buat Purchase Order terlebih dahulu</p>
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


<!-- Modal Create PO -->
<div id="purchaseModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('purchaseModal')"></div>
    <div class="modal-flex-container">

        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Buat Order Baru</h3>
                <button onclick="closeModal('purchaseModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Supplier</label>
                    <select name="supplier_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">Pilih Supplier</option>
                        <?php
                        // Ambil data supplier dari database
                        $query = "SELECT id, name FROM suppliers ORDER BY name ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Produk</label>
                        <select name="product_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Produk</option>
                            <?php
                            // Ambil data produk dari database
                            $query = "SELECT id, product_code, name FROM products ORDER BY name ASC";
                            $result = mysqli_query($conn, $query);

                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<option value="' . $row['id'] . '">' . $row['name'] . ' (' . $row['product_code'] . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Jumlah</label>
                        <input type="number" name="quantity" id="quantyty" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Location</label>
                    <select name="location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">Pilih Location</option>
                        <?php
                        // Ambil data location dari database
                        $query = "SELECT id, name FROM locations ORDER BY name ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['id'] . '">' . $row['name'] . ' </option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Tanggal Order</label>
                        <input type="date" name="order_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
                    </div>
                </div>


                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Units Price</label>
                        <input type="number" name="units_price" id="units_price" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Total Amount</label>
                        <input type="number" name="total_amount" id="total_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0" readonly>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('purchaseModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Buat Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editPurchaseModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editPurchaseModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">

            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Order</h3>
                <button onclick="closeModal('editPurchaseModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class=mb-4>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">PO ID</label>
                    <input type="text" name="po_id" id="edit_po_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Supplier</label>
                    <select name="supplier_id" id="edit_supplier_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none cursor-pointer">
                        <option value="">Pilih Supplier</option>
                        <?php
                        // Ambil data category dari database
                        $query = "SELECT id, name FROM suppliers ORDER BY name ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Produk</label>
                        <select name="product_id" id="edit_product_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#e6b949] outline-none cursor-pointer">
                            <option value="">Pilih Produk</option>
                            <?php
                            // Ambil data produk dari database
                            $query = "SELECT id, product_code, name FROM products ORDER BY name ASC";
                            $result = mysqli_query($conn, $query);

                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<option value="' . $row['id'] . '">' . $row['name'] . ' (' . $row['product_code'] . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Jumlah</label>
                        <input type="number" name="quantity" id="edit_quantity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">location</label>
                    <select name="location_id" id="edit_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">Pilih Location</option>
                        <?php
                        // Ambil data location dari database
                        $query = "SELECT id, name FROM locations ORDER BY name ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['id'] . '">' . $row['name'] . ' </option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Tanggal Order</label>
                    <input type="date" name="order_date" id="edit_order_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e6b949] outline-none">
                </div>


                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Units Price</label>
                        <input type="number" name="units_price" id="edit_units_price" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Total Amount</label>
                        <input type="number" name="total_amount" id="edit_total_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0" readonly>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Status</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('editPurchaseModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const quantityInput = document.getElementById('quantyty');
    const unitsPriceInput = document.getElementById('units_price');
    const totalAmountInput = document.getElementById('total_amount');

    function updateTotalAmount() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitsPrice = parseFloat(unitsPriceInput.value) || 0;
        totalAmountInput.value = (quantity * unitsPrice).toFixed(2);
    }

    quantityInput.addEventListener('input', updateTotalAmount);
    unitsPriceInput.addEventListener('input', updateTotalAmount);
</script>