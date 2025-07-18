<?php
require 'config.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Fungsi untuk mengatur alert
function setAlert($type, $message) {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
}

// Fungsi untuk validasi CSRF token
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception("Token CSRF tidak valid!");
    }
}

// Fungsi untuk validasi file upload
function validateFileUpload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Gagal mengupload file: " . $file['error']);
    }

    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception("Ukuran file terlalu besar! Maksimum 5MB.");
    }

    $allowed_types = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
        'application/vnd.ms-excel', 
        'text/csv'
    ];
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("Tipe file tidak didukung! Gunakan .xlsx, .xls, atau .csv");
    }
    
    return true;
}

// Fungsi untuk membuat reader berdasarkan ekstensi file
function createSpreadsheetReader($file) {
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $reader = null;
    
    switch ($file_extension) {
        case 'csv':
            $reader = IOFactory::createReader('Csv');
            break;
        case 'xlsx':
            $reader = IOFactory::createReader('Xlsx');
            break;
        case 'xls':
            $reader = IOFactory::createReader('Xls');
            break;
        default:
            throw new Exception("Format file tidak didukung");
    }
    
    $reader->setReadDataOnly(true);
    return $reader;
}

// Fungsi untuk memfilter baris kosong
function filterEmptyRows($rows) {
    return array_values(array_filter($rows, function($row) {
        $row = array_map('trim', array_map('strval', $row));
        $non_empty_cells = array_filter($row, function($cell) {
            return $cell !== '' && $cell !== null;
        });
        return count($non_empty_cells) > 0; // Pertahankan baris dengan setidaknya satu sel berisi
    }));
}

// Fungsi untuk menemukan baris header
function findHeaderRow($rows) {
    foreach ($rows as $index => $row) {
        $row = array_map('trim', array_map('strval', $row)); // Pastikan semua nilai adalah string dan trim
        if (in_array('No. Nota', $row) && in_array('Kode Barang', $row) && in_array('Nama Barang', $row)) {
            return ['index' => $index, 'row' => $row];
        }
    }
    throw new Exception("Baris header dengan 'No. Nota', 'Kode Barang', dan 'Nama Barang' tidak ditemukan!");
}

// Fungsi untuk menemukan indeks kolom berdasarkan nama header
function findColumnIndices($header_row) {
    $required_columns = [
        'No. Nota' => null,
        'Kode Barang' => null,
        'Barcode' => null,
        'Nama Barang' => null,
        'Qty' => null,
        'Harga Jual' => null,
        'Diskon Item' => null,
        'Sub Total' => null // Meskipun tidak digunakan untuk kalkulasi, tetap dicari untuk validasi format file
    ];

    foreach ($header_row as $index => $header) {
        $header = trim((string)$header);
        foreach ($required_columns as $key => &$value) {
            if (strcasecmp($header, $key) === 0) {
                $value = $index;
                break;
            }
        }
    }

    // Hanya kolom esensial untuk kalkulasi yang wajib ada
    $essential_columns = ['No. Nota', 'Kode Barang', 'Nama Barang', 'Qty', 'Harga Jual', 'Diskon Item'];
    $missing_columns = [];
    foreach($essential_columns as $col_name) {
        if (is_null($required_columns[$col_name])) {
            $missing_columns[] = $col_name;
        }
    }
    
    if (!empty($missing_columns)) {
        throw new Exception("Kolom wajib untuk kalkulasi tidak ditemukan: " . implode(', ', $missing_columns));
    }

    return $required_columns;
}

