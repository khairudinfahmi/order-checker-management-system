<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'config.php';
requireAuth();

// Initialize session variables to avoid undefined index errors
$_SESSION['order_ready_to_complete'] = $_SESSION['order_ready_to_complete'] ?? false;

$order_data = [];
$items = [];
$total_subtotal = 0;
$total_qty = 0;
$total_qty_ready = 0;
$total_diskon_item = 0; // Initialize total_diskon_item
$_SESSION['is_scan_action'] = $_SESSION['is_scan_action'] ?? false; // Initialize if not set

// Proses Pencarian Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_order'])) {
    $_SESSION['is_scan_action'] = false; // Reset scan action flag on new search
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token keamanan tidak valid!");
        }
        
        $search_input = mb_strtoupper(sanitize($_POST['no_penjualan'] ?? ''));
        $numeric_part = preg_replace('/[^0-9]/', '', $search_input);
        
        if (empty($numeric_part)) {
            throw new Exception("Input tidak valid! Masukkan No.Penjualan atau 4 digit terakhir");
        }

        $result_search = null;  
        
        if (strlen($numeric_part) === 4) {
            $stmt_search = $conn->prepare("SELECT * FROM pending_imports 
                WHERE RIGHT(
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(no_penjualan, '-', 2), 
                        '-', 
                        -1
                    ), 
                    4
                ) = ?");
            $stmt_search->bind_param("s", $numeric_part);
        } else {
            if (preg_match('/^JO-\d{8}-S$/', $search_input)) { // Example: JO-2506050007-S (8 digits in middle)
                 $stmt_search = $conn->prepare("SELECT * FROM pending_imports 
                     WHERE no_penjualan = ?");
                 $stmt_search->bind_param("s", $search_input);
            } else if (preg_match('/^JO-\d{6}\d{4}-S$/', $search_input)){ // Original more complex pattern
                 $stmt_search = $conn->prepare("SELECT * FROM pending_imports 
                     WHERE no_penjualan = ?");
                 $stmt_search->bind_param("s", $search_input);
            }
             else { // General search if not 4-digit and not matching specific JO format
                 $stmt_search = $conn->prepare("SELECT * FROM pending_imports 
                     WHERE no_penjualan LIKE CONCAT('%', ?, '%')");
                 $stmt_search->bind_param("s", $search_input); // Search for the input string anywhere
             }
        }


        if (!$stmt_search->execute()) {
            throw new Exception("Error executing query: " . $stmt_search->error);
        }
        
        $result_search = $stmt_search->get_result();
        
        if (!$result_search) {
            throw new Exception("Database query failed");
        }

        if ($result_search->num_rows === 0) {
            // Log aktivitas pencarian gagal
            logActivity($conn, $_SESSION['user']['id'], 'search_order_failed', "Pencarian gagal untuk No.Penjualan: " . htmlspecialchars($search_input), ['search_term' => $search_input]);
            throw new Exception("No.Penjualan " . htmlspecialchars($search_input) . " tidak ditemukan");
        }
        
        $order_data = $result_search->fetch_assoc();
        if (!$order_data) {
            throw new Exception("Gagal mengambil data order");
        }

        // Log aktivitas pencarian sukses
        logActivity($conn, $_SESSION['user']['id'], 'search_order_success', "Pencarian sukses untuk No.Penjualan: " . htmlspecialchars($order_data['no_penjualan']), ['no_penjualan' => $order_data['no_penjualan']]);

        $stmt_items_search = $conn->prepare("SELECT * FROM pending_import_items 
                                                WHERE pending_import_id = ?");
        $stmt_items_search->bind_param("i", $order_data['id']);
        
        if (!$stmt_items_search->execute()) {
            throw new Exception("Error fetching items: " . $stmt_items_search->error);
        }
        
        $items_result_search = $stmt_items_search->get_result();
        $items = $items_result_search->fetch_all(MYSQLI_ASSOC);
        
        $_SESSION['initial_items'] = $items;

        $stmt_qty_calc_search = $conn->prepare("SELECT SUM(qty) as total_qty FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_qty_calc_search->bind_param("i", $order_data['id']);
        $stmt_qty_calc_search->execute();
        $total_qty_result_calc_search = $stmt_qty_calc_search->get_result()->fetch_assoc();
        $total_qty = $total_qty_result_calc_search['total_qty'] ?? 0;

        $stmt_qty_ready_calc_search = $conn->prepare("SELECT SUM(qty_ready) as total_qty_ready FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_qty_ready_calc_search->bind_param("i", $order_data['id']);
        $stmt_qty_ready_calc_search->execute();
        $total_qty_ready_result_calc_search = $stmt_qty_ready_calc_search->get_result()->fetch_assoc();
        $total_qty_ready = $total_qty_ready_result_calc_search['total_qty_ready'] ?? 0;

        $stmt_total_diskon_calc_search = $conn->prepare("SELECT SUM(diskon_item) as total_diskon FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_diskon_calc_search->bind_param("i", $order_data['id']);
        $stmt_total_diskon_calc_search->execute();
        $total_diskon_result_calc_search = $stmt_total_diskon_calc_search->get_result()->fetch_assoc();
        $total_diskon_item = $total_diskon_result_calc_search['total_diskon'] ?? 0;

        $stmt_total_calc_search = $conn->prepare("SELECT SUM(sub_total) as total FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_calc_search->bind_param("i", $order_data['id']);
        $stmt_total_calc_search->execute();
        $total_result_calc_search = $stmt_total_calc_search->get_result()->fetch_assoc();
        $_SESSION['order_initial_total'] = $total_result_calc_search['total'] ?? 0;
        
        $total_subtotal = $total_result_calc_search['total'] ?? 0;

        $_SESSION['order_details'] = [
            'id' => $order_data['id'],
            'input_time' => time(),
        ];

        // Initialize form_data from fetched order_data only if not already set for this specific order
if ($order_data && (!isset($_SESSION['form_data']['order_id']) || $_SESSION['form_data']['order_id'] != $order_data['id'])) {
    $no_penjualan = $order_data['no_penjualan'];
    $invoice_data = getInvoiceData($no_penjualan); // Fungsi dari config.php

    if ($invoice_data) {
        // Prioritaskan data dari invoice
        $_SESSION['form_data'] = [
            'order_id' => $order_data['id'],
            'nama_customer' => mb_strtoupper($invoice_data['nama_penerima']),
            'layanan_pengiriman' => mb_strtoupper($invoice_data['courier_name']),
            'alamat' => mb_strtoupper($invoice_data['alamat_penerima']),
            'telepon_penerima' => $invoice_data['telepon_penerima'],
            'sumber_layanan' => mb_strtoupper($order_data['sumber_layanan'] ?? 'WA1')
        ];
    } else {
        // Gunakan data dari pending_imports jika tidak ada di invoice
        $_SESSION['form_data'] = [
            'order_id' => $order_data['id'],
            'nama_customer' => mb_strtoupper($order_data['nama_customer'] ?? ''),
            'sumber_layanan' => mb_strtoupper($order_data['sumber_layanan'] ?? 'WA1'),
            'layanan_pengiriman' => mb_strtoupper($order_data['layanan_pengiriman'] ?? 'GO SEND INSTANT/SAMEDAY'),
            'alamat' => mb_strtoupper($order_data['alamat'] ?? ''),
            'telepon_penerima' => ''
        ];
    }
}
    } catch (Exception $e) {
        error_log("Error in search process: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
        $_SESSION['alert_timer'] = 2000;
        header("Location: index.php");
        exit;
    }
}

// Handle GET parameter untuk mempertahankan order setelah reload
if (!$order_data && (isset($_GET['order_id']) || isset($_SESSION['order_details']['id']))) {
    try {
        $pending_import_id_get = (int)($_GET['order_id'] ?? $_SESSION['order_details']['id'] ?? 0);
        if ($pending_import_id_get <= 0) {
            if (isset($_GET['order_id'])) {
                if (!isset($_SESSION['order_details']['id']) || $_SESSION['order_details']['id'] != $pending_import_id_get || $pending_import_id_get === 0) {
                    unset($_SESSION['order_details']);
                    unset($_SESSION['form_data']);    
                    unset($_SESSION['initial_items']);
                    unset($_SESSION['order_initial_total']);
                }
            } else if (isset($_SESSION['order_details']['id']) && $_SESSION['order_details']['id'] == 0) {
                unset($_SESSION['order_details']);
                unset($_SESSION['form_data']);    
                unset($_SESSION['initial_items']);
                unset($_SESSION['order_initial_total']);
            }
        }
        
        if ($pending_import_id_get > 0) {
            if (!isset($_SESSION['order_details']) || $_SESSION['order_details']['id'] != $pending_import_id_get) {
                unset($_SESSION['form_data']);
                unset($_SESSION['initial_items']);
                unset($_SESSION['order_initial_total']);
            }

            $_SESSION['order_details'] = [
                'id' => $pending_import_id_get,
                'input_time' => $_SESSION['order_details']['input_time'] ?? time(), // Preserve original input time if exists
            ];
            
            $stmt_order_get = $conn->prepare("SELECT * FROM pending_imports WHERE id = ?");
            $stmt_order_get->bind_param("i", $pending_import_id_get);
            $stmt_order_get->execute();
            $order_data_get_result = $stmt_order_get->get_result();
            if ($order_data_get_result) {
                $order_data = $order_data_get_result->fetch_assoc();
            }

            if ($order_data) {
                $stmt_items_get = $conn->prepare("SELECT * FROM pending_import_items 
                                                    WHERE pending_import_id = ?");
                $stmt_items_get->bind_param("i", $pending_import_id_get);
                $stmt_items_get->execute();
                $items_get_result = $stmt_items_get->get_result();
                if ($items_get_result) {
                    $items = $items_get_result->fetch_all(MYSQLI_ASSOC);
                }

                if (!isset($_SESSION['initial_items'])) {
                    $_SESSION['initial_items'] = $items;
                }

                $stmt_qty_calc_get = $conn->prepare("SELECT SUM(qty) as total_qty FROM pending_import_items WHERE pending_import_id = ?");
                $stmt_qty_calc_get->bind_param("i", $pending_import_id_get);
                $stmt_qty_calc_get->execute();
                $total_qty_result_calc_get = $stmt_qty_calc_get->get_result()->fetch_assoc();
                $total_qty = $total_qty_result_calc_get['total_qty'] ?? 0;

                $stmt_qty_ready_calc_get = $conn->prepare("SELECT SUM(qty_ready) as total_qty_ready FROM pending_import_items WHERE pending_import_id = ?");
                $stmt_qty_ready_calc_get->bind_param("i", $pending_import_id_get);
                $stmt_qty_ready_calc_get->execute();
                $total_qty_ready_result_calc_get = $stmt_qty_ready_calc_get->get_result()->fetch_assoc();
                $total_qty_ready = $total_qty_ready_result_calc_get['total_qty_ready'] ?? 0;

                $stmt_total_diskon_calc_get = $conn->prepare("SELECT SUM(diskon_item) as total_diskon FROM pending_import_items WHERE pending_import_id = ?");
                $stmt_total_diskon_calc_get->bind_param("i", $pending_import_id_get);
                $stmt_total_diskon_calc_get->execute();
                $total_diskon_result_calc_get = $stmt_total_diskon_calc_get->get_result()->fetch_assoc();
                $total_diskon_item = $total_diskon_result_calc_get['total_diskon'] ?? 0;

                $stmt_total_calc_get = $conn->prepare("SELECT SUM(sub_total) as total FROM pending_import_items WHERE pending_import_id = ?");
                $stmt_total_calc_get->bind_param("i", $pending_import_id_get);
                $stmt_total_calc_get->execute();
                $total_result_calc_get = $stmt_total_calc_get->get_result()->fetch_assoc();
                $total_subtotal = $total_result_calc_get['total'] ?? 0;

                if (!isset($_SESSION['order_initial_total'])) {
                    $_SESSION['order_initial_total'] = $total_subtotal;
                }
                // Hanya inisialisasi form_data jika belum di-set untuk order ini
if ($order_data && (!isset($_SESSION['form_data']['order_id']) || $_SESSION['form_data']['order_id'] != $order_data['id'])) {
    $no_penjualan = $order_data['no_penjualan'];
    $invoice_data = getInvoiceData($no_penjualan);

    if ($invoice_data) {
        $_SESSION['form_data'] = [
            'order_id' => $order_data['id'],
            'nama_customer' => mb_strtoupper($invoice_data['nama_penerima']),
            'layanan_pengiriman' => mb_strtoupper($invoice_data['courier_name']),
            'alamat' => mb_strtoupper($invoice_data['alamat_penerima']),
            'telepon_penerima' => $invoice_data['telepon_penerima'],
            'sumber_layanan' => mb_strtoupper($order_data['sumber_layanan'] ?? 'WA1')
        ];
    } else {
        $_SESSION['form_data'] = [
            'order_id' => $order_data['id'],
            'nama_customer' => mb_strtoupper($order_data['nama_customer'] ?? ''),
            'sumber_layanan' => mb_strtoupper($order_data['sumber_layanan'] ?? 'WA1'),
            'layanan_pengiriman' => mb_strtoupper($order_data['layanan_pengiriman'] ?? 'GO SEND INSTANT/SAMEDAY'),
            'alamat' => mb_strtoupper($order_data['alamat'] ?? ''),
            'telepon_penerima' => ''
        ];
    }
}
            } else { 
                unset($_SESSION['order_details']);
                unset($_SESSION['order_initial_total']);
                unset($_SESSION['initial_items']);
                unset($_SESSION['form_data']);
            }
        }
    } catch (Exception $e) {
        error_log("Error in order reload: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
        $_SESSION['alert_timer'] = 2000;
        unset($_SESSION['order_details']); 
        unset($_SESSION['form_data']);
    }
}

// Proses Scan Barcode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_barcode'])) {
    $_SESSION['is_scan_action'] = true;
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token keamanan tidak valid!");
        }
        
        $required_scan = [
            'pending_import_id' => 'ID Order',
            'nama_customer' => 'Nama Customer',
            'sumber_layanan' => 'Sumber Layanan',
            'layanan_pengiriman' => 'Layanan Pengiriman',
            'barcode' => 'Barcode',
            'qty_scanned' => 'Qty'
        ];
        
        foreach ($required_scan as $field => $label) {
            if (empty($_POST[$field])) { 
                 throw new Exception("{$label} harus diisi!");
            }
        }
        if (sanitize($_POST['layanan_pengiriman'] ?? '') !== 'AMBIL DI TOKO' && empty($_POST['alamat'])) {
            throw new Exception("Alamat harus diisi jika bukan AMBIL DI TOKO!");
        }


        $pending_import_id_scan = (int)$_POST['pending_import_id'];
        $barcode_scan = mb_strtoupper(sanitize($_POST['barcode']));
        $qty_scanned_val = (int)$_POST['qty_scanned'];

        $_SESSION['form_data']['order_id'] = $pending_import_id_scan; 
        $_SESSION['form_data']['nama_customer'] = mb_strtoupper($_POST['nama_customer']);
        $_SESSION['form_data']['sumber_layanan'] = mb_strtoupper($_POST['sumber_layanan']);
        $_SESSION['form_data']['layanan_pengiriman'] = mb_strtoupper($_POST['layanan_pengiriman']);
        $_SESSION['form_data']['alamat'] = mb_strtoupper($_POST['alamat']);
        // ## FIX: Persist phone number in session during scan action ##
        $_SESSION['form_data']['telepon_penerima'] = sanitize($_POST['telepon_penerima'] ?? '');

        error_log("Scan attempt - Barcode: $barcode_scan | Qty Scanned: $qty_scanned_val | OrderID: $pending_import_id_scan");

        $stmt_scan = $conn->prepare("SELECT id, kode_barang, qty, qty_ready, is_checked FROM pending_import_items 
                                        WHERE pending_import_id = ? 
                                        AND (kode_barang = ? OR barcode = ?)");
        $stmt_scan->bind_param("iss", $pending_import_id_scan, $barcode_scan, $barcode_scan);
        $stmt_scan->execute();
        $item_result_scan = $stmt_scan->get_result();
        $item_scan = $item_result_scan->fetch_assoc();

        if (!$item_scan) {
            throw new Exception("Item dengan kode/barcode '$barcode_scan' tidak ditemukan!");
        }

        if ($item_scan['is_checked'] && $item_scan['qty_ready'] >= $item_scan['qty']) {
            throw new Exception("Item SKU: " . htmlspecialchars($item_scan['kode_barang']) . " sudah dicek sepenuhnya!");
        }

        $new_qty_ready_scan = $item_scan['qty_ready'] + $qty_scanned_val;
        if ($new_qty_ready_scan > $item_scan['qty']) {
            throw new Exception("Qty scan ($new_qty_ready_scan) melebihi qty ({$item_scan['qty']}) untuk SKU: " . htmlspecialchars($item_scan['kode_barang']));
        }

        $transaction_active_scan = false;
        
        try {
            $conn->query("START TRANSACTION");
            $transaction_active_scan = true;
            
            $is_checked_scan = ($new_qty_ready_scan >= $item_scan['qty']) ? 1 : 0;
            $stmt_update_scan = $conn->prepare("UPDATE pending_import_items 
                                                 SET qty_ready = ?, is_checked = ? 
                                                 WHERE id = ?");
            $stmt_update_scan->bind_param("iii", $new_qty_ready_scan, $is_checked_scan, $item_scan['id']);
            
            if (!$stmt_update_scan->execute()) {
                throw new Exception("Gagal update status: " . $stmt_update_scan->error);
            }

            // Log scan activity
            logActivity($conn, $_SESSION['user']['id'], 'scan_item', "Scan item: {$item_scan['kode_barang']} (Qty: +{$qty_scanned_val})", [
                'pending_import_id' => $pending_import_id_scan,
                'item_id' => $item_scan['id'],
                'kode_barang' => $item_scan['kode_barang'],
                'scanned_qty' => $qty_scanned_val,
                'new_qty_ready' => $new_qty_ready_scan,
                'is_completed' => (bool)$is_checked_scan
            ]);

            $stmt_check_qty_scan = $conn->prepare("SELECT COUNT(*) as qty_mismatch 
                                                    FROM pending_import_items 
                                                    WHERE pending_import_id = ? 
                                                    AND qty_ready != qty");
            $stmt_check_qty_scan->bind_param("i", $pending_import_id_scan);
            $stmt_check_qty_scan->execute();
            $qty_mismatch_scan = $stmt_check_qty_scan->get_result()->fetch_assoc()['qty_mismatch'];

            if ((int)$qty_mismatch_scan === 0) {
                $_SESSION['order_ready_to_complete'] = true;
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'Semua item telah diperiksa. Silakan selesaikan order.'
                ];
            } else {
                $_SESSION['order_ready_to_complete'] = false;
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => "SKU/BARCODE: " . htmlspecialchars($item_scan['kode_barang']) . " discan! Qty Ready: $new_qty_ready_scan dari {$item_scan['qty']}"
                ];
            }
            $_SESSION['alert_timer'] = 600;

            $conn->query("COMMIT");
            $transaction_active_scan = false;
            header("Location: index.php?order_id=" . $pending_import_id_scan);
            exit;
        } catch (Exception $e_scan_trans) {
            if ($transaction_active_scan) {
                $conn->query("ROLLBACK");
            }
            throw $e_scan_trans; 
        }
    } catch (Exception $e_scan_main) {
        error_log("Barcode scan error: " . $e_scan_main->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e_scan_main->getMessage()];
        $_SESSION['alert_timer'] = 800;
        $redirect_order_id_scan = $_POST['pending_import_id'] ?? ($_SESSION['order_details']['id'] ?? '');
        header("Location: index.php?order_id=" . $redirect_order_id_scan);
        exit;
    }
}

// Proses Complete Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $_SESSION['is_scan_action'] = false;
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token keamanan tidak valid!");
        }

        $pending_import_id_complete = (int)$_POST['pending_import_id'];

        $stmt_check_complete = $conn->prepare("SELECT COUNT(*) as mismatch 
                                                FROM pending_import_items 
                                                WHERE pending_import_id = ? 
                                                AND qty_ready != qty");
        $stmt_check_complete->bind_param("i", $pending_import_id_complete);
        $stmt_check_complete->execute();
        $mismatch_complete = $stmt_check_complete->get_result()->fetch_assoc()['mismatch'];

        if ($mismatch_complete > 0) {
            throw new Exception("Belum semua item diperiksa sepenuhnya!");
        }

        moveToMainTable($conn, $pending_import_id_complete, $_POST);
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Order berhasil diselesaikan dan dipindahkan ke riwayat!'
        ];
        $_SESSION['alert_timer'] = 2000;
        unset($_SESSION['order_ready_to_complete']);
        unset($_SESSION['form_data']);
        unset($_SESSION['order_details']);
        unset($_SESSION['order_initial_total']);
        unset($_SESSION['initial_items']);
        header("Location: index.php");
        exit;
    } catch (Exception $e_complete) {
        error_log("Order completion error: " . $e_complete->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e_complete->getMessage()];
        $_SESSION['alert_timer'] = 2000;
        $redirect_order_id_complete = $_POST['pending_import_id'] ?? ($_SESSION['order_details']['id'] ?? '');
        header("Location: index.php?order_id=" . $redirect_order_id_complete);
        exit;
    }
}

// Proses Save Customer and Address (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer_address'])) {
    header("Content-Type: application/json"); // Moved to the very top of the block
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token keamanan tidak valid!");
        }

        $pending_import_id_save = (int)($_POST['pending_import_id'] ?? 0);
        
        if ($pending_import_id_save > 0 && isset($_SESSION['order_details']['id']) && $pending_import_id_save == $_SESSION['order_details']['id']) {
    $_SESSION['form_data']['order_id'] = $pending_import_id_save; 
    $_SESSION['form_data']['nama_customer'] = mb_strtoupper($_POST['nama_customer']);
    $_SESSION['form_data']['sumber_layanan'] = mb_strtoupper($_POST['sumber_layanan']);
    $_SESSION['form_data']['layanan_pengiriman'] = mb_strtoupper($_POST['layanan_pengiriman']);
    $_SESSION['form_data']['alamat'] = mb_strtoupper($_POST['alamat']);
    $_SESSION['form_data']['telepon_penerima'] = sanitize($_POST['telepon_penerima']);
    echo json_encode(['success' => true, 'message' => 'Data disimpan sementara di sesi!']);
} else {
            throw new Exception("Order tidak aktif atau ID order tidak cocok untuk menyimpan data.");
        }
    } catch (Exception $e_save_addr) {
        error_log("Save customer address error: " . $e_save_addr->getMessage());
        http_response_code(500); // Added http_response_code
        echo json_encode(['success' => false, 'message' => "Error: " . $e_save_addr->getMessage()]);
    }
    exit;
}

// Proses Tambah Barang/Items (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    header("Content-Type: application/json"); // Moved to the very top of the block
    $_SESSION['is_scan_action'] = false;
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token keamanan tidak valid!");
        }

        $pending_import_id_add = (int)$_POST['pending_import_id'];
        $kode_barang_add = mb_strtoupper(sanitize($_POST['kode_barang']));
        $barcode_add = mb_strtoupper(sanitize($_POST['barcode']));
        $nama_barang_add = mb_strtoupper(sanitize($_POST['nama_barang']));
        $qty_add = (int)$_POST['qty'];
        $harga_jual_add = (float)$_POST['harga_jual']; // Use float for currency
        $diskon_item_add = (float)$_POST['diskon_item']; 
        $sub_total_add = ($qty_add * $harga_jual_add) - $diskon_item_add;

        if (empty($kode_barang_add) || empty($nama_barang_add) || $qty_add <= 0 || $harga_jual_add < 0) { // Harga boleh 0
            throw new Exception("Semua field wajib harus diisi dengan benar!");
        }
        
        $stmt_check_add = $conn->prepare("SELECT COUNT(*) as item_count FROM pending_import_items 
                                            WHERE pending_import_id = ? AND (kode_barang = ? OR (barcode != '' AND barcode = ? AND ? != ''))");
        $stmt_check_add->bind_param("isss", $pending_import_id_add, $kode_barang_add, $barcode_add, $barcode_add);
        $stmt_check_add->execute();
        $exists_add = $stmt_check_add->get_result()->fetch_assoc()['item_count'];

        if ($exists_add > 0) {
            throw new Exception("Item dengan kode atau barcode tersebut sudah ada!");
        }

        $stmt_get_no_nota_add = $conn->prepare("SELECT no_penjualan FROM pending_imports WHERE id = ?");
        $stmt_get_no_nota_add->bind_param("i", $pending_import_id_add);
        $stmt_get_no_nota_add->execute();
        $no_nota_row_add = $stmt_get_no_nota_add->get_result()->fetch_assoc();
        $no_nota_val_add = $no_nota_row_add['no_penjualan'] ?? '';

        $stmt_insert_add = $conn->prepare("INSERT INTO pending_import_items (
            pending_import_id, kode_barang, barcode, nama_barang, qty, qty_ready, harga_jual, diskon_item, sub_total, is_checked, no_nota
        ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, 0, ?)");
        
        // Correct types: i, s, s, s, i, d, d, d, s
        $stmt_insert_add->bind_param("isssiddsd", $pending_import_id_add, $kode_barang_add, $barcode_add, $nama_barang_add, $qty_add, $harga_jual_add, $diskon_item_add, $sub_total_add, $no_nota_val_add);
        
        if (!$stmt_insert_add->execute()) {
            throw new Exception("Gagal menambahkan item: " . $stmt_insert_add->error);
        }
        $new_item_id = $stmt_insert_add->insert_id;

        logActivity($conn, $_SESSION['user']['id'], 'add_item', "Tambah item ke pending order: {$nama_barang_add}", [
            'pending_import_id' => $pending_import_id_add,
            'item_id' => $new_item_id,
            'item_details' => $_POST
        ]);

        $_SESSION['order_ready_to_complete'] = false; 

        $stmt_total_qty_calc_add = $conn->prepare("SELECT SUM(qty) as total_qty FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_qty_calc_add->bind_param("i", $pending_import_id_add);
        $stmt_total_qty_calc_add->execute();
        $response_total_qty_add = $stmt_total_qty_calc_add->get_result()->fetch_assoc()['total_qty'] ?? 0;
        
        $stmt_total_qty_ready_calc_add = $conn->prepare("SELECT SUM(qty_ready) as total_qty_ready FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_qty_ready_calc_add->bind_param("i", $pending_import_id_add);
        $stmt_total_qty_ready_calc_add->execute();
        $response_total_qty_ready_add = $stmt_total_qty_ready_calc_add->get_result()->fetch_assoc()['total_qty_ready'] ?? 0;

        $stmt_items_total_calc_add = $conn->prepare("SELECT SUM(sub_total) as total_saat_ini FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_items_total_calc_add->bind_param("i", $pending_import_id_add);
        $stmt_items_total_calc_add->execute();
        $response_total_saat_ini_add = $stmt_items_total_calc_add->get_result()->fetch_assoc()['total_saat_ini'] ?? 0;
        
        $stmt_total_diskon_calc_add = $conn->prepare("SELECT SUM(diskon_item) as total_diskon FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_diskon_calc_add->bind_param("i", $pending_import_id_add);
        $stmt_total_diskon_calc_add->execute();
        $response_total_diskon_add = $stmt_total_diskon_calc_add->get_result()->fetch_assoc()['total_diskon'] ?? 0;


        echo json_encode([
            'success' => true,
            'message' => 'Item berhasil ditambahkan!',
            'order_id' => $pending_import_id_add,
            'total_saat_ini' => $response_total_saat_ini_add,
            'total_qty' => $response_total_qty_add,
            'total_qty_ready' => $response_total_qty_ready_add,
            'total_diskon_item' => $response_total_diskon_add
        ]);
    } catch (Exception $e_add) {
        error_log("Add item error: " . $e_add->getMessage());
        http_response_code(500); // Added http_response_code
        echo json_encode(['success' => false, 'message' => "Error: " . $e_add->getMessage()]);
    }
    exit;
}

// Proses Edit Item (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    header("Content-Type: application/json"); // Moved to the very top of the block
    $_SESSION['is_scan_action'] = false;
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token keamanan tidak valid!");
        }

        $item_id_edit = (int)$_POST['item_id'];
        $pending_import_id_edit = (int)$_POST['pending_import_id'];
        $kode_barang_edit = mb_strtoupper(sanitize($_POST['kode_barang']));
        $barcode_edit = mb_strtoupper(sanitize($_POST['barcode']));
        $nama_barang_edit = mb_strtoupper(sanitize($_POST['nama_barang']));
        $qty_edit = (int)$_POST['qty'];
        $harga_jual_edit = (float)$_POST['harga_jual']; // Use float for currency
        $diskon_item_edit = (float)$_POST['diskon_item']; 
        $sub_total_edit = ($qty_edit * $harga_jual_edit) - $diskon_item_edit; 

        if (empty($kode_barang_edit) || empty($nama_barang_edit) || $qty_edit <= 0 || $harga_jual_edit < 0) { // Harga boleh 0
            throw new Exception("Semua field wajib harus diisi dengan benar!");
        }

        $stmt_check_edit = $conn->prepare("SELECT COUNT(*) as item_count FROM pending_import_items 
                                            WHERE pending_import_id = ? AND (kode_barang = ? OR (barcode != '' AND barcode = ? AND ? != '')) AND id != ?");
        $stmt_check_edit->bind_param("isssi", $pending_import_id_edit, $kode_barang_edit, $barcode_edit, $barcode_edit, $item_id_edit);
        $stmt_check_edit->execute();
        $exists_edit = $stmt_check_edit->get_result()->fetch_assoc()['item_count'];

        if ($exists_edit > 0) {
            throw new Exception("Item dengan kode atau barcode tersebut sudah ada!");
        }

        $stmt_update_edit = $conn->prepare("UPDATE pending_import_items 
                                            SET kode_barang = ?, barcode = ?, nama_barang = ?, qty = ?, harga_jual = ?, diskon_item = ?, sub_total = ?, qty_ready = 0, is_checked = 0 
                                            WHERE id = ? AND pending_import_id = ?");
        $stmt_update_edit->bind_param("sssidddii", $kode_barang_edit, $barcode_edit, $nama_barang_edit, $qty_edit, $harga_jual_edit, $diskon_item_edit, $sub_total_edit, $item_id_edit, $pending_import_id_edit);
        if (!$stmt_update_edit->execute()) {
            throw new Exception("Gagal mengupdate item: " . $stmt_update_edit->error);
        }

        logActivity($conn, $_SESSION['user']['id'], 'edit_item', "Edit item pada pending order: {$nama_barang_edit}", [
            'pending_import_id' => $pending_import_id_edit,
            'item_id' => $item_id_edit,
            'item_details' => $_POST
        ]);
        
        $_SESSION['order_ready_to_complete'] = false; 

        $stmt_total_qty_calc_edit = $conn->prepare("SELECT SUM(qty) as total_qty FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_qty_calc_edit->bind_param("i", $pending_import_id_edit);
        $stmt_total_qty_calc_edit->execute();
        $response_total_qty_edit = $stmt_total_qty_calc_edit->get_result()->fetch_assoc()['total_qty'] ?? 0;

        $stmt_total_qty_ready_calc_edit = $conn->prepare("SELECT SUM(qty_ready) as total_qty_ready FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_qty_ready_calc_edit->bind_param("i", $pending_import_id_edit);
        $stmt_total_qty_ready_calc_edit->execute();
        $response_total_qty_ready_edit = $stmt_total_qty_ready_calc_edit->get_result()->fetch_assoc()['total_qty_ready'] ?? 0;

        $stmt_total_calc_edit = $conn->prepare("SELECT SUM(sub_total) as total_saat_ini FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_calc_edit->bind_param("i", $pending_import_id_edit);
        $stmt_total_calc_edit->execute();
        $response_total_saat_ini_edit = $stmt_total_calc_edit->get_result()->fetch_assoc()['total_saat_ini'] ?? 0;

        $stmt_total_diskon_calc_edit = $conn->prepare("SELECT SUM(diskon_item) as total_diskon FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_total_diskon_calc_edit->bind_param("i", $pending_import_id_edit);
        $stmt_total_diskon_calc_edit->execute();
        $response_total_diskon_edit = $stmt_total_diskon_calc_edit->get_result()->fetch_assoc()['total_diskon'] ?? 0;

        echo json_encode([
            'success' => true,
            'message' => 'Item berhasil diupdate!',
            'order_id' => $pending_import_id_edit,
            'total_saat_ini' => $response_total_saat_ini_edit,
            'total_qty' => $response_total_qty_edit,
            'total_qty_ready' => $response_total_qty_ready_edit,
            'total_diskon_item' => $response_total_diskon_edit
        ]);
    } catch (Exception $e_edit) {
        error_log("Edit item error: " . $e_edit->getMessage());
        http_response_code(500); // Added http_response_code
        echo json_encode(['success' => false, 'message' => "Error: " . $e_edit->getMessage()]);
    }
    exit;
}

// Proses Reset Items (Batal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_items'])) {
    $_SESSION['is_scan_action'] = false;
    $conn->begin_transaction(); 
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token keamanan tidak valid!");
        }
        $pending_import_id_reset = (int)$_POST['pending_import_id'];

        $stmt_delete_reset = $conn->prepare("DELETE FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_delete_reset->bind_param("i", $pending_import_id_reset);
        $stmt_delete_reset->execute();

        if (isset($_SESSION['initial_items']) && !empty($_SESSION['initial_items'])) {
            $stmt_insert_reset = $conn->prepare("INSERT INTO pending_import_items (
                pending_import_id, no_nota, kode_barang, barcode, nama_barang, qty, harga_jual, diskon_item, sub_total, qty_ready, is_checked
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");
            
            foreach ($_SESSION['initial_items'] as $item_reset) {
                $no_nota_reset = $item_reset['no_nota'] ?? '';
                $kode_barang_reset = $item_reset['kode_barang'] ?? '';
                $barcode_reset = $item_reset['barcode'] ?? ''; 
                $nama_barang_reset = $item_reset['nama_barang'] ?? '';
                $qty_reset_val = isset($item_reset['qty']) ? (int)$item_reset['qty'] : 0;
                $harga_jual_reset_val = isset($item_reset['harga_jual']) ? (float)$item_reset['harga_jual'] : 0.0;
                $diskon_item_reset_val = isset($item_reset['diskon_item']) ? (float)$item_reset['diskon_item'] : 0.0;
                $sub_total_reset_val = ($qty_reset_val * $harga_jual_reset_val) - $diskon_item_reset_val; 
                
                $stmt_insert_reset->bind_param("issssiddd", 
                    $pending_import_id_reset, $no_nota_reset, $kode_barang_reset, $barcode_reset,
                    $nama_barang_reset, $qty_reset_val, $harga_jual_reset_val, $diskon_item_reset_val, $sub_total_reset_val
                );
                if (!$stmt_insert_reset->execute()) {
                    throw new Exception("Failed to insert item (reset): " . $stmt_insert_reset->error . " | SQL: " . $stmt_insert_reset->sqlstate);
                }
            }
        }
        
        // Ambil no_penjualan untuk log yang lebih informatif
        $stmt_get_no_nota = $conn->prepare("SELECT no_penjualan FROM pending_imports WHERE id = ?");
        $stmt_get_no_nota->bind_param("i", $pending_import_id_reset);
        $stmt_get_no_nota->execute();
        $no_penjualan_log = $stmt_get_no_nota->get_result()->fetch_assoc()['no_penjualan'] ?? "ID: {$pending_import_id_reset}";
        $stmt_get_no_nota->close();

        logActivity($conn, $_SESSION['user']['id'], 'reset_items', "Reset item pada order: {$no_penjualan_log}", [
            'pending_import_id' => $pending_import_id_reset,
            'no_penjualan' => $no_penjualan_log
        ]);
        
        $conn->commit(); 
        $_SESSION['order_ready_to_complete'] = false;
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Daftar item telah direset ke kondisi awal!'];
        $_SESSION['alert_timer'] = 2000;
        header("Location: index.php?order_id=" . $pending_import_id_reset);
        exit;
    } catch (Exception $e_reset) {
        if ($conn->in_transaction) { 
            $conn->rollback(); 
        }
        error_log("Reset items error: " . $e_reset->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e_reset->getMessage()];
        $_SESSION['alert_timer'] = 2000;
        $redirect_order_id_reset = $_POST['pending_import_id'] ?? ($_SESSION['order_details']['id'] ?? '');
        header("Location: index.php?order_id=" . $redirect_order_id_reset);
        exit;
    }
}

// Fungsi untuk memindahkan ke tabel utama
function moveToMainTable($conn, $pending_import_id_move, $post_data_move) {
    $conn->begin_transaction();
    try {
        $stmt_order_move = $conn->prepare("SELECT * FROM pending_imports WHERE id = ?");
        $stmt_order_move->bind_param("i", $pending_import_id_move);
        $stmt_order_move->execute();
        $order_move = $stmt_order_move->get_result()->fetch_assoc();

        if (!$order_move) {
            throw new Exception("Pending order data not found for move.");
        }

        $stmt_calc_totals_move = $conn->prepare("SELECT SUM(sub_total) as total_belanja_final, 
                                                    SUM(qty) as total_qty_final,
                                                    SUM(diskon_item) as total_diskon_final
                                                    FROM pending_import_items 
                                                    WHERE pending_import_id = ?");
        $stmt_calc_totals_move->bind_param("i", $pending_import_id_move);
        $stmt_calc_totals_move->execute();
        $final_totals_move = $stmt_calc_totals_move->get_result()->fetch_assoc();

        $total_belanja_final_move = $final_totals_move['total_belanja_final'] ?? 0;
        $total_qty_final_move = $final_totals_move['total_qty_final'] ?? 0;

        // In the moveToMainTable function (around line 940):
$stmt_insert_move = $conn->prepare("INSERT INTO online_wa (
    nama_customer, no_penjualan, sumber_layanan, layanan_pengiriman,
    alamat, telepon_customer, total_belanja, qty, checker, tanggal, status_checked, source 
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'completed', 'checker')");

// ## FATAL ERROR FIX: Assign the result of the null coalescing operator to a variable first ##
$telepon_customer_move = $post_data_move['telepon_penerima'] ?? '';

$stmt_insert_move->bind_param("ssssssdis",
    $post_data_move['nama_customer'],
    $order_move['no_penjualan'],
    $post_data_move['sumber_layanan'],
    $post_data_move['layanan_pengiriman'],
    $post_data_move['alamat'],
    $telepon_customer_move, // Use the new variable here
    $total_belanja_final_move, 
    $total_qty_final_move,
    $_SESSION['user']['username']
);
        $stmt_insert_move->execute();
        $new_order_id_move = $conn->insert_id;
        
        // Log Aktivitas Complete Order
        logActivity($conn, $_SESSION['user']['id'], 'complete_order', "Menyelesaikan order checker: {$order_move['no_penjualan']}", [
            'pending_import_id' => $pending_import_id_move,
            'new_order_id' => $new_order_id_move,
            'no_penjualan' => $order_move['no_penjualan'],
            'customer_data' => $post_data_move
        ]);

        $stmt_move_items_to_order = $conn->prepare("INSERT INTO order_items (
            order_id, no_nota, kode_barang, barcode, nama_barang, 
            qty, qty_ready, harga_jual, diskon_item, sub_total, is_checked
        ) SELECT ?, no_nota, kode_barang, barcode, nama_barang, 
            qty, qty_ready, harga_jual, diskon_item, sub_total, is_checked 
            FROM pending_import_items 
            WHERE pending_import_id = ?");
        $stmt_move_items_to_order->bind_param("ii", $new_order_id_move, $pending_import_id_move);
        $stmt_move_items_to_order->execute();

        $stmt_del_pending_items = $conn->prepare("DELETE FROM pending_import_items WHERE pending_import_id = ?");
        $stmt_del_pending_items->bind_param("i", $pending_import_id_move);
        $stmt_del_pending_items->execute();

        $stmt_del_pending_import = $conn->prepare("DELETE FROM pending_imports WHERE id = ?");
        $stmt_del_pending_import->bind_param("i", $pending_import_id_move);
        $stmt_del_pending_import->execute();
        
        $conn->commit();
    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        throw $e; 
    }
}

// Logika untuk tabel transaksi (Displayed when no order is active)
$current_page_trans = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page_trans = 30;
$offset_trans = ($current_page_trans - 1) * $per_page_trans;

// Ambil parameter filter dari URL
$search_trans = isset($_GET['search']) ? mb_strtoupper(sanitize($_GET['search'])) : '';
$date_range_trans = isset($_GET['date_range']) ? sanitize($_GET['date_range']) : '';

$sort_trans = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'tanggal';
$order_trans = isset($_GET['order']) ? strtoupper(sanitize($_GET['order'])) : 'DESC';
$valid_sort_columns_trans = ['nama_customer', 'no_penjualan', 'sumber_layanan', 'layanan_pengiriman', 'qty', 'total_belanja', 'status_checked', 'tanggal'];
$sort_trans = in_array($sort_trans, $valid_sort_columns_trans) ? $sort_trans : 'tanggal';
$order_trans = in_array($order_trans, ['ASC', 'DESC']) ? $order_trans : 'DESC';

// Bangun query WHERE berdasarkan filter
$whereClause_trans = " WHERE 1=1 "; // Mulai dengan kondisi yang selalu benar
$params_trans = [];
$types_trans = '';

// ## MODIFIED: Added telepon_customer to search ##
if (!empty($search_trans)) {
    $whereClause_trans .= " AND (nama_customer LIKE ? OR no_penjualan LIKE ? OR layanan_pengiriman LIKE ? OR alamat LIKE ? OR sumber_layanan LIKE ? OR telepon_customer LIKE ?) ";
    $searchTerm_trans = "%$search_trans%";
    array_push($params_trans, $searchTerm_trans, $searchTerm_trans, $searchTerm_trans, $searchTerm_trans, $searchTerm_trans, $searchTerm_trans);
    $types_trans .= 'ssssss';
}

if (!empty($date_range_trans)) {
    $dates = explode(' to ', $date_range_trans);
    if (count($dates) === 2) {
        $start_date = $dates[0] . ' 00:00:00';
        $end_date = $dates[1] . ' 23:59:59';
        $whereClause_trans .= " AND tanggal BETWEEN ? AND ? ";
        array_push($params_trans, $start_date, $end_date);
        $types_trans .= 'ss';
    }
}
$countSql_trans = "SELECT COUNT(*) as total FROM online_wa $whereClause_trans";
$countStmt_trans = $conn->prepare($countSql_trans);
if ($countStmt_trans && !empty($types_trans)) {
    $countStmt_trans->bind_param($types_trans, ...$params_trans);
}
if ($countStmt_trans) {
    $countStmt_trans->execute();
}
$total_records_trans = $countStmt_trans ? ($countStmt_trans->get_result()->fetch_assoc()['total'] ?? 0) : 0;
$total_pages_trans = max(1, ceil($total_records_trans / $per_page_trans));
$sql_trans = "SELECT id, nama_customer, no_penjualan, sumber_layanan, layanan_pengiriman, 
                alamat, telepon_customer, qty, total_belanja, status_checked, tanggal, checker, source 
                FROM online_wa $whereClause_trans ORDER BY $sort_trans $order_trans LIMIT ?, ?";
$stmt_trans = $conn->prepare($sql_trans);

$final_params_trans = [];
$final_types_trans = '';

if (!empty($types_trans)) {
    $final_params_trans = array_merge($params_trans, [$offset_trans, $per_page_trans]);
    $final_types_trans = $types_trans . 'ii';
} else {
    $final_params_trans = [$offset_trans, $per_page_trans];
    $final_types_trans = 'ii';
}

if ($stmt_trans) {
    $stmt_trans->bind_param($final_types_trans, ...$final_params_trans);
    $stmt_trans->execute();
}
$result_transactions = $stmt_trans ? $stmt_trans->get_result() : false;


// Proses penghapusan Transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $_SESSION['is_scan_action'] = false;
    $conn->begin_transaction();
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token!");
        }
        requireAdmin();
        $id_to_delete_trans = (int)$_POST['id'];

        // ## PERBAIKAN: Ambil no_penjualan SEBELUM dihapus untuk logging ##
        $stmt_get_no_penjualan = $conn->prepare("SELECT no_penjualan FROM online_wa WHERE id = ?");
        $stmt_get_no_penjualan->bind_param("i", $id_to_delete_trans);
        $stmt_get_no_penjualan->execute();
        $result_no_penjualan = $stmt_get_no_penjualan->get_result();
        $order_to_delete = $result_no_penjualan->fetch_assoc();
        $no_penjualan_log = $order_to_delete['no_penjualan'] ?? "ID: $id_to_delete_trans"; // Fallback jika tidak ketemu
        $stmt_get_no_penjualan->close();

        $stmt_delete_items_trans = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_delete_items_trans->bind_param("i", $id_to_delete_trans);
        $stmt_delete_items_trans->execute();
        $stmt_delete_order_trans = $conn->prepare("DELETE FROM online_wa WHERE id = ?");
        $stmt_delete_order_trans->bind_param("i", $id_to_delete_trans);
        $stmt_delete_order_trans->execute();
        if ($stmt_delete_order_trans->affected_rows === 0) {
            error_log("Warning: No data deleted from online_wa for ID $id_to_delete_trans, but items might have been.");
        }
        // ## PERBAIKAN: Gunakan no_penjualan dalam deskripsi log ##
        logActivity($conn, $_SESSION['user']['id'], 'delete_transaction', "Menghapus transaksi No.Penjualan: $no_penjualan_log", ['transaction_id' => $id_to_delete_trans, 'no_penjualan' => $no_penjualan_log]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Data berhasil dihapus!'];
        $_SESSION['alert_timer'] = 2000;
        $conn->commit();
    } catch (Exception $e_delete_trans) {
        if ($conn->in_transaction) { 
            $conn->rollback();
        }
        error_log("Delete transaction error: " . $e_delete_trans->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => "Failed to delete: " . $e_delete_trans->getMessage()];
        $_SESSION['alert_timer'] = 2000;
    }
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checker WA Order</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- LINK CSS FLATPCIKR BARU -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container main-content custom-container">
        <?php showAlert(); ?>

        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-check-circle me-2"></i>Proses Checker</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="no_penjualan_search" 
                                    name="no_penjualan" required
                                    placeholder="JO-2505160001-S atau 0001"
                                    pattern="[A-Za-z0-9-]{4,}"
                                    autocomplete="off"
                                    value="<?= htmlspecialchars($_POST['no_penjualan'] ?? '') ?>">
                                <label for="no_penjualan_search">
                                    <i class="fas fa-hashtag me-2"></i>
                                    No.Penjualan (contoh: JO-2505160001-S atau 0001)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="submit" name="search_order" class="btn btn-primary flex-grow-1 py-3">
                                    <i class="fas fa-search me-2"></i>Cari
                                </button>
                                <a href="order_list.php" class="btn btn-info flex-grow-1 py-3 text-white">
                                    <i class="fas fa-list-check"></i>Pending
                                </a>
                                <a href="manual_order.php" class="btn btn-success flex-grow-1 py-3">
                                    <i class="fas fa-pen-to-square me-2"></i>Manual
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($order_data)): ?>
                    <form method="POST" id="scanForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="pending_import_id" value="<?= $order_data['id'] ?>">
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" 
                                        value="<?= htmlspecialchars($order_data['no_penjualan']) ?>" readonly>
                                    <label>No. Penjualan</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" 
                                        value="<?= htmlspecialchars($order_data['checker']) ?>" readonly>
                                    <label>Checker</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating position-relative">
                                    <input type="text" class="form-control" id="nama_customer" 
                                        name="nama_customer" required
                                        value="<?= htmlspecialchars($_SESSION['form_data']['nama_customer'] ?? $order_data['nama_customer'] ?? '') ?>">
                                    <label for="nama_customer">Nama Customer</label>
                                    <div class="position-absolute top-0 end-0 mt-2 me-2">
                                        <button type="button" class="btn btn-sm btn-success me-1" title="Save" onclick="saveCustomerAddress(event, this.form)">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-floating position-relative">
                                    <input type="text" class="form-control" id="telepon_penerima" 
    name="telepon_penerima" required
    inputmode="numeric" pattern="[0-9]*"
    value="<?= htmlspecialchars($_SESSION['form_data']['telepon_penerima'] ?? '') ?>">
                                    <label for="telepon_penerima">Nomor Telepon Customer</label>
                                    <div class="position-absolute top-0 end-0 mt-2 me-2">
                                        <button type="button" class="btn btn-sm btn-success me-1" title="Save" onclick="saveCustomerAddress(event, this.form)">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="sumber_layanan" name="sumber_layanan" required>
                                        <?php $sumber_val = $_SESSION['form_data']['sumber_layanan'] ?? $order_data['sumber_layanan'] ?? 'WA1'; ?>
                                        <option value="WA1" <?= $sumber_val === 'WA1' ? 'selected' : '' ?>>WA1</option>
                                        <option value="WA2" <?= $sumber_val === 'WA2' ? 'selected' : '' ?>>WA2</option>
                                        <option value="SHOPIFY" <?= $sumber_val === 'SHOPIFY' ? 'selected' : '' ?>>Shopify</option>
                                        <option value="BELANJA DI TOKO" <?= $sumber_val === 'BELANJA DI TOKO' ? 'selected' : '' ?>>Belanja di Toko</option>
                                    </select>
                                    <label for="sumber_layanan">Sumber Layanan</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="layanan_pengiriman" name="layanan_pengiriman" required>
                                        <?php
                                        $current_layanan = $_SESSION['form_data']['layanan_pengiriman'] ?? $order_data['layanan_pengiriman'] ?? 'GO SEND INSTANT/SAMEDAY';
                                        ?>
                                        <?php
                                        $layanan_list_options = $conn->query("SELECT nama_layanan FROM layanan_pengiriman ORDER BY nama_layanan ASC");
                                        if ($layanan_list_options) { 
                                            while ($row_layanan = $layanan_list_options->fetch_assoc()):
                                            ?>
                                            <option value="<?= htmlspecialchars($row_layanan['nama_layanan']) ?>" <?= $current_layanan === $row_layanan['nama_layanan'] ? 'selected' : '' ?>><?= htmlspecialchars($row_layanan['nama_layanan']) ?></option>
                                            <?php endwhile; 
                                        }?>
                                    </select>
                                    <label for="layanan_pengiriman">Layanan Pengiriman</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating position-relative">
                                    <textarea class="form-control" id="alamat" name="alamat" style="height: 100px;"><?= htmlspecialchars($_SESSION['form_data']['alamat'] ?? $order_data['alamat'] ?? '') ?></textarea>
                                    <label for="alamat">Alamat Pengiriman</label>
                                     <div class="position-absolute top-0 end-0 mt-2 me-2">
                                        <button type="button" class="btn btn-sm btn-success me-1" title="Save" onclick="saveCustomerAddress(event, this.form)">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="barcode" 
                                        name="barcode" required autofocus autocomplete="off"
                                        value="">
                                    <label for="barcode">Scan Barcode/Kode Barang</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="qty_scanned" 
                                        name="qty_scanned" min="1" required value="1">
                                    <label for="qty_scanned">Qty</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="scan_barcode" 
                                    class="btn btn-success w-100 py-3 h-100">
                                    <i class="fas fa-barcode me-2"></i>Konfirmasi
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>No</th><th>SKU</th><th>Barcode</th><th>Nama Barang</th><th>Qty</th><th>Qty Ready</th><th>Harga</th><th>Diskon Item</th><th>Subtotal</th><th class="text-center">Status</th><th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $i_item => $item_data): ?>
                                    <tr class="<?= $item_data['is_checked'] ? 'table-success' : ($item_data['qty_ready'] > 0 ? 'table-warning' : 'table-light') ?>">
                                        <td><?= $i_item + 1 ?></td>
                                        <td><?= htmlspecialchars($item_data['kode_barang']) ?></td>
                                        <td><?= htmlspecialchars($item_data['barcode']) ?></td>
                                        <td><?= htmlspecialchars($item_data['nama_barang']) ?></td>
                                        <td class="text-center"><?= $item_data['qty'] ?></td>
                                        <td class="text-center"><?= $item_data['qty_ready'] ?></td>
                                        <td><?= number_format($item_data['harga_jual'], 0, ',', '.') ?></td>
                                        <td class="text-center"><?= number_format($item_data['diskon_item'], 0, ',', '.') ?></td>
                                        <td><?= number_format($item_data['sub_total'], 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <?php if ($item_data['is_checked']): ?><i class="fas fa-check-circle text-success fs-4"></i>
                                            <?php elseif ($item_data['qty_ready'] > 0): ?><i class="fas fa-hourglass-half text-warning fs-4"></i>
                                            <?php else: ?><i class="fas fa-times-circle text-danger fs-4"></i><?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning edit-item" 
                                                    data-id="<?= $item_data['id'] ?>" 
                                                    data-kode="<?= htmlspecialchars($item_data['kode_barang']) ?>" 
                                                    data-barcode="<?= htmlspecialchars($item_data['barcode']) ?>" 
                                                    data-nama="<?= htmlspecialchars($item_data['nama_barang']) ?>" 
                                                    data-qty="<?= $item_data['qty'] ?>" 
                                                    data-harga="<?= $item_data['harga_jual'] ?>"
                                                    data-diskon="<?= $item_data['diskon_item'] ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editItemModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (!$item_data['is_checked']): ?>
                                                <button class="btn btn-sm btn-danger delete-item" data-id="<?= $item_data['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="11" class="text-center">Belum ada item.</td></tr>
                                <?php endif; ?>
                                
                                <tr class="table-info">
                                    <td colspan="4" class="text-end fw-bold">Total Qty / Ready / Diskon</td>
                                    <td class="text-center fw-bold"><?= number_format($total_qty, 0, ',', '.') ?></td>
                                    <td class="text-center fw-bold"><?= number_format($total_qty_ready, 0, ',', '.') ?></td>
                                    <td></td>
                                    <td class="text-center fw-bold"><?= number_format($total_diskon_item, 0, ',', '.') ?></td>
                                    <td colspan="3"></td>
                                </tr>
                                
                                <tr class="table-info">
                                    <td colspan="8" class="text-end fw-bold">Total Saat Ini</td>
                                    <td class="fw-bold"><?= number_format($total_subtotal, 0, ',', '.') ?></td>
                                    <td colspan="2"></td>
                                </tr>
                                <?php
                                $total_awal_display = $_SESSION['order_initial_total'] ?? 0;
                                $selisih_display = $total_awal_display - $total_subtotal;
                                ?>
                                <tr class="table-warning"><td colspan="8" class="text-end fw-bold">Selisih</td><td class="fw-bold"><?= number_format($selisih_display, 0, ',', '.') ?></td><td colspan="2"></td></tr>
                                <tr class="table-primary"><td colspan="8" class="text-end fw-bold">Total Awal</td><td class="fw-bold"><?= number_format($total_awal_display, 0, ',', '.') ?></td><td colspan="2"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <form method="POST" onsubmit="return confirmResetItems(event)">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="pending_import_id" value="<?= $order_data['id'] ?>">
                                <input type="hidden" name="reset_items" value="1">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-undo me-2"></i>Reset Items</button>
                            </form>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="fas fa-plus me-2"></i>Tambah Items</button>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['order_ready_to_complete']) && $_SESSION['order_ready_to_complete']): ?>
                    <div class="d-flex justify-content-end mt-3">
                        <form method="POST" onsubmit="return confirmCheckerComplete(event)">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="pending_import_id" value="<?= $order_data['id'] ?>">
    <input type="hidden" name="complete_order" value="1">
    <input type="hidden" name="nama_customer" value="<?= htmlspecialchars($_SESSION['form_data']['nama_customer'] ?? '') ?>">
    <input type="hidden" name="sumber_layanan" value="<?= htmlspecialchars($_SESSION['form_data']['sumber_layanan'] ?? '') ?>">
    <input type="hidden" name="layanan_pengiriman" value="<?= htmlspecialchars($_SESSION['form_data']['layanan_pengiriman'] ?? '') ?>">
    <input type="hidden" name="alamat" value="<?= htmlspecialchars($_SESSION['form_data']['alamat'] ?? '') ?>">
    <input type="hidden" name="telepon_penerima" value="<?= htmlspecialchars($_SESSION['form_data']['telepon_penerima'] ?? '') ?>">
    <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i>Selesaikan Order</button>
</form>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title" id="addItemModalLabel">Tambah Barang/Items</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <form id="addItemForm" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="pending_import_id" value="<?= $order_data['id'] ?? '' ?>">
                            <input type="hidden" name="add_item" value="1">
                            <div class="mb-3"><label for="add_kode_barang" class="form-label">Kode Barang</label><input type="text" class="form-control" id="add_kode_barang" name="kode_barang" required></div>
                            <div class="mb-3"><label for="add_barcode" class="form-label">Barcode (Opsional)</label><input type="text" class="form-control" id="add_barcode" name="barcode"></div>
                            <div class="mb-3"><label for="add_nama_barang" class="form-label">Nama Barang</label><input type="text" class="form-control" id="add_nama_barang" name="nama_barang" required></div>
                            <div class="mb-3"><label for="add_qty" class="form-label">Qty</label><input type="number" class="form-control" id="add_qty" name="qty" min="1" required value="1"></div>
                            <div class="mb-3">
    <label for="add_harga_jual" class="form-label">Harga Jual</label>
    <input type="number" min="0" class="form-control" id="add_harga_jual" 
           name="harga_jual" required placeholder="Contoh: 31500">
</div>
                            <div class="mb-3">
    <label for="add_diskon_item" class="form-label">Diskon Item (Rp)</label>
    <input type="number" min="0" class="form-control" id="add_diskon_item" 
           name="diskon_item" value="0" placeholder="Contoh: 5000">
</div>
                            <button type="submit" class="btn btn-primary">Tambah</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title" id="editItemModalLabel">Edit Barang/Items</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <form id="editItemForm" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="pending_import_id" value="<?= $order_data['id'] ?? '' ?>">
                            <input type="hidden" name="edit_item" value="1">
                            <input type="hidden" name="item_id" id="edit_item_id">
                            <div class="mb-3"><label for="edit_kode_barang" class="form-label">Kode Barang</label><input type="text" class="form-control" id="edit_kode_barang" name="kode_barang" required></div>
                            <div class="mb-3"><label for="edit_barcode" class="form-label">Barcode (Opsional)</label><input type="text" class="form-control" id="edit_barcode" name="barcode"></div>
                            <div class="mb-3"><label for="edit_nama_barang" class="form-label">Nama Barang</label><input type="text" class="form-control" id="edit_nama_barang" name="nama_barang" required></div>
                            <div class="mb-3"><label for="edit_qty" class="form-label">Qty</label><input type="number" class="form-control" id="edit_qty" name="qty" min="1" required></div>
                            <div class="mb-3">
    <label for="edit_harga_jual" class="form-label">Harga Jual</label>
    <input type="number" min="0" class="form-control" id="edit_harga_jual" 
           name="harga_jual" required placeholder="Contoh: 31500">
</div>
                            <div class="mb-3">
    <label for="edit_diskon_item" class="form-label">Diskon Item (Rp)</label>
    <input type="number" min="0" class="form-control" id="edit_diskon_item" 
           name="diskon_item" value="0" placeholder="Contoh: 5000">
</div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($order_data)): ?>
        <div class="dashboard-card mt-4">
            <div class="card-header"><h5 class="mb-0 fw-semibold"><i class="fas fa-list me-2"></i>Riwayat Transaksi</h5></div>
            <div class="card-body p-0">
                <!-- FORM FILTER DAN EXPORT -->
<div class="p-3 border-bottom">
    <form method="GET" action="index.php" id="filterForm" class="row g-3 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="col-md-5">
            <label for="search" class="form-label">Cari Transaksi</label>
            <input type="text" name="search" id="search" class="form-control" placeholder="Cari berdasarkan customer, no penjualan, dll..." value="<?= htmlspecialchars($search_trans) ?>">
        </div>
        <div class="col-md-5">
            <label for="date_range" class="form-label">Rentang Tanggal</label>
            <input type="text" id="date_range" name="date_range" class="form-control" placeholder="Pilih rentang tanggal" value="<?= htmlspecialchars($date_range_trans) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Filter</button>
            <button type="button" class="btn btn-success w-100" onclick="exportData()"><i class="fas fa-file-excel me-2"></i>Export</button>
        </div>
    </form>
</div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover mb-0 data-table">
                        <thead class="bg-primary text-white">
    <tr>
        <th class="text-center">No</th>
        <th class="text-center">Customer</th>
        <th class="text-center">No Penjualan</th>
        <th class="text-center">Sumber Layanan</th>
        <th class="text-center">Pengiriman</th>
        <th>Alamat</th>
        <th class="text-center">Telepon</th>
        <th class="text-center">Qty</th>
        <th class="text-center">Total</th>
        <th class="text-center">Check Status</th>
        <th class="text-center">Tanggal</th>
        <th class="text-center">Checker</th>
        <th class="text-center">Aksi</th>
    </tr>
</thead>
                        <tbody>
    <?php if ($result_transactions && $result_transactions->num_rows > 0): ?>
        <?php $no_trans = $offset_trans + 1; ?>
        <?php while ($row_trans = $result_transactions->fetch_assoc()): ?>
        <tr>
            <td class="text-center"><?= $no_trans++ ?></td>
            <td><?= htmlspecialchars($row_trans['nama_customer']) ?></td>
            <td><?= htmlspecialchars($row_trans['no_penjualan']) ?></td>
            <td class="text-center">
                <span class="badge <?= match(strtoupper($row_trans['sumber_layanan'] ?? '')) {
                    'WA1' => 'bg-success', 'WA2' => 'bg-info', 'SHOPIFY' => 'bg-warning', 'BELANJA DI TOKO' => 'bg-secondary', default => 'bg-primary'
                } ?>">
                    <?= htmlspecialchars($row_trans['sumber_layanan']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($row_trans['layanan_pengiriman']) ?></td>
            <td class="address-cell" onclick="showAddressModal('<?= htmlspecialchars(addslashes($row_trans['alamat'])) ?>')"><?= truncateText($row_trans['alamat'], 20) ?></td>
            <td class="text-center"><?= htmlspecialchars($row_trans['telepon_customer'] ?? '') ?></td>
            <td class="text-center"><?= number_format($row_trans['qty']) ?></td>
            <td class="text-center fw-bold"><?= formatCurrency($row_trans['total_belanja']) ?></td>
            <td class="text-center">
                <span class="badge <?= match($row_trans['status_checked']) {
                    'completed' => 'bg-success', 'partial' => 'bg-warning', default => 'bg-secondary'
                } ?>">
                    <?= match($row_trans['status_checked']) {
                        'completed' => '✓ Completed', 'partial' => '! Partial', default => 'Pending'
                    } ?>
                </span>
            </td>
            <td class="text-center"><?= date('d/m/y H:i', strtotime($row_trans['tanggal'])) ?></td>
            <td class="text-center"><?= htmlspecialchars($row_trans['checker']) ?></td>
            <td class="text-center">
    <div class="d-flex justify-content-center gap-2 aksi-buttons">
        <?php if ($row_trans['source'] === 'checker'): ?>
            <a href="edit_checker_order.php?edit=<?= $row_trans['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirmEdit(event, 'edit_checker_order.php?edit=<?= $row_trans['id'] ?>')"><i class="fas fa-edit"></i></a>
        <?php else: ?>
            <a href="manual_order.php?edit=<?= $row_trans['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirmEdit(event, 'manual_order.php?edit=<?= $row_trans['id'] ?>')"><i class="fas fa-edit"></i></a>
        <?php endif; ?>
        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
        <form method="POST" class="delete-form d-inline">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="delete" value="1"><input type="hidden" name="id" value="<?= $row_trans['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger delete-btn"><i class="fas fa-trash"></i></button>
        </form>
        <?php endif; ?>
        <button class="btn btn-sm btn-info" onclick="showItems(<?= $row_trans['id'] ?>)" title="View Items">
            <i class="fas fa-list"></i>
        </button>
        <a href="export_pdf.php?id=<?= $row_trans['id'] ?>" class="btn btn-sm btn-danger" title="Export PDF" target="_blank">
            <i class="fas fa-file-pdf"></i>
        </a>
    </div>
