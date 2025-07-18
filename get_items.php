<?php
require 'config.php';
requireAuth();

// Set header di awal untuk memastikan selalu dikirim
header('Content-Type: application/json');

try {
    if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
        throw new Exception('Invalid order ID');
    }

    $order_id_get = (int)$_GET['order_id'];
    error_log("Fetching items for order_id: $order_id_get in get_items.php"); 

    // Ambil daftar item
    $stmt_items_get = $conn->prepare("SELECT id, kode_barang, barcode, nama_barang, qty, qty_ready, harga_jual, diskon_item, sub_total, is_checked 
                                    FROM pending_import_items WHERE pending_import_id = ?");
    if (!$stmt_items_get) {
        throw new Exception("Prepare failed (get items): " . $conn->error);
    }
    $stmt_items_get->bind_param("i", $order_id_get);
    if (!$stmt_items_get->execute()) {
        throw new Exception("Execute failed (get items): " . $stmt_items_get->error);
    }
    $result_items_get = $stmt_items_get->get_result();
    $items_list_get = $result_items_get->fetch_all(MYSQLI_ASSOC);

    // Hitung total qty
    $stmt_qty_get = $conn->prepare("SELECT SUM(qty) as total_qty FROM pending_import_items WHERE pending_import_id = ?");
    if (!$stmt_qty_get) {
        throw new Exception("Prepare failed for total qty (get): " . $conn->error);
    }
    $stmt_qty_get->bind_param("i", $order_id_get);
    $stmt_qty_get->execute();
    $total_qty_result_get = $stmt_qty_get->get_result()->fetch_assoc();
    $total_qty_val_get = $total_qty_result_get['total_qty'] ?? 0;

    // Hitung total qty ready
    $stmt_qty_ready_get = $conn->prepare("SELECT SUM(qty_ready) as total_qty_ready FROM pending_import_items WHERE pending_import_id = ?");
    if (!$stmt_qty_ready_get) {
        throw new Exception("Prepare failed for total qty ready (get): " . $conn->error);
    }
    $stmt_qty_ready_get->bind_param("i", $order_id_get);
    $stmt_qty_ready_get->execute();
    $total_qty_ready_result_get = $stmt_qty_ready_get->get_result()->fetch_assoc();
    $total_qty_ready_val_get = $total_qty_ready_result_get['total_qty_ready'] ?? 0;

    // Hitung total diskon item <<<<------ BARIS TAMBAHAN DI SINI
    $stmt_total_diskon_get = $conn->prepare("SELECT SUM(diskon_item) as total_diskon FROM pending_import_items WHERE pending_import_id = ?");
    if (!$stmt_total_diskon_get) {
        throw new Exception("Prepare failed for total diskon (get): " . $conn->error);
    }
    $stmt_total_diskon_get->bind_param("i", $order_id_get);
    $stmt_total_diskon_get->execute();
    $total_diskon_result_get = $stmt_total_diskon_get->get_result()->fetch_assoc();
    $total_diskon_item_val_get = $total_diskon_result_get['total_diskon'] ?? 0;

    // Hitung total saat ini (sub_total)
    $stmt_total_get = $conn->prepare("SELECT SUM(sub_total) as total_saat_ini FROM pending_import_items WHERE pending_import_id = ?");
    if (!$stmt_total_get) {
        throw new Exception("Prepare failed for total (get): " . $conn->error);
    }
    $stmt_total_get->bind_param("i", $order_id_get);
    $stmt_total_get->execute();
    $total_result_get = $stmt_total_get->get_result()->fetch_assoc();
    $total_saat_ini_val_get = $total_result_get['total_saat_ini'] ?? 0;

    // Ambil total awal order jika ada (opsional, tapi baik untuk konsistensi jika JS mengandalkannya dari sini juga)
    // Anda mungkin sudah mengelolanya di $_SESSION['order_initial_total'] yang di-pass ke initialTotalGlobal di JS
    // Jika ingin lebih konsisten, Anda bisa mengambilnya juga dari DB di sini.
    // $stmt_initial_total_get = $conn->prepare("SELECT total_awal FROM pending_imports WHERE id = ?"); // Asumsi ada kolom total_awal di pending_imports
    // if ($stmt_initial_total_get) {
    //     $stmt_initial_total_get->bind_param("i", $order_id_get);
    //     $stmt_initial_total_get->execute();
    //     $initial_total_row = $stmt_initial_total_get->get_result()->fetch_assoc();
    //     $order_initial_total_val = $initial_total_row['total_awal'] ?? $_SESSION['order_initial_total'] ?? 0; // Fallback
    // } else {
    //     $order_initial_total_val = $_SESSION['order_initial_total'] ?? 0;
    // }


    // Kirim response JSON
    echo json_encode([
        'success' => true,
        'items' => $items_list_get,
        'total_saat_ini' => $total_saat_ini_val_get,
        'total_qty' => $total_qty_val_get,
        'total_qty_ready' => $total_qty_ready_val_get,
        'total_diskon_item' => $total_diskon_item_val_get, // <-- Ditambahkan
        // 'order_initial_total' => $order_initial_total_val // Jika Anda memutuskan untuk mengambilnya dari DB
    ]);

} catch (Exception $e_get_items) {
    error_log("Error in get_items.php: " . $e_get_items->getMessage());
    // Pastikan kode status HTTP diatur dengan benar untuk error
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error' => "Error fetching items details: " . $e_get_items->getMessage()
    ]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>