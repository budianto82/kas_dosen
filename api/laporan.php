<?php
/**
 * Laporan Keuangan Kas API
 * Kas Dosen UNPAM Prodi Sistem Informasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$pdo = getDBConnection();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// 1. Hitung Saldo Awal (semua pemasukan - pengeluaran sebelum startDate)
$stmtInBefore = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM iuran WHERE status = 'lunas' AND tanggal_bayar < ?");
$stmtInBefore->execute([$startDate]);
$totalInBefore = (float)$stmtInBefore->fetchColumn();

$stmtOutBefore = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE tanggal < ?");
$stmtOutBefore->execute([$startDate]);
$totalOutBefore = (float)$stmtOutBefore->fetchColumn();

$saldoAwal = $totalInBefore - $totalOutBefore;

// 2. Ambil semua transaksi pemasukan pada rentang tanggal
$stmtIn = $pdo->prepare("
    SELECT 
        i.id,
        i.tanggal_bayar as tanggal,
        CONCAT('Iuran Kas: ', d.nama, ' (', i.bulan, '/', i.tahun, ')') as uraian,
        'masuk' as jenis,
        i.nominal as debit,
        0 as kredit,
        i.metode_bayar as metode,
        i.bukti_bayar as bukti
    FROM iuran i
    JOIN dosen d ON i.dosen_id = d.id
    WHERE i.status = 'lunas' AND i.tanggal_bayar BETWEEN ? AND ?
");
$stmtIn->execute([$startDate, $endDate]);
$mutasiIn = $stmtIn->fetchAll();

// 3. Ambil semua transaksi pengeluaran pada rentang tanggal
$stmtOut = $pdo->prepare("
    SELECT 
        p.id,
        p.tanggal,
        CONCAT(p.judul, ' (', COALESCE(k.nama_kategori, 'Lainnya'), ')') as uraian,
        'keluar' as jenis,
        0 as debit,
        p.nominal as kredit,
        p.pj_penerima as metode,
        p.bukti_nota as bukti
    FROM pengeluaran p
    LEFT JOIN kategori_pengeluaran k ON p.kategori_id = k.id
    WHERE p.tanggal BETWEEN ? AND ?
");
$stmtOut->execute([$startDate, $endDate]);
$mutasiOut = $stmtOut->fetchAll();

// 4. Gabungkan dan urutkan transaksi berdasarkan tanggal ASC
$allMutasi = array_merge($mutasiIn, $mutasiOut);
usort($allMutasi, function ($a, $b) {
    if ($a['tanggal'] === $b['tanggal']) {
        return $a['id'] <=> $b['id'];
    }
    return strcmp($a['tanggal'], $b['tanggal']);
});

// 5. Hitung saldo berjalan per baris
$runningSaldo = $saldoAwal;
$totalDebitPeriode = 0;
$totalKreditPeriode = 0;

foreach ($allMutasi as &$m) {
    $totalDebitPeriode += (float)$m['debit'];
    $totalKreditPeriode += (float)$m['kredit'];
    $runningSaldo += (float)$m['debit'] - (float)$m['kredit'];
    $m['saldo_berjalan'] = $runningSaldo;
    $m['debit_formatted'] = $m['debit'] > 0 ? formatRupiah($m['debit']) : '-';
    $m['kredit_formatted'] = $m['kredit'] > 0 ? formatRupiah($m['kredit']) : '-';
    $m['saldo_formatted'] = formatRupiah($runningSaldo);
}

$saldoAkhir = $runningSaldo;

// 6. Rekap per kategori pengeluaran dalam rentang tanggal
$stmtKatBreakdown = $pdo->prepare("
    SELECT k.nama_kategori, COALESCE(SUM(p.nominal), 0) as total
    FROM kategori_pengeluaran k
    LEFT JOIN pengeluaran p ON p.kategori_id = k.id AND p.tanggal BETWEEN ? AND ?
    GROUP BY k.id, k.nama_kategori
    HAVING COALESCE(SUM(p.nominal), 0) > 0
    ORDER BY total DESC
");
$stmtKatBreakdown->execute([$startDate, $endDate]);
$kategoriBreakdown = $stmtKatBreakdown->fetchAll();

// Pengaturan kampus & prodi
$pengaturan = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);

jsonResponse([
    'status' => 'success',
    'filter' => [
        'start_date' => $startDate,
        'end_date' => $endDate
    ],
    'ringkasan' => [
        'saldo_awal' => $saldoAwal,
        'saldo_awal_formatted' => formatRupiah($saldoAwal),
        'total_debit' => $totalDebitPeriode,
        'total_debit_formatted' => formatRupiah($totalDebitPeriode),
        'total_kredit' => $totalKreditPeriode,
        'total_kredit_formatted' => formatRupiah($totalKreditPeriode),
        'saldo_akhir' => $saldoAkhir,
        'saldo_akhir_formatted' => formatRupiah($saldoAkhir),
    ],
    'pengeluaran_per_kategori' => $kategoriBreakdown,
    'mutasi' => $allMutasi,
    'info_lembaga' => [
        'prodi' => $pengaturan['nama_prodi'] ?? 'Sistem Informasi (S1)',
        'fakultas' => $pengaturan['nama_fakultas'] ?? 'Fakultas Ilmu Komputer',
        'kampus' => $pengaturan['nama_kampus'] ?? 'Universitas Pamulang',
        'bendahara' => $pengaturan['nama_bendahara'] ?? 'Siti Rohmah, S.Kom., M.Kom.'
    ]
]);
