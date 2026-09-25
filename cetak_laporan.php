<?php
/**
 * Cetak Laporan Kas Dosen (Print Friendly & PDF Export)
 * Program Studi Sistem Informasi - Universitas Pamulang
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

$pdo = getDBConnection();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Saldo Awal
$stmtInBefore = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM iuran WHERE status = 'lunas' AND tanggal_bayar < ?");
$stmtInBefore->execute([$startDate]);
$totalInBefore = (float)$stmtInBefore->fetchColumn();

$stmtOutBefore = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE tanggal < ?");
$stmtOutBefore->execute([$startDate]);
$totalOutBefore = (float)$stmtOutBefore->fetchColumn();

$saldoAwal = $totalInBefore - $totalOutBefore;

// Mutasi Periode
$stmtIn = $pdo->prepare("
    SELECT i.tanggal_bayar as tanggal, CONCAT('Iuran Kas: ', d.nama, ' (', i.bulan, '/', i.tahun, ')') as uraian, i.nominal as debit, 0 as kredit, i.metode_bayar as metode
    FROM iuran i JOIN dosen d ON i.dosen_id = d.id
    WHERE i.status = 'lunas' AND i.tanggal_bayar BETWEEN ? AND ?
");
$stmtIn->execute([$startDate, $endDate]);
$mutasiIn = $stmtIn->fetchAll();

$stmtOut = $pdo->prepare("
    SELECT p.tanggal, CONCAT(p.judul, ' (', COALESCE(k.nama_kategori, 'Operasional'), ')') as uraian, 0 as debit, p.nominal as kredit, p.pj_penerima as metode
    FROM pengeluaran p LEFT JOIN kategori_pengeluaran k ON p.kategori_id = k.id
    WHERE p.tanggal BETWEEN ? AND ?
");
$stmtOut->execute([$startDate, $endDate]);
$mutasiOut = $stmtOut->fetchAll();

$allMutasi = array_merge($mutasiIn, $mutasiOut);
usort($allMutasi, function($a, $b) {
    return strcmp($a['tanggal'], $b['tanggal']);
});

$runningSaldo = $saldoAwal;
$totalDebit = 0;
$totalKredit = 0;
foreach ($allMutasi as &$m) {
    $totalDebit += (float)$m['debit'];
    $totalKredit += (float)$m['kredit'];
    $runningSaldo += (float)$m['debit'] - (float)$m['kredit'];
    $m['saldo'] = $runningSaldo;
}
$saldoAkhir = $runningSaldo;

$pengaturan = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Kas Dosen SI UNPAM (<?= htmlspecialchars($startDate) ?> s/d <?= htmlspecialchars($endDate) ?>)</title>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { margin: 0; padding: 10mm; }
    }
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #1a1a1a;
      background-color: #f8fafc;
      margin: 20px auto;
      padding: 20px;
      max-width: 800px;
    }
    .print-box {
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .kop {
      border-bottom: 3px double #000;
      padding-bottom: 12px;
      margin-bottom: 20px;
      text-align: center;
    }
    .kop h2 { margin: 0; font-size: 16px; text-transform: uppercase; color: #0B2F64; }
    .kop h3 { margin: 4px 0; font-size: 14px; text-transform: uppercase; }
    .kop p { margin: 0; font-size: 11px; color: #555; }
    .title-lap {
      text-align: center;
      margin-bottom: 20px;
    }
    .title-lap h4 { margin: 0; font-size: 14px; text-transform: uppercase; }
    .title-lap span { font-size: 11px; color: #666; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; font-size: 11px; }
    th { background-color: #f1f5f9; text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      margin-bottom: 20px;
    }
    .summary-card {
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 10px;
      background: #fafafa;
    }
    .summary-card span { font-size: 10px; color: #64748b; display: block; }
    .summary-card strong { font-size: 12px; color: #0f172a; margin-top: 4px; display: block; }
    .ttd-box {
      margin-top: 40px;
      display: flex;
      justify-content: space-between;
      text-align: center;
    }
    .ttd-col { width: 220px; }
    .ttd-space { height: 70px; }
    .btn-print {
      background-color: #0B2F64;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: bold;
      cursor: pointer;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

  <div class="no-print" style="text-align: right;">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak / Simpan PDF</button>
  </div>

  <div class="print-box">
    <!-- Kop Surat -->
    <div class="kop" style="display: flex; align-items: center; justify-content: center; gap: 18px; text-align: center;">
      <img src="assets/img/logo_unpam.png" alt="Logo UNPAM" style="width: 68px; height: 68px; object-fit: contain;">
      <div>
        <h2>UNIVERSITAS PAMULANG</h2>
        <h3>FAKULTAS ILMU KOMPUTER - PROGRAM STUDI SISTEM INFORMASI</h3>
        <p>Jl. Surya Kencana No. 1, Pamulang Barat, Kota Tangerang Selatan, Banten 15417</p>
      </div>
    </div>

    <!-- Judul Laporan -->
    <div class="title-lap">
      <h4>LAPORAN BUKU KAS UMUM DOSEN</h4>
      <span>Periode: <?= date('d F Y', strtotime($startDate)) ?> s/d <?= date('d F Y', strtotime($endDate)) ?></span>
    </div>

    <!-- Ringkasan -->
    <div class="summary-grid">
      <div class="summary-card">
        <span>Saldo Awal:</span>
        <strong><?= formatRupiah($saldoAwal) ?></strong>
      </div>
      <div class="summary-card">
        <span>Total Pemasukan:</span>
        <strong style="color: #10B981;"><?= formatRupiah($totalDebit) ?></strong>
      </div>
      <div class="summary-card">
        <span>Total Pengeluaran:</span>
        <strong style="color: #EF4444;"><?= formatRupiah($totalKredit) ?></strong>
      </div>
      <div class="summary-card">
        <span>Saldo Akhir:</span>
        <strong style="color: #0B2F64;"><?= formatRupiah($saldoAkhir) ?></strong>
      </div>
    </div>

    <!-- Tabel Mutasi -->
    <table>
      <thead>
        <tr>
          <th class="text-center" style="width: 30px;">No</th>
          <th style="width: 75px;">Tanggal</th>
          <th>Uraian Transaksi</th>
          <th style="width: 70px;">Metode / PJ</th>
          <th class="text-right" style="width: 90px;">Masuk (Debit)</th>
          <th class="text-right" style="width: 90px;">Keluar (Kredit)</th>
          <th class="text-right" style="width: 95px;">Saldo</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="text-center">-</td>
          <td><?= date('d/m/Y', strtotime($startDate)) ?></td>
          <td><strong>SALDO AWAL PERIODE</strong></td>
          <td>-</td>
          <td class="text-right">-</td>
          <td class="text-right">-</td>
          <td class="text-right"><strong><?= formatRupiah($saldoAwal) ?></strong></td>
        </tr>
        <?php if (empty($allMutasi)): ?>
          <tr>
            <td colspan="7" class="text-center" style="padding: 20px; color: #888;">Tidak ada mutasi transaksi pada periode ini.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($allMutasi as $i => $row): ?>
            <tr>
              <td class="text-center"><?= $i + 1 ?></td>
              <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
              <td><?= htmlspecialchars($row['uraian']) ?></td>
              <td><?= htmlspecialchars($row['metode'] ?: '-') ?></td>
              <td class="text-right" style="color: #10B981;"><?= $row['debit'] > 0 ? formatRupiah($row['debit']) : '-' ?></td>
              <td class="text-right" style="color: #EF4444;"><?= $row['kredit'] > 0 ? formatRupiah($row['kredit']) : '-' ?></td>
              <td class="text-right"><strong><?= formatRupiah($row['saldo']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr style="background-color: #f8fafc; font-weight: bold;">
          <td colspan="4" class="text-center">TOTAL MUTASI PERIODE INI</td>
          <td class="text-right" style="color: #10B981;"><?= formatRupiah($totalDebit) ?></td>
          <td class="text-right" style="color: #EF4444;"><?= formatRupiah($totalKredit) ?></td>
          <td class="text-right" style="color: #0B2F64;"><?= formatRupiah($saldoAkhir) ?></td>
        </tr>
      </tfoot>
    </table>

    <!-- Tanda Tangan -->
    <div class="ttd-box">
      <div class="ttd-col">
        <p>Mengetahui,<br><strong>Ketua Program Studi Sistem Informasi</strong></p>
        <div class="ttd-space"></div>
        <p><u>Dr. Ir. Ahmad Sudrajat, M.Kom.</u><br>NIDN: 0408037501</p>
      </div>
      <div class="ttd-col">
        <p>Pamulang, <?= date('d F Y') ?><br><strong>Bendahara Kas Dosen</strong></p>
        <div class="ttd-space"></div>
        <p><u><?= htmlspecialchars($pengaturan['nama_bendahara'] ?? 'Ayu Ernawati') ?></u><br>NIDOS: <?= htmlspecialchars($pengaturan['nidn_bendahara'] ?? '03144') ?></p>
      </div>
    </div>
  </div>

</body>
</html>
