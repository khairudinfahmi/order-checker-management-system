<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

// Memastikan hanya pengguna yang sudah login yang bisa mengakses
requireAuth();

// Validasi ID Order dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID Order tidak valid atau tidak diberikan.");
}
$order_id = (int)$_GET['id'];

// --- FUNGSI UNTUK MENGUBAH ANGKA MENJADI TERBILANG ---
function terbilang($angka) {
    $angka = abs($angka);
    $bilangan = array('', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas');
    $terbilang = '';
    if ($angka < 12) {
        $terbilang = ' ' . $bilangan[$angka];
    } else if ($angka < 20) {
        $terbilang = terbilang($angka - 10) . ' Belas';
    } else if ($angka < 100) {
        $terbilang = terbilang($angka / 10) . ' Puluh' . terbilang($angka % 10);
    } else if ($angka < 200) {
        $terbilang = ' Seratus' . terbilang($angka - 100);
    } else if ($angka < 1000) {
        $terbilang = terbilang($angka / 100) . ' Ratus' . terbilang($angka % 100);
    } else if ($angka < 2000) {
        $terbilang = ' Seribu' . terbilang($angka - 1000);
    } else if ($angka < 1000000) {
        $terbilang = terbilang($angka / 1000) . ' Ribu' . terbilang($angka % 1000);
    } else if ($angka < 1000000000) {
        $terbilang = terbilang($angka / 1000000) . ' Juta' . terbilang($angka % 1000000);
    } else if ($angka < 1000000000000) {
        $terbilang = terbilang($angka / 1000000000) . ' Milyar' . terbilang($angka % 1000000000);
    }
    return $terbilang;
}


// --- AMBIL DATA DARI DATABASE ---
$stmt_order = $conn->prepare("SELECT * FROM online_wa WHERE id = ?");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order = $stmt_order->get_result()->fetch_assoc();

if (!$order) {
    die("Order tidak ditemukan.");
}

$stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);

$total_diskon_semua_item = 0;
foreach($items as $item) {
    $total_diskon_semua_item += $item['diskon_item'];
}

$sub_total_keseluruhan = $order['total_belanja'] + $total_diskon_semua_item;

// Buat instance mPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
]);

$mpdf->SetTitle('Nota - ' . $order['no_penjualan']);

// --- BUAT KONTEN HTML UNTUK PDF ---
$logo_path = 'logo.png';
$logo_data = base64_encode(file_get_contents($logo_path));

