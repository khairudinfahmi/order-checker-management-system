<?php

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);



session_start();

require 'config.php';

requireAuth();



// Generate CSRF token if not set

if (!isset($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}



// Clean up session data from the checker process to ensure a fresh form

unset($_SESSION['order_details']);

unset($_SESSION['form_data']);



$edit_data = [];

$generated_no_penjualan = '';



if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM online_wa WHERE id = ?");

    if ($stmt === false) {

        die("Prepare failed: " . $conn->error);

    }

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $edit_data = $stmt->get_result()->fetch_assoc();

    if (!$edit_data) {

        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Data not found!'];

        header("Location: manual_order.php");

        exit;

    }

} else {

    // Generate a sales number for a new form

    $generated_no_penjualan = generateSalesNumber($conn);

}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn->begin_transaction();

    try {

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

            throw new Exception("Invalid CSRF token!");

        }



        // ## MODIFIED: Added telepon_customer to required fields ##

        $required = [

            'nama_customer' => 'Nama Customer',

            'telepon_customer' => 'Nomor Telepon',

            'sumber_layanan' => 'Sumber Layanan',

            'layanan_pengiriman' => 'Pengiriman',

            'alamat' => 'Alamat',

            'qty' => 'Quantity',

            'total_belanja' => 'Total Belanja'

        ];

        foreach ($required as $field => $label) {

            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {

                throw new Exception("$label is required!");

            }

        }



        // ## MODIFIED: Added telepon_customer to data array ##

        $data = [

            'nama_customer' => mb_strtoupper(sanitize($_POST['nama_customer'])),

            'no_penjualan' => mb_strtoupper(sanitize($_POST['no_penjualan'] ?? '')),

            'telepon_customer' => sanitize($_POST['telepon_customer']),

            'sumber_layanan' => sanitize($_POST['sumber_layanan']),

            'layanan_pengiriman' => sanitize($_POST['layanan_pengiriman']),

            'alamat' => mb_strtoupper(sanitize($_POST['alamat'])),

            'total_belanja' => (float)$_POST['total_belanja'],

            'qty' => (int)$_POST['qty'],

            'checker' => sanitize($_SESSION['user']['username'])

        ];



        if (empty($data['no_penjualan'])) {

            $data['no_penjualan'] = generateSalesNumber($conn);

            if (!$data['no_penjualan']) {

                throw new Exception("Failed to generate sales number!");

            }

        }



        if (isset($_POST['id'])) {

            // Update existing order

            $id = (int)$_POST['id'];

            // ## MODIFIED: Added telepon_customer to UPDATE statement ##

            $stmt = $conn->prepare("UPDATE online_wa SET 

                nama_customer = ?, no_penjualan = ?, sumber_layanan = ?, 

                layanan_pengiriman = ?, alamat = ?, total_belanja = ?, 

                qty = ?, checker = ?, telepon_customer = ?, status_checked = 'completed', source = 'manual'

                WHERE id = ?");

            if ($stmt === false) {

                throw new Exception("Prepare failed: " . $conn->error);

            }

            // ## MODIFIED: Updated bind_param for the new field ##

            $stmt->bind_param(

                "sssssdisis",

                $data['nama_customer'],

                $data['no_penjualan'],

                $data['sumber_layanan'],

                $data['layanan_pengiriman'],

                $data['alamat'],

                $data['total_belanja'],

                $data['qty'],

                $data['checker'],

                $data['telepon_customer'],

                $id

            );

            $stmt->execute();

            logActivity($conn, $_SESSION['user']['id'], 'update_manual_order', "Memperbarui manual order No.Penjualan: {$data['no_penjualan']}", ['order_id' => $id, 'data' => $data]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Order berhasil diperbarui!'];

        } else {

            // Insert new order

            // ## MODIFIED: Added telepon_customer to INSERT statement ##

            $stmt = $conn->prepare("INSERT INTO online_wa (

                nama_customer, no_penjualan, sumber_layanan, layanan_pengiriman, 

                alamat, telepon_customer, total_belanja, qty, checker, tanggal, status_checked, source

            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'completed', 'manual')");

            if ($stmt === false) {

                throw new Exception("Prepare failed: " . $conn->error);

            }

            // ## MODIFIED: Updated bind_param for the new field ##

            $stmt->bind_param(

                "ssssssdis",

                $data['nama_customer'],

                $data['no_penjualan'],

                $data['sumber_layanan'],

                $data['layanan_pengiriman'],

                $data['alamat'],

                $data['telepon_customer'],

                $data['total_belanja'],

                $data['qty'],

                $data['checker']

            );

            $stmt->execute();

            $new_order_id = $conn->insert_id;

            logActivity($conn, $_SESSION['user']['id'], 'create_manual_order', "Membuat manual order baru No.Penjualan: {$data['no_penjualan']}", ['order_id' => $new_order_id, 'data' => $data]);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Order berhasil dibuat!'];

        }



        $conn->commit();

        header("Location: index.php");

        exit;

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['alert'] = ['type' => 'danger', 'message' => "Error: " . $e->getMessage()];

        $_SESSION['form_data'] = $_POST;

        header("Location: manual_order.php" . (isset($_POST['id']) ? "?edit={$_POST['id']}" : ""));

        exit;

    }

}



?>

<!DOCTYPE html>

