<?php

require 'config.php';

requireAuth();



// Set header untuk response JSON

header('Content-Type: application/json');



try {

    // Validasi CSRF token

    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {

        throw new Exception("Invalid CSRF token!");

    }



    // Validasi input

    if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id']) || 

        !isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {

        throw new Exception('Invalid order or item ID');

    }



    $order_id = (int)$_GET['order_id'];

    $item_id = (int)$_GET['item_id'];



    error_log("Attempting to delete item_id: $item_id from order_id: $order_id"); // Log untuk debugging



    // Cek apakah item ada dan belum dicek sepenuhnya

    $stmt_check = $conn->prepare("SELECT is_checked, kode_barang, nama_barang FROM pending_import_items WHERE id = ? AND pending_import_id = ?");

    if (!$stmt_check) {

        throw new Exception("Prepare failed for check query: " . $conn->error);

    }

    $stmt_check->bind_param("ii", $item_id, $order_id);

    $stmt_check->execute();

    $item = $stmt_check->get_result()->fetch_assoc();



    if (!$item) {

        throw new Exception("Item tidak ditemukan!");

    }



    if ($item['is_checked']) {

        throw new Exception("Item yang sudah dicek tidak dapat dihapus!");

    }



    // Hapus item

    $stmt_delete = $conn->prepare("DELETE FROM pending_import_items WHERE id = ? AND pending_import_id = ?");

    if (!$stmt_delete) {

        throw new Exception("Prepare failed for delete query: " . $conn->error);

    }

    $stmt_delete->bind_param("ii", $item_id, $order_id);

    if (!$stmt_delete->execute()) {

        throw new Exception("Execute failed for delete query: " . $stmt_delete->error);

    }



    if ($stmt_delete->affected_rows === 0) {

        throw new Exception("Tidak ada item yang dihapus!");

    }



    // Log aktivitas penghapusan item

    // Ambil no_penjualan untuk log
    $stmt_get_no_nota = $conn->prepare("SELECT no_penjualan FROM pending_imports WHERE id = ?");
    $stmt_get_no_nota->bind_param("i", $order_id);
    $stmt_get_no_nota->execute();
    $no_penjualan_log = $stmt_get_no_nota->get_result()->fetch_assoc()['no_penjualan'] ?? "ID: {$order_id}";
    $stmt_get_no_nota->close();

    logActivity($conn, $_SESSION['user']['id'], 'delete_pending_item', "Hapus item: {$item['nama_barang']} dari order: {$no_penjualan_log}", [
        'pending_import_id' => $order_id,
        'no_penjualan' => $no_penjualan_log,
        'item_id' => $item_id,
        'kode_barang' => $item['kode_barang']
    ]);



    // Cek apakah semua item sudah selesai dicek setelah penghapusan

    $stmt_check_qty = $conn->prepare("SELECT COUNT(*) as qty_mismatch 

                                     FROM pending_import_items 

                                     WHERE pending_import_id = ? 

                                     AND qty_ready != qty");

    $stmt_check_qty->bind_param("i", $order_id);

    $stmt_check_qty->execute();

    $qty_mismatch = $stmt_check_qty->get_result()->fetch_assoc()['qty_mismatch'];



    if ((int)$qty_mismatch === 0) {

        $_SESSION['order_ready_to_complete'] = true;

    } else {

        $_SESSION['order_ready_to_complete'] = false;

    }



    echo json_encode([

        'success' => true,

        'message' => 'Item berhasil dihapus!'

    ]);



} catch (Exception $e) {

    error_log("Error in delete_item.php: " . $e->getMessage());

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);

}



$conn->close();

?>