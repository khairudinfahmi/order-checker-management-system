<?php
require 'config.php'; // Ensure this path is correct
header('Content-Type: application/json');

try {
    $order_id = $_GET['order_id'] ?? 0;
    // Validate order_id: should be a positive integer
    if (!filter_var($order_id, FILTER_VALIDATE_INT) || $order_id <= 0) {
        throw new Exception("Invalid order ID provided.");
    }

    // Fetch all items for the order
    // Assuming 'diskon_item' and 'sub_total' in 'order_items' table store the actual monetary values
    // (e.g., 20000 for Rp 20.000)
    $stmt_items = $conn->prepare("SELECT kode_barang, nama_barang, qty, harga_jual, diskon_item, sub_total FROM order_items WHERE order_id = ?");
    if (!$stmt_items) {
        // Log detailed error if possible, but don't expose too much to client
        error_log("Prepare failed (items): " . $conn->error);
        throw new Exception("Failed to prepare item statement.");
    }
    $stmt_items->bind_param("i", $order_id);
    if (!$stmt_items->execute()) {
        error_log("Execute failed (items): " . $stmt_items->error);
        throw new Exception("Failed to execute item statement.");
    }
    $result_items = $stmt_items->get_result();
    $items = $result_items->num_rows > 0 ? $result_items->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_items->close();

    // Calculate totals
    // Removed '/ 100' assuming 'diskon_item' and 'sub_total' are stored as direct values
    $stmt_total = $conn->prepare("
        SELECT 
            SUM(qty) as total_qty,
            SUM(diskon_item) as total_diskon_item, 
            SUM(sub_total) as total_saat_ini 
        FROM order_items 
        WHERE order_id = ?
    ");
    if (!$stmt_total) {
        error_log("Prepare failed (totals): " . $conn->error);
        throw new Exception("Failed to prepare total statement.");
    }
    $stmt_total->bind_param("i", $order_id);
    if (!$stmt_total->execute()) {
        error_log("Execute failed (totals): " . $stmt_total->error);
        throw new Exception("Failed to execute total statement.");
    }
    $result_totals = $stmt_total->get_result();
    $totals = $result_totals->fetch_assoc();
    $stmt_total->close();

    // Ensure numeric types for sums, default to 0 if null
    $total_qty_val = isset($totals['total_qty']) ? (int)$totals['total_qty'] : 0;
    $total_diskon_item_val = isset($totals['total_diskon_item']) ? (float)$totals['total_diskon_item'] : 0;
    $total_saat_ini_val = isset($totals['total_saat_ini']) ? (float)$totals['total_saat_ini'] : 0;

    echo json_encode([
        'success' => true,
        'items' => $items,
        'total_qty' => $total_qty_val,
        'total_diskon_item' => $total_diskon_item_val,
        'total_saat_ini' => $total_saat_ini_val
    ]);

} catch (Exception $e) {
    // Log the exception message for server-side debugging
    error_log("Error in get_order_items.php: " . $e->getMessage());
    
    // Send a generic error message to the client
    if (!headers_sent()) {
        http_response_code(500); // Internal Server Error
    }
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching order details. Please try again.'
        // For development, you might want to include $e->getMessage(), but not for production.
        // 'dev_error' => $e->getMessage() 
    ]);
} finally {
    // Ensure the database connection is closed if it was opened
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>