</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="13" class="text-center py-4 text-muted">Data Tidak Ditemukan</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages_trans > 1): ?>
                <div class="p-3 border-top">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0 pagination-sm">
                            <li class="page-item <?= $current_page_trans <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $current_page_trans - 1 ?>&search=<?= urlencode($search_trans) ?>&sort=<?= $sort_trans ?>&order=<?= $order_trans ?>#data-transaksi"><i class="fas fa-chevron-left"></i></a></li>
                            <?php
                            $start_page_trans = max(1, min($current_page_trans - 2, $total_pages_trans - 4));
                            $end_page_trans = min($total_pages_trans, $start_page_trans + 4);
                            for ($i_page_trans = $start_page_trans; $i_page_trans <= $end_page_trans; $i_page_trans++): ?>
                                <li class="page-item <?= $i_page_trans == $current_page_trans ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i_page_trans ?>&search=<?= urlencode($search_trans) ?>&sort=<?= $sort_trans ?>&order=<?= $order_trans ?>#data-transaksi"><?= $i_page_trans ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $current_page_trans >= $total_pages_trans ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $current_page_trans + 1 ?>&search=<?= urlencode($search_trans) ?>&sort=<?= $sort_trans ?>&order=<?= $order_trans ?>#data-transaksi"><i class="fas fa-chevron-right"></i></a></li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addressModalLabel">📌 Alamat Lengkap</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body"><pre id="fullAddressContent" class="mb-0"></pre></div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" onclick="copyAddress()"><i class="fas fa-copy me-2"></i>Salin Alamat</button></div>
            </div>
        </div>
    </div>
        <div class="modal fade" id="itemsModal" tabindex="-1" aria-labelledby="itemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemsModalLabel">📦 Detail Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    <div id="loadingIndicator" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <table id="itemsTable" class="table table-striped table-bordered"> 
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nama Barang</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Diskon Item</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="itemsList"></tbody>
                        <tfoot id="itemsFooter"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="main.js"></script>