<html lang="id" data-bs-theme="light">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manual Order - Checker WA Order</title>

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

                    <i class="fas fa-pen-to-square me-2"></i>

                    <?= isset($edit_data['id']) ? 'Edit Order' : 'Manual Order' ?>

                </h5>

            </div>

            <div class="card-body p-4">

                <form method="POST" id="orderForm" class="needs-validation" novalidate>

                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <?php if (isset($edit_data['id'])): ?>

                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">

                    <?php endif; ?>



                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nama_customer" 
                                    name="nama_customer" required
                                    value="<?= htmlspecialchars($edit_data['nama_customer'] ?? '') ?>">
                                <label for="nama_customer">Nama Customer</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="no_penjualan" 
                                    name="no_penjualan" 
                                    value="<?= htmlspecialchars($edit_data['no_penjualan'] ?? $generated_no_penjualan) ?>">
                                <label for="no_penjualan">No. Penjualan</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="telepon_customer" 
    name="telepon_customer" required
    inputmode="numeric" pattern="[0-9]*"
    value="<?= htmlspecialchars($edit_data['telepon_customer'] ?? '') ?>">
                                <label for="telepon_customer">Nomor Telepon Customer</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="sumber_layanan" name="sumber_layanan" required>
                                    <option value="WA1" <?= ($edit_data['sumber_layanan'] ?? 'WA1') === 'WA1' ? 'selected' : '' ?>>WA1</option>
                                    <option value="WA2" <?= ($edit_data['sumber_layanan'] ?? '') === 'WA2' ? 'selected' : '' ?>>WA2</option>
                                    <option value="Shopify" <?= ($edit_data['sumber_layanan'] ?? '') === 'Shopify' ? 'selected' : '' ?>>Shopify</option>
                                    <option value="Belanja di Toko" <?= ($edit_data['sumber_layanan'] ?? '') === 'Belanja di Toko' ? 'selected' : '' ?>>Belanja di Toko</option>
                                </select>
                                <label for="sumber_layanan">Sumber Layanan</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                             <div class="form-floating">
                                <select class="form-select" id="layanan_pengiriman" name="layanan_pengiriman" required>
                                    <?php
                                    $layanan = $conn->query("SELECT nama_layanan FROM layanan_pengiriman ORDER BY nama_layanan ASC");
                                    while ($row = $layanan->fetch_assoc()):
                                        $selected = ($edit_data['layanan_pengiriman'] ?? '') === $row['nama_layanan'];
                                    ?>
                                    <option value="<?= htmlspecialchars($row['nama_layanan']) ?>" 
                                            <?= $selected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['nama_layanan']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <label for="layanan_pengiriman">Layanan Pengiriman</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="qty" 
                                    name="qty" required min="1" 
                                    value="<?= htmlspecialchars($edit_data['qty'] ?? '') ?>">
                                <label for="qty">Total Quantity</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="total_belanja" 
                                    name="total_belanja" required min="0" step="0.01" 
                                    value="<?= htmlspecialchars($edit_data['total_belanja'] ?? '') ?>">
                                <label for="total_belanja">Total Belanja (IDR)</label>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="checker" 
                                    name="checker" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>" readonly>
                                <label for="checker">Checker</label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="alamat" name="alamat" 
                                    style="height: 100px;" required><?= htmlspecialchars($edit_data['alamat'] ?? '') ?></textarea>
                                <label for="alamat">Alamat Pengiriman</label>
                            </div>
                        </div>
                    </div>


                    <div class="text-end mt-4">

                        <a href="index.php" class="btn btn-secondary me-2">

                            <i class="fas fa-arrow-left me-2"></i>Kembali

                        </a>

                        <button type="submit" class="btn btn-primary px-5 py-2">

                            <i class="fas fa-save me-2"></i>Simpan Order

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <div id="floating-notification" class="floating-notification"></div>



    <?php include 'footer.php'; ?>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="main.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('orderForm');

    // --- Bagian kode yang sudah ada untuk validasi & uppercase ---
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        form.querySelectorAll('input[type="text"], textarea').forEach(function(input) {
            if (input.name !== 'telepon_customer' && !input.readOnly) {
                input.addEventListener('input', function() {
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.toUpperCase();
                    this.setSelectionRange(start, end);
                });
            }
        });
    }

    // ▼▼▼ KODE BARU UNTUK AUTO-FILL DITEMPEL DI SINI ▼▼▼
    const noPenjualanInput = document.getElementById('no_penjualan');
    if (noPenjualanInput) {
        noPenjualanInput.addEventListener('blur', function() {
            const noPenjualan = this.value.trim();
            if (noPenjualan) {
                // Tampilkan loading spinner kecil jika ada
                // Swal.showLoading(); 

                fetch(`get_invoice_details.php?no_penjualan=${encodeURIComponent(noPenjualan)}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data) {
                            // Isi form dengan data yang diterima
                            document.getElementById('nama_customer').value = result.data.nama_customer.toUpperCase();
                            document.getElementById('telepon_customer').value = result.data.telepon_customer;
                            document.getElementById('alamat').value = result.data.alamat.toUpperCase();

                            const layananSelect = document.getElementById('layanan_pengiriman');
                            if (result.data.layanan_pengiriman) {
                                for (let i = 0; i < layananSelect.options.length; i++) {
                                    if (layananSelect.options[i].value.toUpperCase() === result.data.layanan_pengiriman.toUpperCase()) {
                                        layananSelect.selectedIndex = i;
                                        break;
                                    }
                                }
                            }

                            // Beri notifikasi sukses
                            Swal.fire({
                                icon: 'success',
                                title: 'Data Ditemukan!',
                                text: 'Data customer berhasil diisi otomatis.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching invoice details:', error);
                    });
            }
        });
    }
    // ▲▲▲ BATAS AKHIR KODE BARU ▲▲▲
});
</script>

</body>

</html>

<?php $conn->close(); ?>

