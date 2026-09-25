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

// 1. Hitung Total Pemasukan Sepanjang Waktu
$totalPemasukan = (float)($pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM iuran WHERE status = 'lunas'")->fetchColumn() ?: 0);

// 2. Hitung Total Pengeluaran Sepanjang Waktu
$totalPengeluaran = (float)($pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran")->fetchColumn() ?: 0);

// 3. Saldo Saat Ini
$saldoKas = $totalPemasukan - $totalPengeluaran;

// 4. Pemasukan Bulan Ini
$stmtPemasukanBulan = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM iuran WHERE status = 'lunas' AND bulan = ? AND tahun = ?");
$stmtPemasukanBulan->execute([$bulanIni, $tahunIni]);
$pemasukanBulanIni = (float)$stmtPemasukanBulan->fetchColumn();

// 5. Pengeluaran Bulan Ini
$stmtPengeluaranBulan = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE EXTRACT(MONTH FROM tanggal) = ? AND EXTRACT(YEAR FROM tanggal) = ?");
$stmtPengeluaranBulan->execute([$bulanIni, $tahunIni]);
$pengeluaranBulanIni = (float)$stmtPengeluaranBulan->fetchColumn();

// 6. Data Dosen & Partisipasi Bulan Ini
$totalDosen = (int)$pdo->query("SELECT COUNT(*) FROM dosen WHERE status = 'aktif'")->fetchColumn();

$stmtLunasBulan = $pdo->prepare("SELECT COUNT(DISTINCT dosen_id) FROM iuran WHERE status = 'lunas' AND bulan = ? AND tahun = ?");
$stmtLunasBulan->execute([$bulanIni, $tahunIni]);
$dosenLunasBulanIni = (int)$stmtLunasBulan->fetchColumn();
$dosenBelumLunasBulanIni = max(0, $totalDosen - $dosenLunasBulanIni);

// 7. Pengaturan Dasar (Info Bank, Nominal Iuran)
$pengaturanRows = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);

// 8. 5 Transaksi Terakhir (Kombinasi Pemasukan & Pengeluaran)
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

// 9. Statistik 6 Bulan Terakhir (Chart)
$chartLabels = [];
$chartPemasukan = [];
$chartPengeluaran = [];

$namaBulanIndo = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

for ($i = 5; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $m = (int)date('n', $time);
    $y = (int)date('Y', $time);
    $chartLabels[] = $namaBulanIndo[$m] . ' ' . substr((string)$y, 2);

    // Sum pemasukan
    $stIn = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM iuran WHERE status = 'lunas' AND bulan = ? AND tahun = ?");
    $stIn->execute([$m, $y]);
    $chartPemasukan[] = (float)$stIn->fetchColumn();

    // Sum pengeluaran
    $stOut = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE EXTRACT(MONTH FROM tanggal) = ? AND EXTRACT(YEAR FROM tanggal) = ?");
    $stOut->execute([$m, $y]);
    $chartPengeluaran[] = (float)$stOut->fetchColumn();
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