<script>
const initialTotalGlobal = <?php echo json_encode($_SESSION['order_initial_total'] ?? 0); ?>;
const csrfTokenGlobal = '<?= $_SESSION['csrf_token'] ?>';
const wasScanAction = <?php echo json_encode($_SESSION['is_scan_action'] ?? false); ?>;
<?php
if (isset($_SESSION['is_scan_action'])) unset($_SESSION['is_scan_action']);
$alertTimerJs = $_SESSION['alert_timer'] ?? 1500; 
?>
const phpAlertTimer = <?php echo (int)$alertTimerJs; ?>; 


function saveCustomerAddress(event, form) {
    event.preventDefault();
    const formData = new FormData(form);
    formData.append('save_customer_address', '1');
    const pendingImportIdFromForm = formData.get('pending_import_id');

    if (!pendingImportIdFromForm) {
        showNotification('danger', 'Error: ID Order tidak ditemukan untuk menyimpan data customer.');
        return;
    }

    fetch(window.location.pathname, { 
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
        if (data.success) {
            showNotification('success', data.message);
        } else {
            showNotification('danger', data.message || 'Gagal menyimpan data customer.');
        }
    })
    .catch(error => showNotification('danger', 'Error: ' + error.message));
}

function updateItemsTable(items, totalAwal, totalSaatIni, totalQty, totalQtyReady, totalDiskonItem) {
    const tbody = document.querySelector('.table-responsive table tbody');
    if (!tbody) {
        console.warn("Items table body not found for update.");
        return;
    }

    tbody.querySelectorAll('tr:not(.table-info):not(.table-warning):not(.table-primary)').forEach(row => row.remove());
    tbody.querySelectorAll('tr.table-info, tr.table-warning, tr.table-primary').forEach(row => row.remove());

    items.forEach((item, index) => {
        const rowClass = item.is_checked ? 'table-success' : (item.qty_ready > 0 ? 'table-warning' : 'table-light');
        const itemHargaJualFormatted = new Intl.NumberFormat('id-ID').format(parseFloat(item.harga_jual || 0));
        const itemDiskonFormatted = new Intl.NumberFormat('id-ID').format(parseFloat(item.diskon_item || 0));
        const itemSubTotalFormatted = new Intl.NumberFormat('id-ID').format(parseFloat(item.sub_total || 0));

        const rowHTML = `
            <tr class="${rowClass}">
                <td>${index + 1}</td>
                <td>${item.kode_barang || ''}</td>
                <td>${item.barcode || ''}</td>
                <td>${item.nama_barang || ''}</td>
                <td class="text-center">${item.qty || 0}</td>
                <td class="text-center">${item.qty_ready || 0}</td>
                <td>${itemHargaJualFormatted}</td>
                <td class="text-center">${itemDiskonFormatted}</td>
                <td>${itemSubTotalFormatted}</td>
                <td class="text-center">
                    ${item.is_checked ? '<i class="fas fa-check-circle text-success fs-4"></i>' :
                      (item.qty_ready > 0 ? '<i class="fas fa-hourglass-half text-warning fs-4"></i>' :
                      '<i class="fas fa-times-circle text-danger fs-4"></i>')}
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-warning edit-item"
                            data-id="${item.id}"
                            data-kode="${item.kode_barang || ''}"
                            data-barcode="${item.barcode || ''}"
                            data-nama="${item.nama_barang || ''}"
                            data-qty="${item.qty || 0}"
                            data-harga="${item.harga_jual || 0}"
                            data-diskon="${item.diskon_item || 0}"
                            data-bs-toggle="modal" data-bs-target="#editItemModal">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${!item.is_checked ? 
                      `<button class="btn btn-sm btn-danger delete-item" data-id="${item.id}">
                           <i class="fas fa-trash"></i>
                       </button>` : ''}
                </td>
            </tr>`;
        tbody.insertAdjacentHTML('beforeend', rowHTML);
    });

    // NEW: Combined totals row
    const summaryRow = `<tr class="table-info">
        <td colspan="4" class="text-end fw-bold">Total Qty / Ready / Diskon</td>
        <td class="text-center fw-bold">${new Intl.NumberFormat('id-ID').format(totalQty || 0)}</td>
        <td class="text-center fw-bold">${new Intl.NumberFormat('id-ID').format(totalQtyReady || 0)}</td>
        <td></td>
        <td class="text-center fw-bold">${new Intl.NumberFormat('id-ID').format(totalDiskonItem || 0)}</td>
        <td colspan="3"></td>
    </tr>`;
    tbody.insertAdjacentHTML('beforeend', summaryRow);
    
    const totalSaatIniRow = `<tr class="table-info"><td colspan="8" class="text-end fw-bold">Total Saat Ini</td><td class="fw-bold">${new Intl.NumberFormat('id-ID').format(totalSaatIni || 0)}</td><td colspan="2"></td></tr>`;
    tbody.insertAdjacentHTML('beforeend', totalSaatIniRow);
    
    const selisihVal = (parseFloat(totalAwal) || 0) - (parseFloat(totalSaatIni) || 0);
    const selisihRow = `<tr class="table-warning"><td colspan="8" class="text-end fw-bold">Selisih</td><td class="fw-bold">${new Intl.NumberFormat('id-ID').format(selisihVal)}</td><td colspan="2"></td></tr>`;
    tbody.insertAdjacentHTML('beforeend', selisihRow);
    const totalAwalRow = `<tr class="table-primary"><td colspan="8" class="text-end fw-bold">Total Awal</td><td class="fw-bold">${new Intl.NumberFormat('id-ID').format(totalAwal || 0)}</td><td colspan="2"></td></tr>`;
    tbody.insertAdjacentHTML('beforeend', totalAwalRow);

    attachEditItemListeners();
    attachDeleteItemListeners();
}

