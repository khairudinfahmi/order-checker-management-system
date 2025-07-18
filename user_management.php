<?php
require 'config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Token CSRF tidak valid!");
        }

        // --- Logika Hapus User ---
        if (isset($_POST['delete'])) {
            $user_id = (int)$_POST['user_id'];
            if ($user_id === $_SESSION['user']['id']) {
                throw new Exception("Tidak bisa menghapus akun sendiri!");
            }

            $conn->begin_transaction();
            // Ambil username sebelum dihapus untuk log
            $stmt_get_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt_get_user->bind_param("i", $user_id);
            $stmt_get_user->execute();
            $deleted_username = $stmt_get_user->get_result()->fetch_assoc()['username'] ?? 'N/A';
            $stmt_get_user->close();

            $stmt_del_act = $conn->prepare("DELETE FROM activity_log WHERE user_id = ?");
            $stmt_del_act->bind_param("i", $user_id);
            $stmt_del_act->execute();
            $stmt_del_act->close();

            $stmt_del_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_del_user->bind_param("i", $user_id);
            $stmt_del_user->execute();
            
            if ($stmt_del_user->affected_rows === 0) {
                throw new Exception("Gagal menghapus user atau user tidak ditemukan!");
            }
            $stmt_del_user->close();
            
            $conn->commit();
            // --- Pembaruan Log ---
            logActivity($conn, $_SESSION['user']['id'], 'delete_user', "Menghapus user: $deleted_username", ['deleted_user_id' => $user_id, 'deleted_username' => $deleted_username]);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'User berhasil dihapus!'];

        // --- Logika Tambah/Edit User ---
        } else {
            $required = ['username' => 'Username', 'role' => 'Role'];
            foreach ($required as $field => $label) {
                if (empty($_POST[$field])) {
                    throw new Exception("$label harus diisi!");
                }
            }

            $data = [
                'username' => sanitize($_POST['username']),
                'role' => in_array($_POST['role'], ['admin', 'checker']) ? $_POST['role'] : 'checker',
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? ''
            ];

            // --- Logika Edit User ---
            if (isset($_POST['user_id'])) {
                $user_id = (int)$_POST['user_id'];
                $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $check->bind_param("si", $data['username'], $user_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception("Username sudah digunakan!");
                }
                $check->close();

                $sql = "UPDATE users SET username = ?, role = ?";
                $types = 'ss';
                $params = [$data['username'], $data['role']];
                
                if (!empty($data['password'])) {
                    if (strlen($data['password']) < 6) {
                        throw new Exception("Password harus minimal 6 karakter!");
                    }
                    if ($data['password'] !== $data['confirm_password']) {
                        throw new Exception("Konfirmasi password tidak cocok!");
                    }
                    $sql .= ", password = ?";
                    $types .= 's';
                    $params[] = createPasswordHash($data['password']);
                }
                $sql .= " WHERE id = ?";
                $types .= 'i';
                $params[] = $user_id;
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                // --- Pembaruan Log ---
                logActivity($conn, $_SESSION['user']['id'], 'update_user', "Memperbarui user: {$data['username']}", ['updated_user_id' => $user_id, 'data' => $data]);
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'User berhasil diperbarui!'];
            
            // --- Logika Tambah User ---
            } else {
                if (empty($data['password']) || strlen($data['password']) < 6) {
                    throw new Exception("Password harus diisi dan minimal 6 karakter!");
                }
                if ($data['password'] !== $data['confirm_password']) {
                    throw new Exception("Konfirmasi password tidak cocok!");
                }
                
                $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $check->bind_param("s", $data['username']);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception("Username sudah terdaftar!");
                }
                $check->close();

                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $hashed_password = createPasswordHash($data['password']);
                $stmt->bind_param("sss", $data['username'], $hashed_password, $data['role']);
                $stmt->execute();
                $new_user_id = $stmt->insert_id;
                $stmt->close();
                // --- Pembaruan Log ---
                logActivity($conn, $_SESSION['user']['id'], 'create_user', "Menambah user baru: {$data['username']}", ['new_user_id' => $new_user_id, 'data' => $data]);
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'User berhasil ditambahkan!'];
            }
        }
    } catch (Exception $e) {
        if ($conn->in_transaction) $conn->rollback();
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }
    header("Location: user_management.php");
    exit;
}

