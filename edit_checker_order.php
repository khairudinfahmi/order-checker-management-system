<?php

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);



session_start();

require 'config.php';

requireAuth();



// Generate CSRF token

if (!isset($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}



// Clean up unnecessary session data for this page context

unset($_SESSION['order_details']);

unset($_SESSION['order_ready_to_complete']);

unset($_SESSION['order_initial_total']);

unset($_SESSION['initial_items']);



$order_data = [];

$items = [];

$total_subtotal = 0;

$total_qty = 0;

$total_diskon_item = 0;



// Check for the 'edit' parameter in the URL

if (!isset($_GET['edit'])) {

    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Permintaan tidak valid!'];

    header("Location: index.php");

    exit;

}



$id = (int)$_GET['edit'];



// Fetch the main order data from the 'online_wa' table

$stmt = $conn->prepare("SELECT * FROM online_wa WHERE id = ? AND source = 'checker'");

$stmt->bind_param("i", $id);

$stmt->execute();

$order_data = $stmt->get_result()->fetch_assoc();



if (!$order_data) {

    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Data order tidak ditemukan!'];

    header("Location: index.php");

    exit;

}



// Initialize form data from the database if not already in session (for preserving edits on reload)

if (!isset($_SESSION['form_data']['order_id']) || $_SESSION['form_data']['order_id'] != $id) {

    $_SESSION['form_data'] = [

        'order_id' => $id,

        'nama_customer' => $order_data['nama_customer'],

        'sumber_layanan' => $order_data['sumber_layanan'],

        'layanan_pengiriman' => $order_data['layanan_pengiriman'],

        'alamat' => $order_data['alamat']

    ];

}



// Fetch all items associated with this order

$stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");

$stmt_items->bind_param("i", $id);

$stmt_items->execute();

$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);



// Calculate total quantity

$stmt_qty = $conn->prepare("SELECT SUM(qty) as total_qty FROM order_items WHERE order_id = ?");

$stmt_qty->bind_param("i", $id);

$stmt_qty->execute();

$total_qty = $stmt_qty->get_result()->fetch_assoc()['total_qty'] ?? 0;



// Calculate total item discount

$stmt_diskon = $conn->prepare("SELECT SUM(diskon_item) as total_diskon FROM order_items WHERE order_id = ?");

$stmt_diskon->bind_param("i", $id);

$stmt_diskon->execute();

$total_diskon_item = $stmt_diskon->get_result()->fetch_assoc()['total_diskon'] ?? 0;



// Calculate current total (subtotal sum)

$stmt_total = $conn->prepare("SELECT SUM(sub_total) as total FROM order_items WHERE order_id = ?");

$stmt_total->bind_param("i", $id);

$stmt_total->execute();

$total_subtotal = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;



