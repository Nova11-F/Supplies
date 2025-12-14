<?php
// File: public/page/transactions/payment_callback.php
// File ini menangani hasil setelah user selesai bayar

// Include database
include __DIR__ . '/../../../config/database.php';

// Include Midtrans
require_once __DIR__ . '/../../../config/midtrans.php';

// Ambil parameter dari URL
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Validasi order_id
if (empty($order_id)) {
    echo "<script>
        alert('Order ID tidak ditemukan!');
        window.location.href='index.php?page=transactions&sub=invoice';
    </script>";
    exit;
}

// ===== FUNGSI UNTUK MENAMBAH STOCK OTOMATIS SETELAH PAYMENT =====
function addToStock($conn, $invoice_id) {
    // Get invoice items dengan location_id
    $stmt = $conn->prepare("
        SELECT 
            ii.product_id,
            ii.quantity,
            poi.location_id,
            p.name as product_name
        FROM invoice_items ii
        JOIN invoices i ON i.id = ii.invoice_id
        JOIN po_items poi ON poi.product_id = ii.product_id AND poi.po_id = i.po_id
        JOIN products p ON p.id = ii.product_id
        WHERE ii.invoice_id = ?
    ");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    $updated_products = [];
    
    while ($item = $items->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $location_id = $item['location_id'];
        $product_name = $item['product_name'];
        
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
            // Update existing stock - TAMBAHKAN quantity
            $stock_row = $stock_result->fetch_assoc();
            $old_stock = $stock_row['current_stock'];
            $new_stock = $old_stock + $quantity;
            
            $update_stmt = $conn->prepare("
                UPDATE stock 
                SET current_stock = ?, 
                    last_updated = NOW() 
                WHERE id = ?
            ");
            $update_stmt->bind_param("ii", $new_stock, $stock_row['id']);
            $update_stmt->execute();
            
            $updated_products[] = "$product_name: $old_stock → $new_stock units (+$quantity)";
        } else {
            // Create new stock record
            $min_stock = 10; // Default minimum stock
            
            $insert_stmt = $conn->prepare("
                INSERT INTO stock (product_id, location_id, min_stock, current_stock, last_updated) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insert_stmt->bind_param("iiii", $product_id, $location_id, $min_stock, $quantity);
            $insert_stmt->execute();
            
            $updated_products[] = "$product_name: NEW STOCK → $quantity units";
        }
    }
    
    return $updated_products;
}

try {
    // Cek status transaksi ke Midtrans
    $status_response = \Midtrans\Transaction::status($order_id);
    
    // Ambil informasi dari response
    $transaction_status = $status_response->transaction_status;
    $fraud_status = isset($status_response->fraud_status) ? $status_response->fraud_status : '';
    $transaction_id = $status_response->transaction_id;
    $payment_type = $status_response->payment_type;
    $transaction_time = $status_response->transaction_time;
    
    // Ambil data payment dari database
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    // Validasi payment record
    if (!$payment) {
        throw new Exception("Payment data tidak ditemukan di database");
    }
    
    $invoice_id = $payment['invoice_id'];
    
    // Tentukan status berdasarkan response Midtrans
    $invoice_status = 'unpaid';
    $payment_status = $transaction_status;
    $should_update_stock = false; // Flag untuk update stock
    
    // Logic penentuan status
    if ($transaction_status == 'capture') {
        // Credit card berhasil di-capture
        if ($fraud_status == 'accept') {
            $invoice_status = 'paid';
            $payment_status = 'success';
            $should_update_stock = true; // ✅ Update stock
        }
    } else if ($transaction_status == 'settlement') {
        // Pembayaran berhasil (untuk non-credit card)
        $invoice_status = 'paid';
        $payment_status = 'success';
        $should_update_stock = true; // ✅ Update stock
    } else if ($transaction_status == 'pending') {
        // Pembayaran pending
        $invoice_status = 'unpaid';
        $payment_status = 'pending';
    } else if ($transaction_status == 'deny') {
        // Pembayaran ditolak
        $invoice_status = 'unpaid';
        $payment_status = 'failed';
    } else if ($transaction_status == 'expire') {
        // Pembayaran kadaluarsa
        $invoice_status = 'unpaid';
        $payment_status = 'expired';
    } else if ($transaction_status == 'cancel') {
        // Pembayaran dibatalkan
        $invoice_status = 'cancelled';
        $payment_status = 'cancelled';
    }
    
    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Update payment record
        $stmt = $conn->prepare("
            UPDATE payments 
            SET transaction_id = ?, 
                payment_type = ?, 
                transaction_status = ?,
                transaction_time = ?
            WHERE order_id = ?
        ");
        $stmt->bind_param("sssss", $transaction_id, $payment_type, $payment_status, $transaction_time, $order_id);
        $stmt->execute();
        
        // Update invoice status
        $stmt = $conn->prepare("
            UPDATE invoices 
            SET status = ?,
                payment_date = NOW(),
                payment_method = 'midtrans',
                transaction_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $invoice_status, $transaction_id, $invoice_id);
        $stmt->execute();
        
        // ===== CRITICAL: UPDATE STOCK JIKA PAYMENT SUKSES =====
        $stock_updates = [];
        if ($should_update_stock) {
            $stock_updates = addToStock($conn, $invoice_id);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect dengan pesan sesuai status
        if ($payment_status == 'success') {
            $stock_info = !empty($stock_updates) ? "\\n\\nStock Updated:\\n" . implode("\\n", $stock_updates) : "";
            echo "<script>
                alert('✅ PAYMENT SUCCESSFUL!" . $stock_info . "\\n\\nInvoice has been paid successfully.\\nTransaction ID: " . addslashes($transaction_id) . "');
                window.location.href='index.php?page=transactions&sub=invoice';
            </script>";
        } else if ($payment_status == 'pending') {
            echo "<script>
                alert('⏳ Payment Pending\\n\\nPlease complete your payment.\\nOrder ID: " . addslashes($order_id) . "');
                window.location.href='index.php?page=transactions&sub=invoice';
            </script>";
        } else if ($payment_status == 'expired') {
            echo "<script>
                alert('⏰ Payment Expired\\n\\nPlease make a new payment.');
                window.location.href='index.php?page=transactions&sub=invoice';
            </script>";
        } else {
            echo "<script>
                alert('❌ Payment Failed\\n\\nPayment was unsuccessful or cancelled.\\nPlease try again.');
                window.location.href='index.php?page=transactions&sub=invoice';
            </script>";
        }
        
    } catch (Exception $e) {
        // Rollback database transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Jika error
    echo "<script>
        alert('Error: " . addslashes($e->getMessage()) . "');
        window.location.href='index.php?page=transactions&sub=invoice';
    </script>";
}

exit;