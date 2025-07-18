<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fab fa-whatsapp me-2"></i>Checker WA Order
        </a>
        <div class="d-flex align-items-center gap-3">
            <!-- Theme Toggle -->
            <button type="button" id="themeToggle" class="btn btn-light btn-sm">
                <i class="fas fa-moon"></i>
            </button>
            
            <!-- Import Pesanan -->
            <a href="import_penjualan.php" class="btn btn-light px-3"> <!-- Tambah class px-3 untuk padding horizontal -->
                <i class="fas fa-file-import me-2"></i>Import Data
            </a>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light px-3 dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown"> <!-- Tambah px-3 -->
                    <i class="fas fa-user me-2"></i><?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <?php if(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                        <li><a class="dropdown-item" href="user_management.php"><i class="fas fa-users-cog me-2"></i>User Management</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="activity_log.php"><i class="fas fa-history me-2"></i>Activity Log</a></li>
                    <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>