// Handle form submission to update the order

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {

    try {

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

            throw new Exception("Token keamanan tidak valid!");

        }



        $order_id = (int)$_POST['order_id'];



        // Validate required fields

        $required_fields = ['nama_customer', 'sumber_layanan', 'layanan_pengiriman', 'telepon_customer'];

        foreach ($required_fields as $field) {

            if (empty(trim($_POST[$field]))) {

                throw new Exception("Field '$field' harus diisi!");

            }

        }

        if (sanitize($_POST['layanan_pengiriman'] ?? '') !== 'AMBIL DI TOKO' && empty($_POST['alamat'])) {

            throw new Exception("Alamat harus diisi jika bukan AMBIL DI TOKO!");

        }



        $conn->begin_transaction();



        // Update the main order details in the 'online_wa' table

        $stmt_update = $conn->prepare("UPDATE online_wa SET 
            nama_customer = ?, 
            sumber_layanan = ?, 
            layanan_pengiriman = ?, 
            alamat = ?,
            telepon_customer = ?, 
            checker = ?, 
            tanggal = NOW()
            WHERE id = ?");

        

        if (!$stmt_update) {

            throw new Exception("Gagal mempersiapkan statement update: " . $conn->error);

        }



        $checker = $_SESSION['user']['username'];
        $nama_customer = mb_strtoupper(sanitize($_POST['nama_customer']));
        $sumber_layanan = mb_strtoupper(sanitize($_POST['sumber_layanan']));
        $layanan_pengiriman = mb_strtoupper(sanitize($_POST['layanan_pengiriman']));
        $alamat = mb_strtoupper(sanitize($_POST['alamat']));
        $telepon_customer = sanitize($_POST['telepon_customer']); // <-- Tambahkan baris ini

        $stmt_update->bind_param(
            "ssssssi", // <-- Ubah dari "sssssi" menjadi "ssssssi"
            $nama_customer,
            $sumber_layanan,
            $layanan_pengiriman,
            $alamat,
            $telepon_customer, // <-- Tambahkan parameter ini
            $checker,
            $order_id
        );

        

        if (!$stmt_update->execute()) {

            throw new Exception("Gagal mengeksekusi update: " . $stmt_update->error);

        }



        // Ganti logActivity di dalam blok try saat update

$log_details = [
    'order_id' => $order_id,
    'nama_customer' => $nama_customer,
    'sumber_layanan' => $sumber_layanan,
    'layanan_pengiriman' => $layanan_pengiriman,
    'telepon_customer' => $telepon_customer // <-- Tambahkan baris ini
];

logActivity($conn, $_SESSION['user']['id'], 'update_checker_order', "Memperbarui checker order ID: $order_id", $log_details);



        $conn->commit();



        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Data order berhasil diperbarui!'];

        $_SESSION['alert_timer'] = 2000;

        unset($_SESSION['form_data']);



        header("Location: index.php");

        exit;



    } catch (Exception $e) {

        if ($conn->in_transaction) {

            $conn->rollback();

        }

        error_log("Complete order error on edit page: " . $e->getMessage());

        $_SESSION['alert'] = ['type' => 'danger', 'message' => "Gagal menyimpan data: " . $e->getMessage()];

        $_SESSION['alert_timer'] = 2000;

        header("Location: edit_checker_order.php?edit=" . ($id ?? ''));

        exit;

    }

}

?>



<!DOCTYPE html>