function attachEditItemListeners() {
    document.querySelectorAll('.edit-item').forEach(button => {
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);

        newButton.addEventListener('click', function() {
            document.getElementById('edit_item_id').value = this.dataset.id;
            document.getElementById('edit_kode_barang').value = this.dataset.kode;
            document.getElementById('edit_barcode').value = this.dataset.barcode;
            document.getElementById('edit_nama_barang').value = this.dataset.nama;
            document.getElementById('edit_qty').value = this.dataset.qty;
            document.getElementById('edit_harga_jual').value = this.dataset.harga;
            document.getElementById('edit_diskon_item').value = this.dataset.diskon;
        });
    });
}

function attachDeleteItemListeners() {
    document.querySelectorAll('.delete-item').forEach(button => {
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);

        newButton.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.id;
            const orderIdValEl = document.querySelector('input[name="pending_import_id"]');
            const orderIdVal = orderIdValEl ? orderIdValEl.value : new URLSearchParams(window.location.search).get('order_id') || <?php echo json_encode($_SESSION['order_details']['id'] ?? 'null'); ?>;

            if (!orderIdVal || orderIdVal === 'null') {
                showNotification('danger', 'Order ID tidak ditemukan untuk menghapus item.');
                return;
            }

            Swal.fire({
                title: 'Hapus Item?', text: 'Data item yang dihapus tidak dapat dikembalikan!', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(); 
                    formData.append('csrf_token', csrfTokenGlobal); 

                    fetch(`delete_item.php?order_id=${orderIdVal}&item_id=${itemId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-Token': csrfTokenGlobal
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('success', data.message);
                            fetchItemsAndUpdateTable(orderIdVal); 
                        } else {
                            showNotification('danger', data.message);
                        }
                    })
                    .catch(error => showNotification('danger', 'Error deleting item: ' + error.message));
                }
            });
        });
    });
}


function fetchItemsAndUpdateTable(orderIdVal) {
    if (!orderIdVal || orderIdVal === 'null') {
        console.error("Cannot fetch items: Order ID is null or invalid.");
        const tbody = document.querySelector('.table-responsive table tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center">Pilih order untuk melihat item.</td></tr>';
        }
        return;
    }
    fetch(`get_items.php?order_id=${orderIdVal}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentInitialTotal = typeof initialTotalGlobal !== 'undefined' && initialTotalGlobal !== 0 ? initialTotalGlobal : (data.order_initial_total || 0); 
                updateItemsTable(data.items, currentInitialTotal, data.total_saat_ini, data.total_qty, data.total_qty_ready, data.total_diskon_item);
            } else {
                showNotification('danger', data.error || 'Error fetching items');
            }
        })
        .catch(error => showNotification('danger', 'Error fetching items: ' + error.message));
}


document.getElementById('addItemForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const orderIdFormEl = document.querySelector('input[name="pending_import_id"]'); 
    let orderIdForm = orderIdFormEl ? orderIdFormEl.value : formData.get('pending_import_id'); 

    if (!orderIdForm) { 
        orderIdForm = new URLSearchParams(window.location.search).get('order_id') || <?php echo json_encode($_SESSION['order_details']['id'] ?? 'null'); ?>;
    }
    
    if (!orderIdForm || orderIdForm === 'null') {
        showNotification('danger', 'Order ID tidak ditemukan untuk menambah item.');
        return;
    }
    if (!formData.has('pending_import_id') || !formData.get('pending_import_id')) {
        formData.set('pending_import_id', orderIdForm);
    }
     if (!formData.has('csrf_token')) {
        formData.set('csrf_token', csrfTokenGlobal);
    }


    fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(response => response.text().then(text => { 
        try {
            return JSON.parse(text); 
        } catch (err) {
            console.error("Failed to parse JSON:", text.substring(0, 500)); 
            throw new Error("Server response was not valid JSON. Check console for details.");
        }
    }))
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            const modalElement = document.getElementById('addItemModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
            this.reset();
            fetchItemsAndUpdateTable(data.order_id || orderIdForm); 
        } else {
            showNotification('danger', data.message || "Unknown error adding item.");
        }
    })
    .catch(error => {
        showNotification('danger', 'Error: ' + error.message);
        console.error("Add item fetch error:", error);
    });
});

