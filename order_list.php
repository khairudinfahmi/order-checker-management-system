<?php
require_once 'config.php';
requireAuth();

// Pagination settings
$per_page = 20; // Same as index.php
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Search parameter for no_penjualan
$search_no_penjualan = isset($_GET['no_penjualan']) ? sanitize($_GET['no_penjualan']) : '';
$whereClause = '';
$params = [];
$types = '';

if (!empty($search_no_penjualan)) {
    $whereClause = "AND no_penjualan LIKE ?";
    $params[] = '%' . $search_no_penjualan . '%';
    $types .= 's';
}

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM pending_imports 
    WHERE nama_customer IS NULL AND sumber_layanan IS NULL AND layanan_pengiriman IS NULL AND alamat IS NULL $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total_records = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, ceil($total_records / $per_page));

// Fetch orders
$sql = "SELECT no_penjualan, 'pending' as check_status, tanggal FROM pending_imports 
    WHERE nama_customer IS NULL AND sumber_layanan IS NULL AND layanan_pengiriman IS NULL AND alamat IS NULL $whereClause 
    ORDER BY tanggal DESC 
    LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$filter_params_fetch = array_merge($params, [$offset, $per_page]); // Renamed to avoid conflict
$filter_types_fetch = $types . 'ii'; // Renamed to avoid conflict
$stmt->bind_param($filter_types_fetch, ...$filter_params_fetch);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle delete single order action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token!");
        }
        $no_penjualan_to_delete = sanitize($_POST['no_penjualan']); // Renamed to avoid conflict
        
        // Verify the order is pending
        $stmt_check = $conn->prepare("SELECT id FROM pending_imports WHERE no_penjualan = ? AND nama_customer IS NULL AND sumber_layanan IS NULL AND layanan_pengiriman IS NULL AND alamat IS NULL");
        $stmt_check->bind_param("s", $no_penjualan_to_delete);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows === 0) {
            throw new Exception("Order not found or not in pending status!");
        }
        
        $conn->begin_transaction();
        
        // Delete from pending_import_items
        $stmt_delete_items = $conn->prepare("DELETE FROM pending_import_items WHERE no_nota = ?");
        $stmt_delete_items->bind_param("s", $no_penjualan_to_delete);
        $stmt_delete_items->execute();
        
        // Delete from pending_imports
        $stmt_delete = $conn->prepare("DELETE FROM pending_imports WHERE no_penjualan = ?");
        $stmt_delete->bind_param("s", $no_penjualan_to_delete);
        $stmt_delete->execute();
        
        if ($stmt_delete->affected_rows === 0) {
            // This might happen if pending_import_items deletion failed silently or if the record was already gone.
            // We already check num_rows above, so this is more of a safeguard.
            throw new Exception("Failed to delete order from pending_imports or it was already deleted!");
        }
        
        $conn->commit();
        logActivity($conn, $_SESSION['user']['id'], 'delete_pending_order', "Menghapus pending order: $no_penjualan_to_delete", ['no_penjualan' => $no_penjualan_to_delete]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Order successfully deleted!'];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = ['type' => 'danger', 'message' => "Error: " . $e->getMessage()];
    }
    header("Location: order_list.php?page=$page" . ($search_no_penjualan ? "&no_penjualan=" . urlencode($search_no_penjualan) : ""));
    exit;
}

// Handle clear all pending orders action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_pending'])) {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token!");
        }

        $conn->begin_transaction();

        // First, delete items from pending_import_items associated with the pending orders
        // We need to select the no_nota of pending orders first
        $stmt_select_pending_notas = $conn->prepare(
            "SELECT no_penjualan FROM pending_imports 
             WHERE nama_customer IS NULL AND sumber_layanan IS NULL AND layanan_pengiriman IS NULL AND alamat IS NULL"
        );
        $stmt_select_pending_notas->execute();
        $result_pending_notas = $stmt_select_pending_notas->get_result();
        $pending_notas_to_delete = [];
        while ($row = $result_pending_notas->fetch_assoc()) {
            $pending_notas_to_delete[] = $row['no_penjualan'];
        }
        $stmt_select_pending_notas->close();

        if (!empty($pending_notas_to_delete)) {
            // Create placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($pending_notas_to_delete), '?'));
            $types_notas = str_repeat('s', count($pending_notas_to_delete));

            $stmt_delete_all_items = $conn->prepare("DELETE FROM pending_import_items WHERE no_nota IN ($placeholders)");
            $stmt_delete_all_items->bind_param($types_notas, ...$pending_notas_to_delete);
            $stmt_delete_all_items->execute();
            // We don't strictly need to check affected_rows here as some pending orders might not have items
            $stmt_delete_all_items->close();
        }

        // Then, delete all pending orders from pending_imports
        $stmt_delete_all = $conn->prepare(
            "DELETE FROM pending_imports 
             WHERE nama_customer IS NULL AND sumber_layanan IS NULL AND layanan_pengiriman IS NULL AND alamat IS NULL"
        );
        $stmt_delete_all->execute();
        $affected_rows_imports = $stmt_delete_all->affected_rows;
        $stmt_delete_all->close();

        if ($affected_rows_imports > 0) {
            $conn->commit();
            logActivity($conn, $_SESSION['user']['id'], 'clear_all_pending', "Membersihkan semua ($affected_rows_imports) pending orders.", ['cleared_count' => $affected_rows_imports]);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'All pending orders successfully cleared!'];
        } else {
            // If no rows were affected, it means there were no pending orders to delete.
            // We can consider this a success or a "no action needed" scenario.
            // To avoid confusion, let's still commit if no items were there.
            $conn->commit(); // or $conn->rollback(); if you want to treat "nothing to delete" as an error
            $_SESSION['alert'] = ['type' => 'info', 'message' => 'No pending orders found to clear.'];
        }

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = ['type' => 'danger', 'message' => "Error clearing all pending orders: " . $e->getMessage()];
    }
    header("Location: order_list.php"); // Redirect to page 1 without search params after clearing all
    exit;
}


