<?php
// Atur Zona Waktu Default ke Jakarta
date_default_timezone_set('Asia/Jakarta');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
$host = 'sql103.byethost22.com';
$dbname = 'b22_37265128_democheckerwaorder';
$username = 'b22_37265128';
$password = 'YnScc89#';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->query("SET time_zone = '+07:00'");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Koneksi ke database invoice
$invoice_db_host = 'sql103.byethost22.com';
$invoice_db_name = 'b22_37265128_demoinvoicegenerator';
$invoice_db_user = 'b22_37265128';
$invoice_db_pass = 'YnScc89#';

try {
    $invoice_conn = new mysqli($invoice_db_host, $invoice_db_user, $invoice_db_pass, $invoice_db_name);
    if ($invoice_conn->connect_error) {
        throw new Exception("Connection to invoice database failed: " . $invoice_conn->connect_error);
    }
    $invoice_conn->query("SET time_zone = '+07:00'");
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Gagal terhubung ke database invoice. Menggunakan data default.'];
    $_SESSION['alert_timer'] = 2000;
}

// Helper Functions
function sanitize($input, $connection = null) {
    global $conn;
    $db = $connection ?? $conn;
    return mysqli_real_escape_string($db, trim($input));
}

/**
 * Mengambil data invoice berdasarkan nomor pesanan dari database invoice.
 *
 * @param string $no_pesanan Nomor pesanan yang akan dicari.
 * @return array|null Data invoice dalam bentuk array asosiatif atau null jika tidak ditemukan/gagal.
 */
function getInvoiceData($no_pesanan) {
    global $invoice_conn;
    
    if (!isset($invoice_conn) || $invoice_conn->connect_error) {
        return null;
    }
    
    $stmt = $invoice_conn->prepare("SELECT nama_penerima, courier_name, alamat_penerima, telepon_penerima FROM invoices WHERE no_pesanan = ?");
    $stmt->bind_param("s", $no_pesanan);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

function createPasswordHash($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function requireAuth() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header("Location: index.php");
        exit;
    }
}

/**
 * Mencatat aktivitas pengguna ke database dengan detail.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $user_id ID pengguna yang melakukan aktivitas.
 * @param string $activity_type Jenis aktivitas (misal: 'login', 'create_user').
 * @param string $description Deskripsi singkat aktivitas.
 * @param array $details Detail tambahan dalam bentuk array asosiatif. Akan diubah menjadi JSON.
 * @return bool True jika berhasil, false jika gagal.
 */
function logActivity($conn, $user_id, $activity_type, $description, $details = []) {
    if (!is_array($details)) {
        $details = [];
    }
    
    $safe_details = $details;
    if (isset($safe_details['password'])) {
        unset($safe_details['password']);
    }
    if (isset($safe_details['confirm_password'])) {
        unset($safe_details['confirm_password']);
    }
    if (isset($safe_details['data']) && is_array($safe_details['data'])) {
         if (isset($safe_details['data']['password'])) {
            unset($safe_details['data']['password']);
        }
        if (isset($safe_details['data']['confirm_password'])) {
            unset($safe_details['data']['confirm_password']);
        }
    }
    
    $details_json = !empty($safe_details) ? json_encode($safe_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;

    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity_type, description, details, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt === false) {
        error_log("Prepare failed for logActivity: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isss", $user_id, $activity_type, $description, $details_json);
    
    $is_executed = $stmt->execute();
    if ($is_executed === false) {
        error_log("Execute failed for logActivity: " . $stmt->error);
    }
    $stmt->close();
    return $is_executed;
}

function generateSalesNumber($conn) {
    $date = date('ymd');
    $prefix = "JO-{$date}";
    $suffix = "-S";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM online_wa WHERE no_penjualan LIKE ?");
    $likePattern = "$prefix%";
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $count = $result['count'] + 1;
    
    return sprintf("%s%04d%s", $prefix, $count, $suffix);
}

function showAlert() {
    if (isset($_SESSION['alert'])) {
        $type = htmlspecialchars($_SESSION['alert']['type'], ENT_QUOTES, 'UTF-8');
        $message_json = json_encode($_SESSION['alert']['message'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $timer = $_SESSION['alert_timer'] ?? 1500; 
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                try {
                    if (typeof Swal === 'undefined') {
                        console.error('SweetAlert2 (Swal) is not loaded.');
                        alert({$message_json});
                        return;
                    }
                    Swal.fire({
                        icon: '$type' === 'success' ? 'success' : ('$type' === 'danger' ? 'error' : '$type'),
                        title: '$type' === 'success' ? 'Berhasil!' : ('$type' === 'danger' ? 'Gagal!' : 'Info'),
                        html: {$message_json},
                        timer: $timer,
                        showConfirmButton: false
                    });
                } catch (e) {
                    console.error('Error in showAlert:', e);
                    alert({$message_json});
                }
            });
        </script>";
        unset($_SESSION['alert']);
        if (isset($_SESSION['alert_timer'])) {
            unset($_SESSION['alert_timer']);
        }
    }
}

function getOrderItems($conn, $order_id) {
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Secara otomatis membersihkan log aktivitas yang lebih lama dari 1 bulan.
 * Fungsi ini dirancang untuk berjalan paling banyak sekali setiap 24 jam
 * untuk menjaga performa aplikasi.
 *
 * @param mysqli $conn Koneksi database.
 * @return void
 */
function autoClearOldLogs($conn) {
    // Tentukan interval pembersihan (24 jam dalam detik)
    $cleanup_interval = 24 * 60 * 60; 

    // Cek kapan terakhir kali pembersihan dilakukan dari session
    $last_cleanup_time = $_SESSION['last_log_cleanup_time'] ?? 0;

    // Jika waktu saat ini belum melewati interval, jangan lakukan apa-apa
    if ((time() - $last_cleanup_time) < $cleanup_interval) {
        return;
    }

    // Lakukan proses penghapusan log yang lebih lama dari 1 bulan
    $sql = "DELETE FROM activity_log WHERE created_at < NOW() - INTERVAL 1 MONTH";
    
    if ($conn->query($sql)) {
        $deleted_rows = $conn->affected_rows;
        // Jika ada baris yang terhapus, catat aktivitas ini
        if ($deleted_rows > 0) {
            // ID 0 digunakan untuk menandakan ini adalah aktivitas sistem
            logActivity($conn, 0, 'auto_cleanup', "Pembersihan otomatis menghapus $deleted_rows log.", ['deleted_count' => $deleted_rows]);
        }
    } else {
        // Catat error jika query gagal
        error_log("Gagal membersihkan log otomatis: " . $conn->error);
    }

    // Perbarui waktu terakhir pembersihan di session
    $_SESSION['last_log_cleanup_time'] = time();
}

// Panggil fungsi pembersihan otomatis jika pengguna sudah login
if (isset($_SESSION['user'])) {
    autoClearOldLogs($conn);
}
?>