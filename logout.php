<?php
require 'config.php';

if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $username = $_SESSION['user']['username'];
    // Ganti baris logActivity dengan ini:
    logActivity($conn, $user_id, 'logout', "User $username logged out", ['username' => $username]);
    session_unset();
    session_destroy();
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Logout berhasil!'];
}

header("Location: login.php");
exit;
?>