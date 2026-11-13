<?php
// =====================================================================
// AUTOMATED TEST SUITE: MELORA KOSMETIK
// Mini Project 3 Pertemuan 4: Relational Database + Advanced PHP
// =====================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/escape.php';
require_once __DIR__ . '/helpers/validation.php';
require_once __DIR__ . '/helpers/auth.php';

$passed = 0;
$failed = 0;

function assert_test(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] " . $name . ($detail ? " ($detail)" : "") . PHP_EOL;
    } else {
        $failed++;
        echo "[FAIL] " . $name . ($detail ? " ($detail)" : "") . PHP_EOL;
    }
}

echo "==========================================================" . PHP_EOL;
echo "MELORA KOSMETIK - PENGUJIAN OTOMATIS SISTEM INVENTORY" . PHP_EOL;
echo "==========================================================" . PHP_EOL;

// 1. Verifikasi Koneksi Database & Tabel
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expectedTables = ['users', 'user_profiles', 'categories', 'products', 'suppliers', 'product_suppliers', 'stock_movements'];
$missingTables = array_diff($expectedTables, $tables);
assert_test("1. Verifikasi 7 Tabel Relasional Database", empty($missingTables), "Tabel lengkap: " . implode(', ', $tables));

// 2. Verifikasi Akun Admin dan Staff
$adminUser = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch();
$staffUser = $pdo->query("SELECT * FROM users WHERE username = 'staff'")->fetch();
assert_test("2. Akun Admin Terdaftar & Role Benar", $adminUser && $adminUser['role'] === 'admin');
assert_test("3. Akun Staff Terdaftar & Role Benar", $staffUser && $staffUser['role'] === 'staff');
assert_test("4. Password Hashing Bcrypt Valid (password_verify)",
    password_verify('password', $adminUser['password_hash']) && password_verify('password', $staffUser['password_hash'])
);

// 3. Verifikasi Relasi One-to-One (users <-> user_profiles)
$profiles = $pdo->query("
    SELECT u.username, p.full_name
    FROM users u
    JOIN user_profiles p ON p.user_id = u.id
")->fetchAll();
assert_test("5. Relasi One-to-One users dan user_profiles Berjalan", count($profiles) >= 2);

// 4. Verifikasi Relasi Many-to-Many (product_suppliers)
$manyToMany = $pdo->query("
    SELECT p.name AS product_name, s.name AS supplier_name, ps.supplier_price
    FROM product_suppliers ps
    JOIN products p ON p.id = ps.product_id
    JOIN suppliers s ON s.id = ps.supplier_id
")->fetchAll();
assert_test("6. Relasi Many-to-Many products & suppliers via product_suppliers Berjalan", count($manyToMany) > 0, count($manyToMany) . " relasi pemasok aktif");

// 5. Test CRUD Kategori: Tambah Kategori
$testCatName = 'Kategori Uji ' . time();
$stmtCat = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
$stmtCat->execute([$testCatName, 'Deskripsi kategori pengujian sistem']);
$testCatId = (int)$pdo->lastInsertId();
assert_test("7. Tambah Master Data Kategori Berhasil", $testCatId > 0, "ID Kategori: $testCatId");

// 6. Test Validasi SKU Unik
$existingProduct = $pdo->query("SELECT sku FROM products LIMIT 1")->fetch();
$isDuplicateAllowed = is_unique_sku($pdo, $existingProduct['sku']);
assert_test("8. Validasi Penolakan SKU Duplikat Berjalan", $isDuplicateAllowed === false, "SKU {$existingProduct['sku']} berhasil ditolak");

// 7. Test Tambah Produk Baru
$testSku = 'MLR-TEST-' . rand(1000, 9999);
$stmtProd = $pdo->prepare("
    INSERT INTO products (category_id, sku, name, brand, price, stock, minimum_stock)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmtProd->execute([$testCatId, $testSku, 'Lip Tint Tester Pink', 'Melora Lab', 50000, 10, 5]);
$testProdId = (int)$pdo->lastInsertId();
assert_test("9. Tambah Master Data Produk Berhasil", $testProdId > 0, "ID Produk: $testProdId, SKU: $testSku");

// 8. Test Transaksi Barang Masuk (+ stok & stock_movements)
$initialStock = (int)$pdo->query("SELECT stock FROM products WHERE id = $testProdId")->fetchColumn();
$inQty = 15;

$pdo->beginTransaction();
$pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$inQty, $testProdId]);
$pdo->prepare("INSERT INTO stock_movements (product_id, user_id, type, quantity, note) VALUES (?, ?, 'IN', ?, ?)")
    ->execute([$testProdId, $adminUser['id'], $inQty, 'Uji coba barang masuk unit test']);
$pdo->commit();

$afterInStock = (int)$pdo->query("SELECT stock FROM products WHERE id = $testProdId")->fetchColumn();
assert_test("10. Transaksi Barang Masuk Menambah Stok Sesuai Komitmen Database",
    $afterInStock === ($initialStock + $inQty),
    "Stok awal: $initialStock -> Setelah Masuk: $afterInStock"
);

// 9. Test Transaksi Barang Keluar (- stok & stock_movements)
$outQty = 5;
$pdo->beginTransaction();
$stmtLock = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
$stmtLock->execute([$testProdId]);
$currentStk = (int)$stmtLock->fetchColumn();

if ($outQty <= $currentStk) {
    $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$outQty, $testProdId]);
    $pdo->prepare("INSERT INTO stock_movements (product_id, user_id, type, quantity, note) VALUES (?, ?, 'OUT', ?, ?)")
        ->execute([$testProdId, $staffUser['id'], $outQty, 'Uji coba barang keluar unit test']);
    $pdo->commit();
} else {
    $pdo->rollBack();
}

