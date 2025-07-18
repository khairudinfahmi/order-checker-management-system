<?php
require 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

requireAuth();

// Ambil parameter dari GET request
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$date_range = isset($_GET['date_range']) ? sanitize($_GET['date_range']) : '';

$whereClause = "WHERE 1=1 ";
$params = [];
$types = '';

// Filter berdasarkan pencarian teks
if (!empty($search)) {
    // ## MODIFIED: Added o.telepon_customer to the search ##
    $whereClause .= " AND (o.nama_customer LIKE ? OR o.no_penjualan LIKE ? OR o.layanan_pengiriman LIKE ? OR o.sumber_layanan LIKE ? OR o.telepon_customer LIKE ?) ";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $types .= 'sssss';
}

// Filter berdasarkan rentang tanggal
if (!empty($date_range)) {
    $dates = explode(' to ', $date_range);
    if (count($dates) === 2) {
        $start_date = $dates[0] . ' 00:00:00';
        $end_date = $dates[1] . ' 23:59:59';
        $whereClause .= " AND o.tanggal BETWEEN ? AND ? ";
        array_push($params, $start_date, $end_date);
        $types .= 'ss';
    }
}

// Query untuk mengambil data transaksi dengan detail item
// ## MODIFIED: Added o.telepon_customer to SELECT ##
$sql = "SELECT 
            o.id, o.nama_customer, o.no_penjualan, o.checker, o.tanggal, o.telepon_customer,
            oi.kode_barang, oi.nama_barang, oi.qty as qty_item, 
            oi.harga_jual, oi.diskon_item, oi.sub_total
        FROM online_wa o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $whereClause
        ORDER BY o.tanggal DESC, o.id ASC, oi.id ASC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$data_transaksi_flat = $result->fetch_all(MYSQLI_ASSOC);

if (empty($data_transaksi_flat)) {
    $_SESSION['alert'] = ['type' => 'warning', 'message' => 'Tidak ada data untuk diekspor sesuai filter yang dipilih.'];
    header('Location: index.php');
    exit;
}

// Olah ulang data untuk pengelompokan
$grouped_data = [];
foreach ($data_transaksi_flat as $row) {
    $order_id = $row['id'];
    if (!isset($grouped_data[$order_id])) {
        $grouped_data[$order_id] = [
            'order_info' => [
                'tanggal' => $row['tanggal'],
                'no_penjualan' => $row['no_penjualan'],
                'nama_customer' => $row['nama_customer'],
                'checker' => $row['checker'],
                'telepon_customer' => $row['telepon_customer'],
            ],
            'items' => []
        ];
    }
    $grouped_data[$order_id]['items'][] = $row;
}

// --- PEMBUATAN FILE EXCEL ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Penjualan');

// --- STYLE DEFINITIONS ---
$centerAlignment = ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER];
$leftAlignment = ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER];
$rightAlignment = ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER];
$allBorders = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

// --- HEADER LAPORAN ---
$sheet->mergeCells('A1:L1');
$sheet->setCellValue('A1', 'Laporan Penjualan - Toko Usaha');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->applyFromArray($centerAlignment);

$sheet->mergeCells('A2:L2');
$periode = !empty($date_range) ? 'Periode: ' . str_replace(' to ', ' s/d ', $date_range) : 'Periode: Semua Tanggal';
$sheet->setCellValue('A2', $periode);
$sheet->getStyle('A2')->getAlignment()->applyFromArray($centerAlignment);

// --- HEADER TABEL ---
// ## MODIFIED: Swapped 'Telepon' and 'Checker' ##
$headers = [
    'No', 'Tanggal', 'No. Penjualan', 'Customer', 'Telepon', 'Checker',
    'Kode Barang', 'Nama Barang', 'Qty', 'Harga Jual', 'Diskon', 'Sub Total'
];
$sheet->fromArray($headers, NULL, 'A4');
$headerStyle = $sheet->getStyle('A4:L4');
$headerStyle->getFont()->setBold(true);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAD3');
$headerStyle->getAlignment()->applyFromArray($centerAlignment);

// --- PENGISIAN DATA ---
$row_num = 5;
$counter = 1;
$total_qty_all = 0;
$total_diskon_all = 0;
$total_subtotal_all = 0;