// Fungsi untuk mengelompokkan item berdasarkan nota
function groupItemsByInvoice($rows, $column_indices) {
    $grouped_items = [];
    $skipped_count = 0;
    $debug_info = [];
    
    foreach ($rows as $i => $row) {
        $row = array_map(function($value) {
            return is_null($value) ? '' : trim((string)$value);
        }, $row);
        
        error_log("Baris " . ($i + 1) . ": " . json_encode($row)); // Log data mentah baris

        // Skip baris kosong atau baris "TOTAL"
        if (empty($row) || (isset($row[0]) && strtoupper($row[0]) === 'TOTAL')) {
            $skipped_count++;
            $debug_info[] = [
                'row_number' => $i + 1,
                'no_nota' => '',
                'kode_barang' => '',
                'nama_barang' => '',
                'qty' => 0,
                'is_valid' => false,
                'reason' => 'Baris total atau kosong di-skip'
            ];
            error_log("Baris " . ($i + 1) . " di-skip: Baris total atau kosong");
            continue;
        }
        
        $no_nota = $row[$column_indices['No. Nota']] ?? '';
        $kode_barang = $row[$column_indices['Kode Barang']] ?? '';
        $barcode = $row[$column_indices['Barcode']] ?? '';
        $nama_barang = $row[$column_indices['Nama Barang']] ?? '';
        $qty = isset($row[$column_indices['Qty']]) ? (int)$row[$column_indices['Qty']] : 0;
        $harga_jual = isset($row[$column_indices['Harga Jual']]) ? (float)$row[$column_indices['Harga Jual']] : 0;
        $diskon_item = isset($row[$column_indices['Diskon Item']]) ? (float)$row[$column_indices['Diskon Item']] : 0;
        
        // ======================= PERUBAHAN DI SINI =======================
        // Subtotal sekarang selalu dihitung berdasarkan Qty * Harga - Diskon,
        // mengabaikan nilai dari kolom 'Sub Total' di file Excel.
        $sub_total = ($qty * $harga_jual) - $diskon_item;
        // ===================== AKHIR PERUBAHAN =========================


        $row_debug = [
            'row_number' => $i + 1,
            'no_nota' => $no_nota,
            'kode_barang' => $kode_barang,
            'nama_barang' => $nama_barang,
            'qty' => $qty,
            'is_valid' => true,
            'reason' => ''
        ];

        if (empty($no_nota) || empty($kode_barang) || empty($nama_barang) || $qty <= 0) {
            $skipped_count++;
            $row_debug['is_valid'] = false;
            $row_debug['reason'] = empty($no_nota) ? 'No. Nota kosong' : 
                                 (empty($kode_barang) ? 'Kode Barang kosong' : 
                                 (empty($nama_barang) ? 'Nama Barang kosong' : 'Qty harus > 0'));
            $debug_info[] = $row_debug;
            error_log("Baris " . ($i + 1) . " di-skip: " . $row_debug['reason']);
            continue;
        } else {
            $row_debug['reason'] = 'Data valid';
            error_log("Baris " . ($i + 1) . " valid: " . json_encode($row_debug));
        }

        if (!isset($grouped_items[$no_nota])) {
            $grouped_items[$no_nota] = [];
        }
        
        $grouped_items[$no_nota][] = [
            'kode_barang' => $kode_barang,
            'barcode' => $barcode,
            'nama_barang' => $nama_barang,
            'qty' => $qty,
            'harga_jual' => $harga_jual,
            'diskon_item' => $diskon_item,
            'sub_total' => $sub_total // Menyimpan subtotal hasil kalkulasi
        ];
        
        $debug_info[] = $row_debug;
    }
    
    return [
        'items' => $grouped_items, 
        'skipped' => $skipped_count,
        'debug_info' => $debug_info
    ];
}

// Fungsi untuk memeriksa apakah nota sudah selesai
function isInvoiceCompleted($conn, $no_nota) {
    $stmt = $conn->prepare("SELECT id FROM online_wa WHERE no_penjualan = ? AND status_checked = 'completed'");
    $stmt->bind_param("s", $no_nota);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_completed = $result->num_rows > 0;
    $stmt->close();
    
    error_log("Cek nota $no_nota: " . ($is_completed ? 'Sudah selesai' : 'Belum selesai'));
    return $is_completed;
}

