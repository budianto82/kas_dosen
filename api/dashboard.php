<?php
/**
 * Dashboard Summary API
 * Kas Dosen UNPAM Prodi Sistem Informasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$pdo = getDBConnection();

$bulanIni = (int)date('n');
$tahunIni = (int)date('Y');

// 1. Pemasukan (Total & Bulan ini) dalam 1 query hemat round-trip
$stmtIn = $pdo->prepare("
    SELECT 
        COALESCE(SUM(nominal), 0) AS total_all,
        COALESCE(SUM(CASE WHEN bulan = ? AND tahun = ? THEN nominal ELSE 0 END), 0) AS total_bulan_ini,
        COUNT(DISTINCT CASE WHEN bulan = ? AND tahun = ? THEN dosen_id ELSE NULL END) AS dosen_lunas_bulan_ini
    FROM iuran WHERE status = 'lunas'
");
$stmtIn->execute([$bulanIni, $tahunIni, $bulanIni, $tahunIni]);
$inData = $stmtIn->fetch() ?: ['total_all' => 0, 'total_bulan_ini' => 0, 'dosen_lunas_bulan_ini' => 0];

$totalPemasukan = (float)$inData['total_all'];
$pemasukanBulanIni = (float)$inData['total_bulan_ini'];
$dosenLunasBulanIni = (int)$inData['dosen_lunas_bulan_ini'];

// 2. Pengeluaran (Total & Bulan ini) dalam 1 query
$stmtOut = $pdo->prepare("
    SELECT 
        COALESCE(SUM(nominal), 0) AS total_all,
        COALESCE(SUM(CASE WHEN EXTRACT(MONTH FROM tanggal) = ? AND EXTRACT(YEAR FROM tanggal) = ? THEN nominal ELSE 0 END), 0) AS total_bulan_ini
    FROM pengeluaran
");
$stmtOut->execute([$bulanIni, $tahunIni]);
$outData = $stmtOut->fetch() ?: ['total_all' => 0, 'total_bulan_ini' => 0];

$totalPengeluaran = (float)$outData['total_all'];
$pengeluaranBulanIni = (float)$outData['total_bulan_ini'];
$saldoKas = $totalPemasukan - $totalPengeluaran;

// 3. Total Dosen Aktif & Jumlah Menunggu Validasi Bukti Transfer
$dosenStats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM dosen WHERE status = 'aktif') AS total_dosen,
        (SELECT COUNT(*) FROM iuran WHERE status = 'pending') AS total_pending_validasi
")->fetch();

$totalDosen = (int)($dosenStats['total_dosen'] ?? 0);
$totalPendingValidasi = (int)($dosenStats['total_pending_validasi'] ?? 0);
$dosenBelumLunasBulanIni = max(0, $totalDosen - $dosenLunasBulanIni);

// 4. Pengaturan Dasar
$pengaturanRows = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);

// 5. 6 Transaksi Terakhir
$recentSql = <<<SQL
    SELECT 
        'masuk' as tipe,
        i.id,
        CONCAT('Iuran Dosen: ', d.nama, ' (', i.bulan, '/', i.tahun, ')') as judul,
        i.nominal,
        i.tanggal_bayar as tanggal,
        i.metode_bayar as keterangan,
        i.bukti_bayar as bukti
    FROM iuran i
    JOIN dosen d ON i.dosen_id = d.id
    WHERE i.status = 'lunas'
    
    UNION ALL
    
    SELECT 
        'keluar' as tipe,
        p.id,
        p.judul,
        p.nominal,
        p.tanggal,
        k.nama_kategori as keterangan,
        p.bukti_nota as bukti
    FROM pengeluaran p
    LEFT JOIN kategori_pengeluaran k ON p.kategori_id = k.id

    ORDER BY tanggal DESC, id DESC
    LIMIT 6
SQL;
$recentTransactions = $pdo->query($recentSql)->fetchAll();

// 6. Statistik 6 Bulan Terakhir (Batch Query - Cepat)
$namaBulanIndo = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

$monthsMap = [];
$startDateLimit = date('Y-m-01', strtotime('-5 months'));

for ($i = 5; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $m = (int)date('n', $time);
    $y = (int)date('Y', $time);
    $key = "{$y}_{$m}";
    $monthsMap[$key] = [
        'label' => $namaBulanIndo[$m] . ' ' . substr((string)$y, 2),
        'in' => 0.0,
        'out' => 0.0
    ];
}

// Ambil sekaligus pemasukan 6 bulan
$stmtBatchIn = $pdo->prepare("
    SELECT bulan, tahun, COALESCE(SUM(nominal), 0) as total 
    FROM iuran 
    WHERE status = 'lunas' AND tanggal_bayar >= ?
    GROUP BY bulan, tahun
");
$stmtBatchIn->execute([$startDateLimit]);
foreach ($stmtBatchIn->fetchAll() as $row) {
    $k = $row['tahun'] . '_' . $row['bulan'];
    if (isset($monthsMap[$k])) {
        $monthsMap[$k]['in'] = (float)$row['total'];
    }
}

// Ambil sekaligus pengeluaran 6 bulan
$stmtBatchOut = $pdo->prepare("
    SELECT EXTRACT(MONTH FROM tanggal) as bulan, EXTRACT(YEAR FROM tanggal) as tahun, COALESCE(SUM(nominal), 0) as total 
    FROM pengeluaran 
    WHERE tanggal >= ?
    GROUP BY EXTRACT(MONTH FROM tanggal), EXTRACT(YEAR FROM tanggal)
");
$stmtBatchOut->execute([$startDateLimit]);
foreach ($stmtBatchOut->fetchAll() as $row) {
    $k = ((int)$row['tahun']) . '_' . ((int)$row['bulan']);
    if (isset($monthsMap[$k])) {
        $monthsMap[$k]['out'] = (float)$row['total'];
    }
}

$chartLabels = [];
$chartPemasukan = [];
$chartPengeluaran = [];
foreach ($monthsMap as $mInfo) {
    $chartLabels[] = $mInfo['label'];
    $chartPemasukan[] = $mInfo['in'];
    $chartPengeluaran[] = $mInfo['out'];
}

jsonResponse([
    'status' => 'success',
    'data' => [
        'saldo_kas' => $saldoKas,
        'saldo_kas_formatted' => formatRupiah($saldoKas),
        'total_pemasukan' => $totalPemasukan,
        'total_pemasukan_formatted' => formatRupiah($totalPemasukan),
        'total_pengeluaran' => $totalPengeluaran,
        'total_pengeluaran_formatted' => formatRupiah($totalPengeluaran),
        'pemasukan_bulan_ini' => $pemasukanBulanIni,
        'pemasukan_bulan_ini_formatted' => formatRupiah($pemasukanBulanIni),
        'pengeluaran_bulan_ini' => $pengeluaranBulanIni,
        'pengeluaran_bulan_ini_formatted' => formatRupiah($pengeluaranBulanIni),
        'total_dosen' => $totalDosen,
        'total_pending_validasi' => $totalPendingValidasi,
        'dosen_lunas_bulan_ini' => $dosenLunasBulanIni,
        'dosen_belum_lunas_bulan_ini' => $dosenBelumLunasBulanIni,
        'persen_lunas' => $totalDosen > 0 ? round(($dosenLunasBulanIni / $totalDosen) * 100) : 0,
        'periode_aktif' => $namaBulanIndo[$bulanIni] . ' ' . $tahunIni,
        'pengaturan' => $pengaturanRows,
        'transaksi_terbaru' => $recentTransactions,
        'chart' => [
            'labels' => $chartLabels,
            'pemasukan' => $chartPemasukan,
            'pengeluaran' => $chartPengeluaran
        ]
    ]
]);