foreach ($grouped_data as $order_id => $data) {
    $item_count = count($data['items']);
    $start_row = $row_num;

    // Tulis setiap item
    // ## MODIFIED: Shifted item columns to the right ##
    foreach ($data['items'] as $item) {
        $sheet->setCellValue('G' . $row_num, $item['kode_barang']);
        $sheet->setCellValue('H' . $row_num, $item['nama_barang']);
        $sheet->setCellValue('I' . $row_num, $item['qty_item']);
        $sheet->setCellValue('J' . $row_num, $item['harga_jual']);
        $sheet->setCellValue('K' . $row_num, $item['diskon_item']);
        $sheet->setCellValue('L' . $row_num, $item['sub_total']);
        
        $total_qty_all += $item['qty_item'];
        $total_diskon_all += $item['diskon_item'];
        $total_subtotal_all += $item['sub_total'];

        $row_num++;
    }
    
    $end_row = $row_num - 1;

    if ($item_count > 0) {
        // Tulis data order
        $sheet->setCellValue('A' . $start_row, $counter);
        $sheet->setCellValue('B' . $start_row, date('d/m/Y H:i', strtotime($data['order_info']['tanggal'])));
        $sheet->setCellValue('C' . $start_row, $data['order_info']['no_penjualan']);
        $sheet->setCellValue('D' . $start_row, $data['order_info']['nama_customer']);
        // ## MODIFIED: Swapped 'Telepon' and 'Checker' columns ##
        $sheet->setCellValue('E' . $start_row, $data['order_info']['telepon_customer']);
        $sheet->setCellValue('F' . $start_row, $data['order_info']['checker']);
        
        // Lakukan merge jika item lebih dari satu
        if ($item_count > 1) {
            $sheet->mergeCells('A' . $start_row . ':A' . $end_row);
            $sheet->mergeCells('B' . $start_row . ':B' . $end_row);
            $sheet->mergeCells('C' . $start_row . ':C' . $end_row);
            $sheet->mergeCells('D' . $start_row . ':D' . $end_row);
            $sheet->mergeCells('E' . $start_row . ':E' . $end_row);
            $sheet->mergeCells('F' . $start_row . ':F' . $end_row);
        }
        
        // Atur alignment untuk sel yang digabung (dan yang tidak)
        $sheet->getStyle('A' . $start_row . ':F' . $end_row)->getAlignment()->applyFromArray($leftAlignment);
        $sheet->getStyle('A' . $start_row . ':A' . $end_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Khusus kolom No
    }
    $counter++;
}

// --- BARIS TOTAL KESELURUHAN ---
$total_row_num = $row_num;
$sheet->mergeCells('A' . $total_row_num . ':H' . $total_row_num); 
$sheet->setCellValue('A' . $total_row_num, 'TOTAL KESELURUHAN');
$sheet->setCellValue('I' . $total_row_num, $total_qty_all);
// Kolom J (Harga Jual) sengaja dikosongkan
$sheet->setCellValue('K' . $total_row_num, $total_diskon_all);
$sheet->setCellValue('L' . $total_row_num, $total_subtotal_all);

// Styling untuk baris total
$totalStyle = $sheet->getStyle('A' . $total_row_num . ':L' . $total_row_num);
$totalStyle->getFont()->setBold(true)->setSize(12);
$totalStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
$sheet->getStyle('A' . $total_row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// --- PENYESUAIAN FINAL ---
// Atur lebar kolom otomatis
foreach (range('A', 'L') as $col) {
    if($col != 'H') { // Kolom Nama Barang (H) diatur manual
         $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}
$sheet->getColumnDimension('H')->setWidth(40);

// Format Angka
$numberFormat = '#,##0';
$sheet->getStyle('I5:L' . $total_row_num)->getNumberFormat()->setFormatCode($numberFormat);

// Terapkan Border ke seluruh tabel data
$sheet->getStyle('A4:L' . $total_row_num)->applyFromArray($allBorders);

// --- OUTPUT FILE ---
$writer = new Xlsx($spreadsheet);
$filename = 'Laporan_Penjualan_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
$writer->save('php://output');

$conn->close();
exit;