showAlert();
?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Order Pending</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- SweetAlert2 and main.js are already included in your original code -->
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container main-content custom-container">
        <?php showAlert(); // Show alerts here, ideally only once per page load ?>

        <div class="dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-list-check me-2"></i>Daftar Pending Order</h5>
                <!-- Clear All Pending Button -->
                <?php if ($total_records > 0 && ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'checker')): // Show for admin or checker ?>
                <form method="POST" id="clear-all-pending-form" class="ms-auto">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="clear_all_pending" value="1">
                    <button type="button" id="clear-all-pending-btn" class="btn btn-danger btn-sm py-2">
                        <i class="fas fa-broom me-2"></i>Hapus Semua Pending
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <!-- Search Form -->
                <form method="GET" class="mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" name="no_penjualan" id="no_penjualan" class="form-control" 
                                    value="<?php echo htmlspecialchars($search_no_penjualan); ?>" 
                                    placeholder="Cari No. Penjualan">
                                <label for="no_penjualan"><i class="fas fa-search me-2"></i>Cari No. Penjualan</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2"> <!-- Reduced gap for better fit -->
                                <button type="submit" class="btn btn-primary w-50 py-3">
                                    <i class="fas fa-search me-2"></i>Cari
                                </button>
                                <a href="index.php" class="btn btn-secondary w-50 py-3"> <!-- Changed to secondary for differentiation -->
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover data-table">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">No. Penjualan</th>
                                <th class="text-center">Check Status</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <?php echo $search_no_penjualan ? 'Data Tidak Ditemukan untuk pencarian Anda.' : 'Tidak ada data pending order.'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($order['no_penjualan']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark">Pending</span> <!-- Added text-dark for better contrast on warning -->
                                        </td>
                                        <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($order['tanggal'])); ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2 aksi-buttons">
                                                <form method="POST" class="delete-form">
                                                    <input type="hidden" name="no_penjualan" value="<?php echo htmlspecialchars($order['no_penjualan']); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="delete_order" value="1">
                                                    <button type="submit" class="btn btn-sm btn-danger delete-btn" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-3 border-top">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0 pagination-sm">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search_no_penjualan ? '&no_penjualan=' . urlencode($search_no_penjualan) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php 
                                $start = max(1, min($page - 2, $total_pages - 4));
                                $end = min($total_pages, $start + 4);
                                if ($total_pages <= 5) { // Adjust for few pages
                                    $start = 1;
                                    $end = $total_pages;
                                }
                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search_no_penjualan ? '&no_penjualan=' . urlencode($search_no_penjualan) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search_no_penjualan ? '&no_penjualan=' . urlencode($search_no_penjualan) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="main.js"></script> <!-- Ensure main.js is loaded -->
    <script>
        // This script remains the same as your original for single delete
        document.addEventListener('DOMContentLoaded', () => {
            // Attach event listener for single delete buttons
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const form = this.closest('.delete-form');
                    Swal.fire({
                        title: 'Hapus Order?',
                        text: 'Data order yang dihapus tidak dapat dikembalikan!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Attach event listener for "Clear All Pending" button
            const clearAllBtn = document.getElementById('clear-all-pending-btn');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    const form = document.getElementById('clear-all-pending-form');
                    if (form) {
                        Swal.fire({
                            title: 'Hapus Semua Pending Order?',
                            text: 'Semua data pending order akan dihapus dan tidak dapat dikembalikan! Aksi ini tidak dapat dibatalkan.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Ya, Hapus Semua!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        console.error("Clear all pending form not found!");
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>