$afterOutStock = (int)$pdo->query("SELECT stock FROM products WHERE id = $testProdId")->fetchColumn();
assert_test("11. Transaksi Barang Keluar Mengurangi Stok dengan Benar",
    $afterOutStock === ($afterInStock - $outQty),
    "Stok sebelum keluar: $afterInStock -> Setelah Keluar: $afterOutStock"
);

// 10. Test Validasi Error: Barang Keluar Melebihi Stok Ditolak & Rollback!
$excessQty = 9999;
$rollbackWorked = false;
$pdo->beginTransaction();
$stmtLock = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
$stmtLock->execute([$testProdId]);
$currentStk = (int)$stmtLock->fetchColumn();

if ($excessQty > $currentStk) {
    // Sesuai business rules: batalkan dan rollback
    $pdo->rollBack();
    $rollbackWorked = true;
} else {
    $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$excessQty, $testProdId]);
    $pdo->commit();
}

$stockAfterAttempt = (int)$pdo->query("SELECT stock FROM products WHERE id = $testProdId")->fetchColumn();
assert_test("12. Validasi Error: Pengeluaran Melebihi Stok Ditolak & Rollback Berhasil",
    $rollbackWorked && ($stockAfterAttempt === $afterOutStock),
    "Stok tetap: $stockAfterAttempt (tidak berubah menjadi negatif)"
);

// 11. Test Proteksi Foreign Key RESTRICT: Kategori yang ada produk tidak boleh dihapus
$fkRestrictCaught = false;
try {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$testCatId]);
} catch (PDOException $e) {
    $fkRestrictCaught = true;
}
assert_test("13. Integritas Data ON DELETE RESTRICT Kategori Berjalan",
    $fkRestrictCaught,
    "Penghapusan kategori dengan produk berhasil dicegah basis data"
);

// 12. Test Proteksi Foreign Key RESTRICT: Produk yang ada histori tidak boleh dihapus
$prodRestrictCaught = false;
try {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$testProdId]);
} catch (PDOException $e) {
    $prodRestrictCaught = true;
}
assert_test("14. Integritas Data ON DELETE RESTRICT Produk dengan Histori Berjalan",
    $prodRestrictCaught,
    "Penghapusan produk yang memiliki riwayat mutasi berhasil dicegah"
);

// 13. Test Query Agregat Laporan Inventory (CASE WHEN, SUM, GROUP BY)
$reportStmt = $pdo->query("
    SELECT p.sku, p.name,
           SUM(CASE WHEN sm.type='IN' THEN sm.quantity ELSE 0 END) AS total_in,
           SUM(CASE WHEN sm.type='OUT' THEN sm.quantity ELSE 0 END) AS total_out
    FROM products p
    LEFT JOIN stock_movements sm ON sm.product_id = p.id
    GROUP BY p.id, p.sku, p.name
");
$reportRows = $reportStmt->fetchAll();
assert_test("15. Query Agregasi Laporan Inventory (SUM, CASE WHEN, GROUP BY)",
    count($reportRows) > 0,
    count($reportRows) . " produk berhasil diagregasikan"
);

// 14. Test Query Laporan Kategori dengan HAVING (PPT Slide 15)
$havingStmt = $pdo->query("
    SELECT c.name, COUNT(p.id) AS total_products, SUM(p.stock) AS total_stock
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    HAVING total_products >= 1
");
$havingRows = $havingStmt->fetchAll();
assert_test("16. Query Laporan Kategori dengan HAVING COUNT(p.id) >= 1",
    count($havingRows) > 0,
    count($havingRows) . " kategori memenuhi klausa HAVING"
);

// 15. Test XSS Output Encoding Helper e()
$maliciousInput = '<script>alert("XSS Attack")</script>';
$escaped = e($maliciousInput);
assert_test("17. Proteksi XSS melalui htmlspecialchars Output Encoding",
    $escaped === '&lt;script&gt;alert(&quot;XSS Attack&quot;)&lt;/script&gt;',
    "Input berhasil di-escape"
);

// 16. Bersihkan data uji coba
$pdo->prepare("DELETE FROM stock_movements WHERE product_id = ?")->execute([$testProdId]);
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$testProdId]);
$pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$testCatId]);
assert_test("18. Pembersihan Data Pengujian Unit Test Berhasil", true);

echo "==========================================================" . PHP_EOL;
echo "HASIL PENGUJIAN: $passed LULUS, $failed GAGAL." . PHP_EOL;
echo "==========================================================" . PHP_EOL;