$html = '
<style>
    body { font-family: sans-serif; font-size: 10pt; }
    .header-table, .info-table, .items-table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: middle; }
    .info-table td { padding: 1px 0; }
    .items-table th, .items-table td { border: 1px solid #000; padding: 5px; }
    .items-table th { background-color: #f2f2f2; text-align: center;}
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .terbilang { font-style: italic; font-size: 9pt; vertical-align: top; }
    .footer-note { text-align: center; margin-top: 20px; font-weight: bold; border: 1px solid black; padding: 5px; }
    .signatures-table { width: 100%; margin-top: 30px; }
    .qc-note { font-size: 8pt; text-align: justify; margin-top: 15px; border-top: 1px solid #ccc; padding-top: 10px; }
    .totals-table { width: 100%; border-collapse: collapse; }
    .totals-table td { padding: 3px 8px; border-bottom: 1px solid #000; }
    .store-info { font-size: 9pt; padding-left: 10px; }
</style>

<body>
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 15%;">
                <img src="data:image/png;base64,'.$logo_data.'" width="70">
            </td>
            <td style="width: 55%;" class="store-info">
                <strong>khairudinfahmi</strong><br>
                Jl. Suka Kamu Banget
            </td>
            <td style="width: 30%; text-align: right;">
                <h1 style="margin:0; font-size: 16pt;">NOTA PENJUALAN</h1>
                <strong>Tgl Jual</strong> : '.date('Y-m-d H:i', strtotime($order['tanggal'])).'
            </td>
        </tr>
    </table>
    
    <hr>
    
    <!-- INFO CUSTOMER & TRANSAKSI -->
    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>No. Pesanan</strong></td>
            <td style="width: 85%;">: '.htmlspecialchars($order['no_penjualan']).'</td>
        </tr>
        <tr>
            <td><strong>Nama Pembeli</strong></td>
            <td>: '.htmlspecialchars($order['nama_customer']).'</td>
        </tr>
        <tr>
            <td><strong>No. Telepon</strong></td>
            <td>: '.htmlspecialchars($order['telepon_customer']).'</td>
        </tr>
        <tr>
            <td style="vertical-align: top;"><strong>Alamat</strong></td>
            <td style="vertical-align: top;">: '.nl2br(htmlspecialchars($order['alamat'])).'</td>
        </tr>
    </table>
    
    <br>
    
    <!-- TABEL ITEM -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width: 15%;">Harga Jual</th>
                <th style="width: 12%;">Diskon Item</th>
                <th style="width: 15%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>';
            foreach ($items as $item) {
                $html .= '
                <tr>
                    <td class="text-center">'.htmlspecialchars($item['qty']).' PCS</td>
                    <td class="text-center">'.htmlspecialchars($item['kode_barang']).'</td>
                    <td>'.htmlspecialchars($item['nama_barang']).'</td>
                    <td class="text-right">'.number_format($item['harga_jual'], 0, ',', '.').'</td>
                    <td class="text-right">'.number_format($item['diskon_item'], 0, ',', '.').'</td>
                    <td class="text-right">'.number_format($item['sub_total'], 0, ',', '.').'</td>
                </tr>';
            }
$html .= '
        </tbody>
    </table>
    
    <!-- TOTALS (Layout Sesuai Screenshot) -->
    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td style="width: 50%;" class="terbilang">
                '.ucwords(trim(terbilang($order['total_belanja']))).' Rupiah
            </td>
            <td style="width: 50%; padding-left: 20px;">
                <table class="totals-table">
                    <tr>
                        <td style="width: 40%;">Sub Total</td>
                        <td class="text-right">Rp '.number_format($sub_total_keseluruhan, 0, ',', '.').'</td>
                    </tr>
                    <tr>
                        <td>Diskon</td>
                        <td class="text-right">Rp '.number_format($total_diskon_semua_item, 0, ',', '.').'</td>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td class="text-right"><strong>Rp '.number_format($order['total_belanja'], 0, ',', '.').'</strong></td>
                    </tr>
                     <tr>
                        <td>Bayar/DP</td>
                        <td class="text-right">Rp '.number_format($order['total_belanja'], 0, ',', '.').'</td>
                    </tr>
                     <tr>
                        <td>Kembalian</td>
                        <td class="text-right">Rp 0</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer-note">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</div>
    
    <table class="signatures-table">
        <tr>
            <td class="text-center">Hormat kami,</td>
        </tr>
        <tr>
            <td class="text-center" style="padding-top: 50px;">(___________________)</td>
        </tr>
    </table>
    
    <!-- KLAUSUL QC -->
    <div class="qc-note">
        <strong>PENTING:</strong> Kami memastikan setiap produk, khususnya barang elektronik, telah lolos proses Quality Control (QC) internal dan berfungsi dengan baik sebelum diserahkan kepada Pembeli. Selanjutnya, seluruh urusan purnajual seperti klaim garansi dan perbaikan produk, wajib diajukan langsung oleh Pembeli ke pusat servis resmi yang ditunjuk oleh merek/brand. Perlu kami tegaskan bahwa toko kami tidak menyediakan layanan perbaikan (servis) dalam bentuk apa pun. Dengan menyelesaikan transaksi ini, Pembeli dianggap telah memahami dan menyetujui sepenuhnya seluruh ketentuan di atas.
    </div>

</body>
';

// Tulis HTML ke PDF dan output ke browser
$mpdf->WriteHTML($html);
$mpdf->Output('Nota-'.$order['no_penjualan'].'.pdf', 'I');

$conn->close();