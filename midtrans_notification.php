<?php
// File: midtrans_notification.php (di root project)
// File ini menerima notifikasi otomatis dari Midtrans (Webhook)
// Setiap kali ada perubahan status transaksi, Midtrans akan kirim data ke sini

// Include database connection
require_once __DIR__ . '/config/database.php';

// Include Midtrans configuration
require_once __DIR__ . '/config/midtrans.php';

// Ambil data JSON yang dikirim Midtrans
$json = file_get_contents('php://input');
$notification = json_decode($json);

// Log semua notifikasi untuk debugging (opsional tapi sangat berguna)
$log_file = 'midtrans_notifications.log';
$log_message = date('Y-m-d H:i:s') . " - Notification received:\n" . $json . "\n\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

try {
    // Buat instance Notification dari Midtrans
    $notif = new \Midtrans\Notification();
    
    // Ambil data dari notification
    $transaction_status = $notif->transaction_status;
    $payment_type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud_status = $notif->fraud_status;
    $transaction_id = $notif->transaction_id;
    $transaction_time = $notif->transaction_time;
    
    // Cari payment record di database berdasarkan order_id
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    // Validasi: Cek apakah payment ada
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
        exit;
    }
    
    $invoice_id = $payment['invoice_id'];
    
    // Tentukan status berdasarkan transaction_status dari Midtrans
    $invoice_status = 'unpaid';
    $payment_status = $transaction_status;
    
    // Logic penentuan status (sama seperti di callback)
    if ($transaction_status == 'capture') {
        if ($fraud_status == 'accept') {
            $invoice_status = 'paid';
            $payment_status = 'success';
        }
    } else if ($transaction_status == 'settlement') {
        $invoice_status = 'paid';
        $payment_status = 'success';
    } else if ($transaction_status == 'pending') {
        $invoice_status = 'unpaid';
        $payment_status = 'pending';
    } else if ($transaction_status == 'deny') {
        $invoice_status = 'cancelled';
        $payment_status = 'failed';
    } else if ($transaction_status == 'expire') {
        $invoice_status = 'unpaid';
        $payment_status = 'expired';
    } else if ($transaction_status == 'cancel') {
        $invoice_status = 'cancelled';
        $payment_status = 'cancelled';
    }
    
    // Update payment di database
    $stmt = $conn->prepare("
        UPDATE payments 
        SET transaction_id = ?, 
            payment_type = ?, 
            transaction_status = ?,
            transaction_time = ?,
            updated_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->bind_param("sssss", $transaction_id, $payment_type, $payment_status, $transaction_time, $order_id);
    $stmt->execute();
    
    // Update invoice status
    $stmt = $conn->prepare("UPDATE invoices SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $invoice_status, $invoice_id);
    $stmt->execute();
    
    // Log success
    $success_log = date('Y-m-d H:i:s') . " - SUCCESS: Order $order_id updated to $payment_status\n\n";
    file_put_contents($log_file, $success_log, FILE_APPEND);
    
    // Response ke Midtrans
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Notification processed',
        'order_id' => $order_id,
        'payment_status' => $payment_status
    ]);
    
} catch (Exception $e) {
    // Log error
    $error_log = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n\n";
    file_put_contents($log_file, $error_log, FILE_APPEND);
    
    // Response error ke Midtrans
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

exit;