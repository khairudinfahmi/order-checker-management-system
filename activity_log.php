<?php
require 'config.php';

// Pagination and search
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20; // Increased for more visibility
$offset = ($current_page - 1) * $per_page;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$whereClause = '';
$params = [];
$types = '';
if (!empty($search)) {
    $whereClause = " WHERE u.username LIKE ? OR al.activity_type LIKE ? OR al.description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = 'sss';
}

$countSql = "SELECT COUNT(*) as total 
             FROM activity_log al 
             JOIN users u ON al.user_id = u.id 
             $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total_records = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, ceil($total_records / $per_page));

// Ambil kolom 'details' juga
$sql = "SELECT al.id, al.activity_type, al.description, al.details, al.created_at, u.username 
        FROM activity_log al 
        JOIN users u ON al.user_id = u.id 
        $whereClause 
        ORDER BY al.created_at DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$limit_params = array_merge($params, [$offset, $per_page]);
$limit_types = $types . 'ii';
$stmt->bind_param($limit_types, ...$limit_params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Clear logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token!");
        }
        requireAdmin(); // Hanya admin yang bisa membersihkan log

        // Simpan info user sebelum truncate
        $clearing_user_id = $_SESSION['user']['id'];
        $clearing_username = $_SESSION['user']['username'];
        
        // Bersihkan tabel
        $conn->query("TRUNCATE TABLE activity_log");
        
        // Log ulang aktivitas pembersihan setelah truncate
        // Ini memastikan log pembersihan itu sendiri ada sebagai satu-satunya entri
        logActivity($conn, $clearing_user_id, 'clear_logs', "Membersihkan semua log aktivitas", ['cleared_by' => $clearing_username]);

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Log aktivitas berhasil dibersihkan!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => "Error: " . $e->getMessage()];
    }
    header("Location: activity_log.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Checker WA Order</title>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-history me-2"></i>Log Aktivitas</h5>
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <form method="POST" class="d-inline clear-logs-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="clear_logs" value="1">
                    <button type="submit" class="btn btn-danger clear-logs-btn">
                        <i class="fas fa-trash me-2"></i>Bersihkan Semua Log
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <form method="GET" class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                            placeholder="Cari berdasarkan username, aktivitas, atau deskripsi..." value="<?= htmlspecialchars($search) ?>">
                         <button class="btn btn-primary" type="submit">Cari</button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>No</th>
                                <th>Username</th>
                                <th>Aktivitas</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Detail</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada log ditemukan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $i => $log): ?>
                                <tr>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td><?= htmlspecialchars($log['username']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['activity_type']) ?></span></td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($log['details'])): ?>
                                            <button type="button" class="btn btn-sm btn-info view-details-btn" data-details='<?= htmlspecialchars($log['details'], ENT_QUOTES, 'UTF-8') ?>'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php 
                        $start = max(1, min($current_page - 2, $total_pages - 4));
                        $end = min($total_pages, $start + 4);
                        if ($total_pages <= 5) {
                           $start = 1;
                           $end = $total_pages;
                        }
                        for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal untuk menampilkan detail JSON -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel"><i class="fas fa-info-circle me-2"></i>Detail Aktivitas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="details-content" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
            </div>
        </div>
    </div>
</div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Inisialisasi Modal
        const detailsModalEl = document.getElementById('detailsModal');
        const detailsModal = new bootstrap.Modal(detailsModalEl);
        
        // Event listener untuk tombol "Lihat Detail"
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                try {
                    const detailsData = JSON.parse(this.dataset.details);
                    // Format JSON agar mudah dibaca (pretty print)
                    document.getElementById('details-content').textContent = JSON.stringify(detailsData, null, 2);
                    detailsModal.show();
                } catch(e) {
                    console.error("Error parsing details JSON:", e);
                    // Tampilkan data mentah jika gagal parse
                    document.getElementById('details-content').textContent = this.dataset.details;
                    detailsModal.show();
                }
            });
        });

        // Event listener untuk tombol "Bersihkan Log"
        const clearLogsBtn = document.querySelector('.clear-logs-btn');
        if (clearLogsBtn) {
            clearLogsBtn.addEventListener('click', function(event) {
                event.preventDefault();
                const form = this.closest('form');
                confirmClearLogs(event, form);
            });
        }
    });

    // Tambahkan fungsi ini jika belum ada di main.js
    function confirmClearLogs(event, form) {
        event.preventDefault();
        Swal.fire({
            title: 'Anda yakin?',
            text: "Semua log aktivitas akan dihapus permanen dan tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>