<html lang="id" data-bs-theme="light">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Checker Order</title>

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

                <h5 class="mb-0 fw-semibold"><i class="fas fa-edit me-2"></i>Edit Order (Checker)</h5>

            </div>

            <div class="card-body p-4">

                <form method="POST" id="checkerForm" onsubmit="return confirmCheckerComplete(event, 'Apakah Anda yakin ingin menyimpan perubahan pada order ini?')">

                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <input type="hidden" name="order_id" value="<?= $order_data['id'] ?>">

                    <input type="hidden" name="complete_order" value="1">

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
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nama_customer" 
                                        name="nama_customer" required
                                        value="<?= htmlspecialchars($order_data['nama_customer'] ?? '') ?>">
                                    <label for="nama_customer">Nama Customer</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="telepon_customer" 
       name="telepon_customer" required
       inputmode="numeric" pattern="[0-9]*"
       value="<?= htmlspecialchars($order_data['telepon_customer'] ?? '') ?>">
                                    <label for="telepon_customer">Nomor Telepon Customer</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="sumber_layanan" name="sumber_layanan" required>
                                        <?php $sumber_val = $order_data['sumber_layanan'] ?? 'WA1'; ?>
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
                                        $current_layanan = $order_data['layanan_pengiriman'] ?? 'GO SEND INSTANT/SAMEDAY';
                                        $layanan_list = $conn->query("SELECT nama_layanan FROM layanan_pengiriman ORDER BY nama_layanan ASC");
                                        if ($layanan_list) {
                                            while ($row = $layanan_list->fetch_assoc()):
                                        ?>
                                        <option value="<?= htmlspecialchars($row['nama_layanan']) ?>" 
                                            <?= $current_layanan === $row['nama_layanan'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['nama_layanan']) ?>
                                        </option>
                                        <?php 
                                            endwhile;
                                        }
                                        ?>
                                    </select>
                                    <label for="layanan_pengiriman">Layanan Pengiriman</label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="alamat" name="alamat" 
                                        style="height: 100px;"><?= htmlspecialchars($order_data['alamat'] ?? '') ?></textarea>
                                    <label for="alamat">Alamat Pengiriman</label>
                                </div>
                            </div>
                        </div>

                    <!-- Items List Table -->

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover">

                            <thead class="bg-primary text-white">

                                <tr>

                                    <th>No</th><th>SKU</th><th>Barcode</th><th>Nama Barang</th><th>Qty</th><th>Harga</th><th>Diskon Item</th><th>Subtotal</th><th class="text-center">Status</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if (!empty($items)): ?>

                                    <?php foreach ($items as $i => $item): ?>

                                    <tr class="<?= $item['is_checked'] ? 'table-success' : 'table-light' ?>">

                                        <td><?= $i + 1 ?></td>

                                        <td><?= htmlspecialchars($item['kode_barang']) ?></td>

                                        <td><?= htmlspecialchars($item['barcode']) ?></td>

                                        <td><?= htmlspecialchars($item['nama_barang']) ?></td>

                                        <td class="text-center"><?= $item['qty'] ?></td>

                                        <td><?= number_format($item['harga_jual'], 0, ',', '.') ?></td>

                                        <td class="text-center"><?= number_format($item['diskon_item'], 0, ',', '.') ?></td>

                                        <td><?= number_format($item['sub_total'], 0, ',', '.') ?></td>

                                        <td class="text-center">

                                            <?php if ($item['is_checked']): ?><i class="fas fa-check-circle text-success fs-4"></i>

                                            <?php else: ?><i class="fas fa-times-circle text-danger fs-4"></i><?php endif; ?>

                                        </td>

                                    </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr><td colspan="9" class="text-center">Belum ada item.</td></tr>

                                <?php endif; ?>

                                

                                <!-- Totals Summary Rows -->

                                <tr class="table-info">

                                    <td colspan="4" class="text-end fw-bold">Total Qty / Diskon</td>

                                    <td class="text-center fw-bold"><?= number_format($total_qty, 0, ',', '.') ?></td>

                                    <td></td> <!-- Empty for Harga column -->

                                    <td class="text-center fw-bold"><?= number_format($total_diskon_item, 0, ',', '.') ?></td>

                                    <td colspan="2"></td> <!-- Adjusted colspan -->

                                </tr>

                                

                                <tr class="table-info">

                                    <td colspan="7" class="text-end fw-bold">Total</td>

                                    <td class="fw-bold" colspan="2"><?= number_format($total_subtotal, 0, ',', '.') ?></td>

                                </tr>

                                <?php

                                $total_awal_display = $order_data['total_belanja'] ?? 0;

                                $selisih_display = $total_awal_display - $total_subtotal;

                                ?>

                                <?php if ($selisih_display != 0): ?>

                                <tr class="table-warning"><td colspan="7" class="text-end fw-bold">Selisih</td><td class="fw-bold" colspan="2"><?= number_format($selisih_display, 0, ',', '.') ?></td></tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>



                    <!-- Action Buttons -->

                    <div class="d-flex justify-content-end mt-3">

                        <a href="index.php" class="btn btn-secondary me-2">

                            <i class="fas fa-arrow-left me-2"></i>Kembali

                        </a>

                        <button type="submit" class="btn btn-success">

                            <i class="fas fa-save me-2"></i>Simpan Perubahan

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <?php include 'footer.php'; ?>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="main.js"></script>

    <script>

    document.addEventListener('DOMContentLoaded', () => {

        // Uppercase input fields automatically

        const form = document.getElementById('checkerForm');

        if (form) {

            form.querySelectorAll('input[type="text"], textarea').forEach(function(input) {

                // Exclude read-only fields from this behavior

                if (!input.readOnly) {

                    input.addEventListener('input', function() {

                        this.value = this.value.toUpperCase();

                    });

                }

            });

        }

    });

    </script>

</body>

</html>

<?php 

if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']);

$conn->close(); 

?>