// --- PERBAIKAN QUERY ---
// Query untuk mengambil data user beserta total aktivitas dari tabel log
$users_query = "
    SELECT
        u.id,
        u.username,
        u.role,
        u.last_login,
        (SELECT COUNT(*) FROM activity_log WHERE user_id = u.id) AS total_activities
    FROM
        users u
    ORDER BY
        u.role DESC, u.username ASC";
$users = $conn->query($users_query)->fetch_all(MYSQLI_ASSOC);

$edit_user = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Checker WA Order</title>
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
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-users-cog me-2"></i>Manajemen Pengguna
                    <span class="badge bg-primary ms-2">Admin Only</span>
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-striped table-hover user-table">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Total Aktivitas</th>
                                <th>Terakhir Login</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($user['username']) ?>
                                    <?php if ($user['id'] === $_SESSION['user']['id']): ?>
                                        <span class="badge bg-success ms-2">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge role-badge <?= ($user['role'] ?? 'checker') === 'admin' ? 'bg-danger' : 'bg-secondary' ?>">
                                        <?= strtoupper(htmlspecialchars($user['role'] ?? 'checker')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['total_activities']) ?> aktivitas</td>
                                <!-- PERBAIKAN TAMPILAN -->
                                <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Belum pernah' ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="?edit=<?= $user['id'] ?>#form-edit" 
                                           class="btn btn-sm btn-warning"
                                           <?= $user['id'] === $_SESSION['user']['id'] ? 'disabled' : '' ?>>
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="delete-user-form d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="delete" value="1">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" 
                                                    class="btn btn-sm btn-danger delete-user-btn"
                                                    <?= $user['id'] === $_SESSION['user']['id'] ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr class="my-5">

                <h5 class="mb-4" id="form-edit">
                    <i class="fas fa-<?= !empty($edit_user) ? 'edit' : 'plus' ?> me-2"></i>
                    <?= !empty($edit_user) ? 'Edit User: ' . htmlspecialchars($edit_user['username']) : 'Tambah User Baru' ?>
                </h5>
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <?php if (!empty($edit_user)): ?>
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($edit_user['id']) ?>">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="username" name="username" 
                                    value="<?= htmlspecialchars($edit_user['username'] ?? '') ?>" required>
                                <label for="username">Username</label>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="role" name="role" required>
                                    <option value="checker" <?= ($edit_user['role'] ?? '') === 'checker' ? 'selected' : '' ?>>Checker</option>
                                    <option value="admin" <?= ($edit_user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <label for="role">Role</label>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password"
                                    <?= empty($edit_user) ? 'required' : '' ?> minlength="6">
                                <label for="password"><?= !empty($edit_user) ? 'Password Baru (opsional)' : 'Password' ?></label>
                                <?php if (!empty($edit_user)): ?>
                                    <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                    <?= empty($edit_user) ? 'required' : '' ?> minlength="6">
                                <label for="confirm_password">Konfirmasi Password</label>
                            </div>
                        </div>
                        <div class="col-12 text-end mt-3">
                            <?php if (!empty($edit_user)): ?>
                                <a href="user_management.php" class="btn btn-secondary">Batal Edit</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                const password = form.querySelector('#password');
                const confirmPassword = form.querySelector('#confirm_password');
                
                // Cek jika password diisi, konfirmasi harus cocok
                if (password && confirmPassword && password.value !== '' && password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Password tidak cocok.');
                } else if (confirmPassword) {
                    confirmPassword.setCustomValidity('');
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        document.querySelectorAll('.delete-user-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const form = this.closest('form');
                confirmDelete(event, form); // Gunakan fungsi dari main.js
            });
        });
    })();
    </script>
</body>
</html>
<?php $conn->close(); ?>