// Fungsi untuk menyimpan data import
function savePendingImport($conn, $no_nota, $username) {
    $stmt = $conn->prepare("INSERT INTO pending_imports (no_penjualan, checker, tanggal) 
                          VALUES (?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE checker = VALUES(checker), tanggal = VALUES(tanggal)");
    $stmt->bind_param("ss", $no_nota, $username);
    $stmt->execute();
    
    $pending_import_id = $stmt->insert_id;
    $stmt->close();
    
    if (!$pending_import_id) {
        $stmt_select = $conn->prepare("SELECT id FROM pending_imports WHERE no_penjualan = ?");
        $stmt_select->bind_param("s", $no_nota);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $pending_import_id = $result->fetch_assoc()['id'];
        $stmt_select->close();
    }
    
    error_log("Pending import ID untuk nota $no_nota: $pending_import_id");
    return $pending_import_id;
}

// Fungsi untuk menghapus item lama dan menyimpan item baru
function saveImportItems($conn, $pending_import_id, $no_nota, $items) {
    $stmt_delete = $conn->prepare("DELETE FROM pending_import_items WHERE pending_import_id = ?");
    $stmt_delete->bind_param("i", $pending_import_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    
    $stmt_item = $conn->prepare("INSERT INTO pending_import_items (
        pending_import_id, 
        no_nota, 
        kode_barang, 
        barcode, 
        nama_barang, 
        qty, 
        qty_ready, 
        harga_jual, 
        diskon_item, 
        sub_total
    ) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)");
    
    $count = 0;
    foreach ($items as $item) {
        // TAMBAHKAN VALIDASI JUMLAH PARAMETER DI SINI
        $expectedParams = 9;
        $passedParams = count([
            $pending_import_id,
            $no_nota,
            $item['kode_barang'],
            $item['barcode'],
            $item['nama_barang'],
            $item['qty'],
            $item['harga_jual'],
            $item['diskon_item'],
            $item['sub_total']
        ]);

        if ($passedParams !== $expectedParams) {
            throw new Exception("Jumlah parameter tidak sesuai! Diharapkan: $expectedParams, Diberikan: $passedParams");
        }
        // END OF VALIDATION

        $stmt_item->bind_param("issssiddd",
            $pending_import_id,
            $no_nota,
            $item['kode_barang'],
            $item['barcode'],
            $item['nama_barang'],
            $item['qty'],
            $item['harga_jual'],
            $item['diskon_item'],
            $item['sub_total']
        );
        $stmt_item->execute();
        $count++;
        error_log("Item diimpor untuk nota $no_nota: " . json_encode($item));
    }
    
    $stmt_item->close();
    return $count;
}

// Handler untuk proses import data
function handleImport($conn) {
    $conn->begin_transaction();
    try {
        validateCSRFToken($_POST['csrf_token']);
        validateFileUpload($_FILES['excel_file']);
        
        $enable_debug = isset($_POST['enable_debug']);
        
        $reader = createSpreadsheetReader($_FILES['excel_file']);
        $spreadsheet = $reader->load($_FILES['excel_file']['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        error_log("Total baris awal: " . count($rows));

        if (isset($_POST['filter_empty_rows'])) {
            $rows = filterEmptyRows($rows);
            error_log("Total baris setelah filter: " . count($rows));
        }

        $header_info = findHeaderRow($rows);
        $header_row_index = $header_info['index'];
        $header_row = $header_info['row'];
        error_log("Baris header ditemukan di index: " . $header_row_index);

        $data_rows = array_slice($rows, $header_row_index + 1);
        error_log("Jumlah baris data: " . count($data_rows));

        $column_indices = findColumnIndices($header_row);

        $result = groupItemsByInvoice($data_rows, $column_indices);
        $grouped_items = $result['items'];
        $skipped_count = $result['skipped'];
        $debug_info = $result['debug_info'];
        $imported_count = 0;
        $skipped_rows = [];
        $completed_invoices = [];

        if (empty($grouped_items)) {
            throw new Exception("Tidak ada data transaksi valid untuk diimpor!");
        }

        foreach ($grouped_items as $no_nota => $items) {
            // Periksa apakah nota sudah selesai
            if (isInvoiceCompleted($conn, $no_nota)) {
                $completed_invoices[] = $no_nota;
                $skipped_count += count($items);
                foreach ($items as $item) {
                    $skipped_rows[] = "Nota $no_nota sudah selesai";
                    $debug_info[] = [
                        'row_number' => 'N/A',
                        'no_nota' => $no_nota,
                        'kode_barang' => $item['kode_barang'],
                        'nama_barang' => $item['nama_barang'],
                        'qty' => $item['qty'],
                        'is_valid' => false,
                        'reason' => "Nota $no_nota sudah selesai"
                    ];
                }
                error_log("Nota $no_nota di-skip karena sudah selesai");
                continue;
            }

            $pending_import_id = savePendingImport($conn, $no_nota, $_SESSION['user']['username']);
            $imported_count += saveImportItems($conn, $pending_import_id, $no_nota, $items);
        }

        $conn->commit();

        // Di dalam function handleImport(), setelah $conn->commit();
$log_details = [
    'file_name' => $_FILES['excel_file']['name'],
    'imported_invoices' => count($grouped_items),
    'imported_items' => $imported_count,
    'skipped_rows' => $skipped_count,
    'completed_invoices_skipped' => count($completed_invoices)
];
logActivity($conn, $_SESSION['user']['id'], 'import_sales', "Import data penjualan dari file: " . $_FILES['excel_file']['name'], $log_details);
        
        $message = "Import berhasil! $imported_count item diimport dari " . count($grouped_items) . " nota.";
        if (!empty($skipped_rows)) {
            $unique_skipped = array_unique($skipped_rows);
            $message .= "<br><strong>Data di-skip (" . count($unique_skipped) . "):</strong> " 
                      . implode(', ', array_slice($unique_skipped, 0, 5))
                      . (count($unique_skipped) > 5 ? '...' : '');
        }
        
        if ($skipped_count > 0) {
            $message .= "<br>Total baris di-skip: $skipped_count (format tidak valid/duplikat/sudah selesai)";
        }

        if ($enable_debug) {
            $_SESSION['debug_info'] = [
                'total_rows' => count($data_rows),
                'valid_rows' => count($debug_info) - $skipped_count,
                'skipped_rows' => $skipped_count,
                'completed_invoices' => $completed_invoices,
                'detailed_info' => $debug_info
            ];
        } else {
            unset($_SESSION['debug_info']);
        }

        setAlert('success', $message);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error selama impor: " . $e->getMessage());
        setAlert('danger', "Error: " . $e->getMessage());
    }
    
    header("Location: import_penjualan.php");
    exit;
}

// Main logic
$debug_info = $_SESSION['debug_info'] ?? null;
unset($_SESSION['debug_info']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    handleImport($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Penjualan - Checker WA Order</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container main-content custom-container">
        <?php showAlert(); ?>
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-file-import me-2"></i>Import Data Penjualan</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5><i class="fas fa-upload me-2"></i>Upload File Excel</h5>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <div class="mb-3">
                                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                        <small class="form-text text-muted">Maksimum 5MB</small>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="filter_empty_rows" name="filter_empty_rows" checked>
                                        <label class="form-check-label" for="filter_empty_rows">
                                            Otomatis filter baris kosong
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="enable_debug" name="enable_debug">
                                        <label class="form-check-label" for="enable_debug">
                                            Tampilkan info debug
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Import Data
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
    <div class="card">
        <div class="card-body">
            <h5><i class="fas fa-info-circle me-2"></i>Petunjuk Import</h5>
            <p class="mt-3">
                Gunakan template di bawah ini untuk memastikan format data sesuai/benar. Isi data penjualan sesuai dengan kolom yang telah disediakan di dalam template.
            </p>
            <a href="Sample.xlsx"
               class="btn btn-success w-100 mt-2" 
               target="_blank" 
               rel="noopener noreferrer">
                <i class="fas fa-download me-2"></i>Download Template
            </a>
        </div>
    </div>
</div>
                
                <?php if ($debug_info): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Informasi Debug</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h6>Total Baris</h6>
                                                <h3><?= $debug_info['total_rows'] ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h6>Baris Valid</h6>
                                                <h3><?= $debug_info['valid_rows'] ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body text-center">
                                                <h6>Baris Di-skip</h6>
                                                <h3><?= $debug_info['skipped_rows'] ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-danger text-white">
                                            <div class="card-body text-center">
                                                <h6>No Penjualan Selesai</h6>
                                                <h3><?= count($debug_info['completed_invoices']) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($debug_info['completed_invoices'])): ?>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>No Penjualan Selesai (Di-skip)</h6>
                                    <p>No Penjualan berikut sudah selesai (status "completed") dan dilewati:</p>
                                    <ul>
                                        <?php foreach($debug_info['completed_invoices'] as $invoice): ?>
                                        <li><?= htmlspecialchars($invoice) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                                
                                <h6 class="mt-4 mb-3"><i class="fas fa-list me-2"></i>Detail Baris</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Baris</th>
                                                <th>No. Penjualan</th>
                                                <th>Kode Barang</th>
                                                <th>Nama Barang</th>
                                                <th>Qty</th>
                                                <th>Status</th>
                                                <th>Alasan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($debug_info['detailed_info'] as $row): ?>
                                            <tr>
                                                <td><?= $row['row_number'] ?></td>
                                                <td><?= htmlspecialchars($row['no_nota']) ?></td>
                                                <td><?= htmlspecialchars($row['kode_barang']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                                <td><?= $row['qty'] ?></td>
                                                <td>
                                                    <?php if($row['is_valid']): ?>
                                                        <span class="badge bg-success">Valid</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Skip</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $row['reason'] ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
</body>
</html>