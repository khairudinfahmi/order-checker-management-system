<?php
require 'config.php';
header('Content-Type: application/json');

// Dapatkan no_pesanan dari request GET
$no_pesanan = sanitize($_GET['no_penjualan'] ?? '');

if (empty($no_pesanan)) {
    echo json_encode(['success' => false, 'message' => 'No. Penjualan tidak boleh kosong.']);
    exit;
}

try {
    // Gunakan fungsi getInvoiceData dari config.php
    $invoice_data = getInvoiceData($no_pesanan);

    if ($invoice_data) {
        // Jika data ditemukan, kirim sebagai response sukses
        echo json_encode([
            'success' => true,
            'data' => [
                'nama_customer' => $invoice_data['nama_penerima'] ?? '',
                'telepon_customer' => $invoice_data['telepon_penerima'] ?? '',
                'alamat' => $invoice_data['alamat_penerima'] ?? '',
                'layanan_pengiriman' => $invoice_data['courier_name'] ?? ''
            ]
        ]);
    } else {
        // Jika tidak ditemukan, kirim response gagal
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan di sistem invoice.']);
    }
} catch (Exception $e) {
    // Tangani error jika terjadi
    error_log("Error in get_invoice_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
}

$conn->close();
if (isset($invoice_conn)) {
    $invoice_conn->close();
}
?>