document.getElementById('editItemForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const orderIdFormEditEl = document.querySelector('input[name="pending_import_id"]');
    let orderIdFormEdit = orderIdFormEditEl ? orderIdFormEditEl.value : formData.get('pending_import_id');

    if (!orderIdFormEdit) {
        orderIdFormEdit = new URLSearchParams(window.location.search).get('order_id') || <?php echo json_encode($_SESSION['order_details']['id'] ?? 'null'); ?>;
    }

    if (!orderIdFormEdit || orderIdFormEdit === 'null') {
        showNotification('danger', 'Order ID tidak ditemukan untuk mengedit item.');
        return;
    }
    if (!formData.has('pending_import_id') || !formData.get('pending_import_id')) {
        formData.set('pending_import_id', orderIdFormEdit);
    }
    if (!formData.has('csrf_token')) {
        formData.set('csrf_token', csrfTokenGlobal);
    }

    fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(response => response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (err) {
            console.error("Failed to parse JSON:", text.substring(0, 500));
            throw new Error("Server response was not valid JSON. Check console for details.");
        }
    }))
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            const modalElement = document.getElementById('editItemModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
            fetchItemsAndUpdateTable(data.order_id || orderIdFormEdit); 
        } else {
            showNotification('danger', data.message || "Unknown error editing item.");
        }
    })
    .catch(error => {
        showNotification('danger', 'Error: ' + error.message);
        console.error("Edit item fetch error:", error);
    });
});

