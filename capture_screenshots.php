<?php
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe';
$shotDir = 'C:\xampp\htdocs\melora-kosmetik\screenshots';

$shots = [
    '01_login.png'             => 'login.php',
    '02_dashboard.png'         => 'dashboard.php',
    '03_kategori.png'          => 'categories/index.php',
    '04_produk.png'            => 'products/index.php',
    '05_supplier.png'          => 'suppliers/index.php',
    '06_barang_masuk.png'      => 'stock/incoming.php',
    '07_barang_keluar.png'     => 'stock/outgoing.php',
    '08_histori_transaksi.png' => 'stock/history.php',
    '09_laporan_inventory.png' => 'reports/index.php',
    '10_validasi_error.png'    => 'test_validation_error.php',
    '11_akses_terlarang_403.png' => '403.php',
    '12_detail_produk.png'     => 'products/detail.php?id=1',
    '13_laporan_kategori.png'  => 'reports/categories.php',
    '14_pengguna.png'          => 'users/index.php'
];

foreach ($shots as $file => $to) {
    $outFile = $shotDir . DIRECTORY_SEPARATOR . $file;
    if ($file === '01_login.png') {
        $url = 'http://localhost/melora-kosmetik/public/login.php';
    } elseif ($file === '11_akses_terlarang_403.png') {
        $url = 'http://localhost/melora-kosmetik/public/dev_login.php?as=staff&to=' . rawurlencode('users/index.php');
    } else {
        $url = 'http://localhost/melora-kosmetik/public/dev_login.php?as=admin&to=' . rawurlencode($to);
    }
    $cmd = "\"$chrome\" --headless=new --screenshot=\"$outFile\" --window-size=1280,850 \"$url\"";
    echo "Capturing $file ... ";
    exec($cmd);
    echo (file_exists($outFile) ? "OK (" . filesize($outFile) . " bytes)" : "FAILED") . PHP_EOL;
}
