<?php
require 'config.php';

header('Content-Type: application/json');
try {
    $no_penjualan = sanitize($_GET['no_penjualan'] ?? '');
    if (empty($no_penjualan)) {
        throw new Exception("No penjualan tidak boleh kosong!");
    }

    $stmt = $conn->prepare("SELECT o.*, oi.* FROM online_wa o 
                           LEFT JOIN order_items oi ON o.id = oi.order_id 
                           WHERE o.no_penjualan = ?");
    $stmt->bind_param("s", $no_penjualan);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = ['is_imported' => false, 'items' => []];
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response['is_imported'] = ($row['sumber_layanan'] === 'IMPORT');
        
        do {
            if ($row['kode_barang']) {
                $response['items'][] = [
                    'id' => (int)$row['id'],
                    'kode_barang' => htmlspecialchars($row['kode_barang']),
                    'nama_barang' => htmlspecialchars($row['nama_barang']),
                    'qty' => (int)$row['qty'],
                    'harga_jual' => (float)$row['harga_jual'],
                    'sub_total' => (float)$row['sub_total'],
                    'is_checked' => (bool)$row['is_checked']
                ];
            }
        } while ($row = $result->fetch_assoc());
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error in check_import.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}