// NEW: Function for discount validation
function setupDiscountValidation(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const hargaJualInput = modal.querySelector('input[name="harga_jual"]');
    const diskonInput = modal.querySelector('input[name="diskon_item"]');
    
    if (!hargaJualInput || !diskonInput) return;
    
    const handleDiscountInput = () => {
        const hargaJual = parseFloat(hargaJualInput.value) || 0;
        let diskon = parseFloat(diskonInput.value) || 0;

        if (diskon > hargaJual) {
            diskonInput.value = hargaJual; // Cap the discount
            showNotification('warning', 'Diskon tidak boleh melebihi harga jual.', 2000);
        }
    };

    diskonInput.addEventListener('input', handleDiscountInput);
    // Also re-validate if the price changes
    hargaJualInput.addEventListener('input', handleDiscountInput);
}


document.addEventListener('DOMContentLoaded', () => {
    const scanForm = document.getElementById('scanForm');
    if (scanForm) {
        scanForm.addEventListener('submit', () => {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });
    }

    document.querySelectorAll('.delete-form .delete-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); 
            const form = this.closest('.delete-form');
            if (form) {
                confirmDelete(event, form); 
            } else {
                console.error("Delete form not found for button:", this);
            }
        });
    });
    
    if (document.querySelector('.table-responsive table tbody')) {
        attachEditItemListeners();
        attachDeleteItemListeners();
    }
    
    // Setup validation for both modals
    setupDiscountValidation('addItemModal');
    setupDiscountValidation('editItemModal');


    const barcodeInput = document.getElementById('barcode');
    const qtyScannedInput = document.getElementById('qty_scanned');
    const orderDataAvailable = <?php echo !empty($order_data) ? 'true' : 'false'; ?>;

    function prepareScanInput() {
        if (orderDataAvailable && barcodeInput) {
            barcodeInput.value = ''; 
        }
        if (orderDataAvailable && qtyScannedInput) {
            qtyScannedInput.value = '1'; 
        }
    }

    prepareScanInput(); 

    function focusBarcode() {
        if (barcodeInput && orderDataAvailable) {
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                const observer = new MutationObserver((mutationsList, obs) => {
                    for (const mutation of mutationsList) {
                        if (!Swal.isVisible()) {
                            barcodeInput.focus();
                            barcodeInput.select();
                            obs.disconnect(); 
                            return;
                        }
                    }
                });
                const swalContainer = Swal.getContainer();
                if (swalContainer) {
                    observer.observe(swalContainer, { attributes: true, childList: true, subtree: true });
                } else {
                     setTimeout(() => {
                         if (!Swal.isVisible()) { 
                             barcodeInput.focus();
                             barcodeInput.select();
                         }
                     }, 200); 
                }
            } else {
                barcodeInput.focus();
                barcodeInput.select();
            }
        }
    }
    
    if (wasScanAction) {
        const savedScrollPosition = sessionStorage.getItem('scrollPosition');
        if (savedScrollPosition !== null) {
            window.scrollTo(0, parseInt(savedScrollPosition, 10));
        }
    }

    const barcodeFocusDelay = wasScanAction ? (phpAlertTimer + 100) : 150; 
    setTimeout(() => {
        focusBarcode();
    }, barcodeFocusDelay);


    const formsToUppercaseConfig = ['scanForm', 'addItemForm', 'editItemForm'];
    formsToUppercaseConfig.forEach(formId => {
        const formElement = document.getElementById(formId);
        if (formElement) {
            formElement.querySelectorAll('input[type="text"], textarea').forEach(function(input) {
                if (input.name !== 'barcode' &&
                    input.name !== 'kode_barang' && 
                    input.id !== 'no_penjualan_search' && 
                    input.id !== 'add_harga_jual' && 
                    input.id !== 'edit_harga_jual' &&
                    input.id !== 'add_barcode' && 
                    input.id !== 'edit_barcode' 
                    ) {
                    input.addEventListener('input', function() {
                        if (this.type === 'text' || this.tagName.toLowerCase() === 'textarea') {
                            this.value = this.value.toUpperCase();
                        }
                    });
                }
            });
        }
    });
    const noPenjualanSearchEl = document.getElementById('no_penjualan_search');
    if (noPenjualanSearchEl) {
        noPenjualanSearchEl.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
// SCRIPT BARU UNTUK FLATPCIKR DAN FILTER
flatpickr("#date_range", {
    mode: "range",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "d F Y",
    defaultDate: "<?= !empty($date_range_trans) ? $date_range_trans : '' ?>"
});

const filterBtn = document.getElementById('filterBtn');
if (filterBtn) {
    filterBtn.addEventListener('click', () => {
        const search = document.getElementById('search').value;
        const dateRange = document.getElementById('date_range').value;
        
        let url = 'index.php?';
        const params = [];
        if (search) {
            params.push(`search=${encodeURIComponent(search)}`);
        }
        if (dateRange) {
            params.push(`date_range=${encodeURIComponent(dateRange)}`);
        }

        window.location.href = url + params.join('&');
    });
}

// FUNGSI EXPORT DATA BARU
function exportData() {
    const searchValue = encodeURIComponent(document.getElementById('search').value);
    const dateRangeValue = encodeURIComponent(document.getElementById('date_range').value);
    window.location.href = `export_excel.php?search=${searchValue}&date_range=${dateRangeValue}`;
}
</script>
</body>
</html>
<?php
function truncateText($text, $length) {
    if (is_null($text)) return '';
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
}

function formatCurrency($amount) {
    if (is_null($amount)) return 'Rp 0';
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>
<?php
if (isset($_SESSION['alert_timer_js_val'])) unset($_SESSION['alert_timer_js_val']);
if ($conn) {
    $conn->close();
}
?>
