<?php
/**
 * Kas Dosen UNPAM - Mobile Web Application
 * Program Studi Sistem Informasi - Universitas Pamulang
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';
$pdo = getDBConnection();
$authUser = getAuthUser();
$isLoggedIn = !empty($authUser);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>Kas Dosen SI UNPAM - Mobile App</title>
  <meta name="theme-color" content="#0B2F64">
  <meta name="description" content="Aplikasi Pengelolaan Kas Dosen Program Studi Sistem Informasi Universitas Pamulang">
  
  <!-- PWA Settings -->
  <link rel="manifest" href="manifest.json">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Kas Dosen SI">
  <link rel="icon" type="image/png" href="assets/img/logo_unpam.png">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">

  <!-- Google Fonts & Tailwind CDN -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Lucide Icons & Chart.js -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Custom Styles -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body class="<?= $isLoggedIn ? '' : 'auth-locked' ?>">

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 flex flex-col gap-2 pointer-events-none w-11/12 max-w-sm"></div>

  <!-- Main Mobile Frame Container -->
  <div class="mobile-wrapper">

    <!-- PWA Install Prompt Banner (muncul di mobile browser) -->
    <div id="pwaInstallBanner" class="hidden bg-amber-500 text-slate-900 px-4 py-2.5 flex items-center justify-between text-xs font-semibold shadow-md">
      <div class="flex items-center gap-2">
        <i data-lucide="download" class="w-4 h-4"></i>
        <span>Pasang aplikasi ini di layar HP Anda!</span>
      </div>
      <button onclick="App.installPwa()" class="px-2.5 py-1 bg-slate-900 text-white rounded-lg text-[11px] font-bold">
        Pasang
      </button>
    </div>

    <!-- App Header -->
    <header class="app-header px-3.5 py-3 sticky top-0 z-30 shadow-md">
      <div class="flex items-center justify-between gap-2 max-w-full">
        <!-- Logo & Judul Prodi -->
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
          <div class="w-9 h-9 rounded-xl bg-white p-1 flex items-center justify-center shadow flex-shrink-0 border border-amber-400/40">
            <img src="assets/img/logo_unpam.png" alt="Logo UNPAM" class="w-full h-full object-contain">
          </div>
          <div class="min-w-0 flex-1 overflow-hidden">
            <h1 class="text-xs font-black text-white tracking-wide uppercase truncate leading-tight">KAS DOSEN SI UNPAM</h1>
            <p class="text-[10px] text-amber-300 font-semibold truncate leading-tight">Prodi Sistem Informasi • R2</p>
          </div>
        </div>

        <!-- Tombol Aksi & Avatar -->
        <div class="flex items-center gap-1.5 flex-shrink-0">
          <div id="userHeaderAvatar" class="w-8 h-8 rounded-full bg-amber-400 text-slate-900 font-extrabold text-xs flex items-center justify-center overflow-hidden flex-shrink-0 border border-white/30 shadow cursor-pointer <?= $isLoggedIn ? '' : 'hidden' ?>" onclick="App.openEditProfileModal()" title="Setingan Profil Akun">
            <?php if ($isLoggedIn): ?>
              <?php
                $initials = strtoupper(substr($authUser['nama'] ?? 'SI', 0, 2));
                $foto = $authUser['foto'] ?? '';
                if (!empty($foto)):
                  $src = str_starts_with($foto, 'data:') ? $foto : htmlspecialchars($foto) . '?v=' . time();
              ?>
                <img src="<?= $src ?>" class="w-full h-full object-cover rounded-full" onerror="this.onerror=null; this.parentElement.textContent='<?= $initials ?>'">
              <?php else: ?>
                <?= $initials ?>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <button id="btnSettingsAction" onclick="App.openSettingsModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all flex-shrink-0 hidden" title="Pengaturan Kas (Bendahara)">
            <i data-lucide="settings" class="w-4 h-4"></i>
          </button>
          <button id="authActionBtn" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all flex-shrink-0" title="Login / Logout">
            <i data-lucide="<?= $isLoggedIn ? 'log-out' : 'log-in' ?>" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
    </header>

    <!-- Content Area (Scrollable) -->
    <main class="content-area px-4 pt-4">

      <!-- ==================== TAB 1: BERANDA ==================== -->
      <section id="tab-beranda" class="tab-pane space-y-4">
        
        <!-- Header Profil Pengguna di Dashboard (Tab Beranda) -->
        <div id="dashUserProfileCard" class="bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex items-center justify-between <?= $isLoggedIn ? '' : 'hidden' ?>">
          <div class="flex items-center gap-3 min-w-0">
            <div id="dashUserAvatar" class="w-11 h-11 rounded-full bg-amber-400 text-slate-900 font-extrabold text-sm flex items-center justify-center overflow-hidden border-2 border-white shadow flex-shrink-0 cursor-pointer" onclick="App.openEditProfileModal()" title="Setingan Profil">
              <?php if ($isLoggedIn): ?>
                <?php
                  $initials = strtoupper(substr($authUser['nama'] ?? 'SI', 0, 2));
                  $foto = $authUser['foto'] ?? '';
                  if (!empty($foto)):
                    $src = str_starts_with($foto, 'data:') ? $foto : htmlspecialchars($foto) . '?v=' . time();
                ?>
                  <img src="<?= $src ?>" class="w-full h-full object-cover rounded-full" onerror="this.onerror=null; this.parentElement.textContent='<?= $initials ?>'">
                <?php else: ?>
                  <?= $initials ?>
                <?php endif; ?>
              <?php endif; ?>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1.5 mb-0.5">
                <span id="dashUserRoleBadge" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-900 tracking-wide uppercase">
                  <?= strtoupper($authUser['role'] ?? 'DOSEN') ?>
                </span>
                <span class="text-[10px] text-slate-400 font-medium">Selamat Datang</span>
              </div>
              <h2 id="dashUserNama" class="text-xs sm:text-sm font-bold text-slate-800 truncate leading-tight"><?= htmlspecialchars($authUser['nama'] ?? 'Nama Pengguna') ?></h2>
              <p id="dashUserNidn" class="text-[10px] text-slate-400 font-mono mt-0.5">NIDOS: <?= htmlspecialchars($authUser['nidn'] ?? ($authUser['username'] ?? '-')) ?></p>
            </div>
          </div>
          <button onclick="App.openEditProfileModal()" class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-[11px] font-bold text-slate-700 flex items-center gap-1 transition-colors flex-shrink-0 ml-2" title="Setingan Profil">
            <i data-lucide="user-cog" class="w-3.5 h-3.5 text-blue-900"></i>
            <span>Setingan</span>
          </button>
        </div>

        <!-- Saldo Kas Card -->
        <div class="saldo-card rounded-2xl p-5 text-white shadow-lg">
          <div class="flex items-center justify-between text-xs text-slate-300 mb-1">
            <span class="flex items-center gap-1.5 font-medium">
              <i data-lucide="wallet" class="w-3.5 h-3.5 text-amber-400"></i> Total Saldo Kas Terkini
            </span>
            <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-500/30">
              Kas Aktif
            </span>
          </div>

          <div id="dashSaldo" class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white my-1 font-mono">
            Rp 0
          </div>

          <div class="grid grid-cols-2 gap-3 mt-4 pt-3 border-t border-white/10 text-xs">
            <div>
              <span class="text-[10px] text-slate-300 flex items-center gap-1">
                <i data-lucide="arrow-down-left" class="w-3 h-3 text-emerald-400"></i> Masuk Bulan Ini
              </span>
              <div id="dashPemasukanBulan" class="font-bold text-emerald-400 font-mono mt-0.5">Rp 0</div>
            </div>
            <div>
              <span class="text-[10px] text-slate-300 flex items-center gap-1">
                <i data-lucide="arrow-up-right" class="w-3 h-3 text-rose-400"></i> Keluar Bulan Ini
              </span>
              <div id="dashPengeluaranBulan" class="font-bold text-rose-400 font-mono mt-0.5">Rp 0</div>
            </div>
          </div>
        </div>

        <!-- Banner Khusus Bendahara: Menunggu Validasi Bukti Transfer -->
        <div id="dashPendingValidationAlert" class="hidden bg-amber-50 border border-amber-200 rounded-2xl p-3.5 shadow-sm flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0">
              <i data-lucide="bell-ring" class="w-4 h-4"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-amber-900" id="dashPendingCountText">0 Bukti Transfer Menunggu Validasi</div>
              <div class="text-[10px] text-amber-700">Dosen telah mengirim bukti pembayaran</div>
            </div>
          </div>
          <button onclick="App.switchTab('iuran'); document.getElementById('pendingValidationSection')?.scrollIntoView({behavior: 'smooth'})" class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold rounded-lg shadow-sm whitespace-nowrap">
            Validasi
          </button>
        </div>

        <!-- Partisipasi Iuran Dosen Banner -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
          <div class="flex items-center justify-between mb-2">
            <div>
              <div class="text-xs font-bold text-slate-800">Partisipasi Iuran Dosen</div>
              <div id="dashPersenLunas" class="text-[11px] text-slate-500 font-medium mt-0.5">0% Lunas Bulan Ini</div>
            </div>
            <div id="dashPartisipasi" class="text-xs font-bold text-blue-900 bg-blue-50 px-2.5 py-1 rounded-lg">
              0 dari 0 Dosen
            </div>
          </div>
          <!-- Progress Bar -->
          <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div id="dashProgressBar" class="bg-gradient-to-r from-blue-600 to-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
          </div>
        </div>

        <!-- Quick Actions (Grid 4) -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
          <div class="text-xs font-bold text-slate-800 mb-3">Menu Cepat</div>
          <div class="grid grid-cols-4 gap-2 text-center">
            <button id="quickActionBayar" onclick="App.handleQuickActionBayar()" class="quick-action-btn">
              <div id="quickActionBayarIconWrap" class="quick-action-icon bg-emerald-50 text-emerald-600">
                <i id="quickActionBayarIcon" data-lucide="help-circle" class="w-6 h-6"></i>
              </div>
              <span id="quickActionBayarText">Cara Bayar</span>
            </button>
            <button id="quickActionPengeluaran" onclick="App.handleQuickActionPengeluaran()" class="quick-action-btn">
              <div class="quick-action-icon bg-rose-50 text-rose-600">
                <i data-lucide="minus-circle" class="w-6 h-6"></i>
              </div>
              <span id="quickActionPengeluaranText">Pengeluaran</span>
            </button>
            <button onclick="App.switchTab('iuran')" class="quick-action-btn">
              <div class="quick-action-icon bg-blue-50 text-blue-700">
                <i data-lucide="calendar" class="w-6 h-6"></i>
              </div>
              <span>Matriks</span>
            </button>
            <button onclick="App.switchTab('laporan')" class="quick-action-btn">
              <div class="quick-action-icon bg-amber-50 text-amber-600">
                <i data-lucide="file-text" class="w-6 h-6"></i>
              </div>
              <span>Laporan</span>
            </button>
          </div>

          <!-- Tombol Kirim Bukti Transfer Dosen -->
          <div id="quickDosenTfWrap" class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
            <div class="text-[11px] text-slate-600 font-medium">Sudah transfer iuran kas prodi?</div>
            <button onclick="App.openModalKirimBuktiTf()" class="px-3 py-1.5 bg-blue-900 hover:bg-blue-950 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all">
              <i data-lucide="upload" class="w-3.5 h-3.5 text-amber-400"></i>
              <span>Kirim Bukti TF</span>
            </button>
          </div>
        </div>

        <!-- Rekening Kas Transfer Info Card -->
        <div class="bg-gradient-to-br from-blue-900 to-blue-950 rounded-2xl p-4 text-white shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Rekening Tujuan Kas Prodi</span>
            <i data-lucide="credit-card" class="w-4 h-4 text-amber-400"></i>
          </div>
          <div id="dashRekeningInfo" class="space-y-1">
            <div class="text-xs text-blue-100">Bank BTN: 4401500586720</div>
            <div class="text-[11px] text-amber-300">a.n. Ayu Ernawati, S.Kom., M.Kom.</div>
          </div>
        </div>

        <!-- Grafik Arus Kas -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
          <div class="flex items-center justify-between mb-2">
            <div class="text-xs font-bold text-slate-800">Tren Arus Kas (6 Bulan)</div>
            <span class="text-[10px] text-slate-400">Pemasukan vs Pengeluaran</span>
          </div>
          <div class="relative h-44 w-full">
            <canvas id="keuanganChart"></canvas>
          </div>
        </div>

        <!-- Transaksi Terbaru -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
          <div class="flex items-center justify-between mb-3">
            <div class="text-xs font-bold text-slate-800">Transaksi Terkini</div>
            <button onclick="App.switchTab('laporan')" class="text-[11px] text-blue-600 font-semibold hover:underline">
              Semua Mutasi
            </button>
          </div>
          <div id="dashRecentTxList" class="space-y-2">
            <!-- Rendered by app.js -->
          </div>
        </div>

      </section>

      <!-- ==================== TAB 2: IURAN ==================== -->
      <section id="tab-iuran" class="tab-pane hidden space-y-4">
        
        <!-- Header & View Switcher -->
        <div class="bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl">
            <button id="btnViewMatrix" onclick="App.switchIuranView('matrix')" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-900 text-white shadow-sm">
              Matriks 12 Bulan
            </button>
            <button id="btnViewList" onclick="App.switchIuranView('list')" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600">
              Riwayat Transaksi
            </button>
          </div>

          <div class="flex items-center gap-1.5">
            <button id="btnKirimBuktiTfTab" onclick="App.openModalKirimBuktiTf()" class="px-2.5 py-1.5 bg-blue-900 text-white rounded-xl text-xs font-bold flex items-center gap-1 shadow-sm hover:bg-blue-950">
              <i data-lucide="upload" class="w-3.5 h-3.5 text-amber-400"></i>
              <span>Kirim Bukti TF</span>
            </button>
            <button id="btnCaraBayar" onclick="App.openModal('modalCaraBayar')" class="px-2.5 py-1.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1 hover:bg-slate-200">
              <i data-lucide="info" class="w-3.5 h-3.5"></i>
              <span>Rekening</span>
            </button>
            <button id="btnTambahIuran" onclick="App.openModalBayarIuran()" class="px-3 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold flex items-center gap-1 shadow-sm hover:bg-emerald-700 hidden">
              <i data-lucide="plus" class="w-3.5 h-3.5"></i>
              <span>+ Catat</span>
            </button>
          </div>
        </div>

        <!-- Section Khusus Bendahara: Daftar Bukti Transfer Menunggu Validasi -->
        <div id="pendingValidationSection" class="hidden bg-amber-50/80 border border-amber-200 rounded-2xl p-4 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
              <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wide">Menunggu Validasi Bukti Transfer</h3>
            </div>
            <span id="pendingValidationBadgeCount" class="text-[10px] font-bold bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full">0 Dosen</span>
          </div>
          <div id="pendingValidationList" class="space-y-2.5">
            <!-- Rendered by JS -->
          </div>
        </div>

        <!-- Dynamic Content Area (Matrix / List) -->
        <div id="iuranContentArea">
          <!-- Rendered by JS -->
        </div>

      </section>

      <!-- ==================== TAB 3: PENGELUARAN ==================== -->
      <section id="tab-pengeluaran" class="tab-pane hidden space-y-4">
        
        <!-- Top Summary Card -->
        <div class="bg-gradient-to-r from-rose-500 to-rose-700 text-white rounded-2xl p-4 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] text-rose-100">Total Pengeluaran Tahun Ini</span>
            <div id="totalPengeluaranHeader" class="text-xl font-bold font-mono mt-0.5">Rp 0</div>
          </div>
          <button id="btnCatatPengeluaran" onclick="App.openModal('modalTambahPengeluaran')" class="px-3 py-2 bg-white text-rose-700 rounded-xl text-xs font-bold shadow hover:bg-rose-50 flex items-center gap-1.5 hidden">
            <i data-lucide="plus" class="w-4 h-4"></i> Catat Keluar
          </button>
        </div>

        <!-- Filter Kategori -->
        <div class="bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex items-center gap-2">
          <i data-lucide="filter" class="w-4 h-4 text-slate-400"></i>
          <select id="filterKatPengeluaran" onchange="App.loadPengeluaran()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-900">
            <option value="">Semua Kategori</option>
          </select>
        </div>

        <!-- List Pengeluaran -->
        <div id="pengeluaranListContainer" class="space-y-2.5">
          <!-- Rendered by JS -->
        </div>

      </section>

      <!-- ==================== TAB 4: LAPORAN ==================== -->
      <section id="tab-laporan" class="tab-pane hidden space-y-4">
        
        <!-- Filter Tanggal Buku Kas -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
          <div class="text-xs font-bold text-slate-800 mb-2.5 flex items-center gap-1.5">
            <i data-lucide="calendar-range" class="w-4 h-4 text-blue-900"></i> Periode Laporan Kas
          </div>
          <div class="grid grid-cols-2 gap-2 mb-3">
            <div>
              <label class="block text-[10px] text-slate-500 font-medium mb-1">Tanggal Mulai</label>
              <input type="date" id="laporanStartDate" value="<?= date('Y-m-01') ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700">
            </div>
            <div>
              <label class="block text-[10px] text-slate-500 font-medium mb-1">Tanggal Akhir</label>
              <input type="date" id="laporanEndDate" value="<?= date('Y-m-t') ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-700">
            </div>
          </div>
          <div class="flex gap-2">
            <button onclick="App.loadLaporan()" class="flex-1 py-2 bg-blue-900 text-white rounded-xl text-xs font-bold hover:bg-blue-950 transition-colors">
              Tampilkan Laporan
            </button>
            <button onclick="App.printLaporan()" class="px-3 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200 flex items-center gap-1">
              <i data-lucide="printer" class="w-3.5 h-3.5"></i> Cetak PDF
            </button>
          </div>
        </div>

        <!-- Ringkasan Mutasi Kas (4 Kotak) -->
        <div class="grid grid-cols-2 gap-2.5">
          <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
            <div class="text-[10px] text-slate-400">Saldo Awal Periode</div>
            <div id="lapSaldoAwal" class="text-xs font-bold text-slate-800 font-mono mt-0.5">Rp 0</div>
          </div>
          <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
            <div class="text-[10px] text-slate-400">Total Kas Masuk (+)</div>
            <div id="lapTotalMasuk" class="text-xs font-bold text-emerald-600 font-mono mt-0.5">Rp 0</div>
          </div>
          <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
            <div class="text-[10px] text-slate-400">Total Pengeluaran (-)</div>
            <div id="lapTotalKeluar" class="text-xs font-bold text-rose-600 font-mono mt-0.5">Rp 0</div>
          </div>
          <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
            <div class="text-[10px] text-slate-400">Saldo Akhir Kumulatif</div>
            <div id="lapSaldoAkhir" class="text-xs font-bold text-blue-900 font-mono mt-0.5">Rp 0</div>
          </div>
        </div>

        <!-- Tabel Buku Kas Umum -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
          <div class="text-xs font-bold text-slate-800 mb-3 flex items-center justify-between">
            <span>Buku Kas Umum (Mutasi)</span>
            <span class="text-[10px] text-slate-400">Transparansi Kas Prodi</span>
          </div>
          <div id="laporanMutasiList">
            <!-- Rendered by JS -->
          </div>
        </div>

      </section>

      <!-- ==================== TAB 5: DOSEN ==================== -->
      <section id="tab-dosen" class="tab-pane hidden space-y-4">
        
        <!-- Kartu Profil Dosen yang Sedang Login -->
        <div id="userProfileCard" class="bg-gradient-to-br from-blue-900 via-blue-950 to-slate-900 text-white rounded-2xl p-4 shadow-sm border border-blue-800/40 space-y-3 <?= $isLoggedIn ? '' : 'hidden' ?>">
          <div class="flex items-center gap-3.5">
            <div class="relative flex-shrink-0">
              <div id="userProfilePhotoPreview" class="w-14 h-14 rounded-full bg-amber-400 text-slate-950 font-extrabold text-base flex items-center justify-center overflow-hidden border-2 border-white/30 shadow-md">
                <?php if ($isLoggedIn): ?>
                  <?php
                    $initials = strtoupper(substr($authUser['nama'] ?? 'SI', 0, 2));
                    $foto = $authUser['foto'] ?? '';
                    if (!empty($foto)):
                      $src = str_starts_with($foto, 'data:') ? $foto : htmlspecialchars($foto) . '?v=' . time();
                  ?>
                    <img src="<?= $src ?>" class="w-full h-full object-cover rounded-full" onerror="this.onerror=null; this.parentElement.textContent='<?= $initials ?>'">
                  <?php else: ?>
                    <?= $initials ?>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <label for="inputUploadFotoProfil" class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-900 flex items-center justify-center cursor-pointer shadow-md transition-transform active:scale-95" title="Ganti Foto Profil">
                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
              </label>
              <input type="file" id="inputUploadFotoProfil" accept="image/*" class="hidden" onchange="App.uploadFotoProfil(this)">
            </div>
            <div class="flex-1 min-w-0">
              <span id="userProfileBadge" class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-400 text-slate-900 tracking-wider mb-1">
                <?= strtoupper($authUser['role'] ?? 'BENDAHARA') ?>
              </span>
              <h3 id="userProfileNama" class="text-xs sm:text-sm font-bold text-white truncate leading-tight"><?= htmlspecialchars($authUser['nama'] ?? 'Nama Dosen') ?></h3>
              <p id="userProfileNidn" class="text-[11px] text-blue-200 font-mono mt-0.5">NIDOS: <?= htmlspecialchars($authUser['nidn'] ?? ($authUser['username'] ?? '-')) ?></p>
            </div>
          </div>
          <div class="pt-2.5 border-t border-white/10 flex items-center justify-between text-xs">
            <button onclick="App.logout()" type="button" class="text-[11px] text-rose-300 hover:text-rose-200 font-bold flex items-center gap-1.5 transition-colors">
              <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
              <span>Keluar</span>
            </button>
            <div class="flex items-center gap-2">
              <label for="inputUploadFotoProfil" class="text-[11px] text-amber-300 hover:text-amber-200 font-bold cursor-pointer flex items-center gap-1 px-2 py-1 bg-white/10 hover:bg-white/15 rounded-lg transition-colors">
                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                <span>Ganti Foto</span>
              </label>
              <button onclick="App.openEditProfileModal()" type="button" class="px-2.5 py-1 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-[11px] rounded-lg flex items-center gap-1 shadow transition-all active:scale-95">
                <i data-lucide="user-cog" class="w-3.5 h-3.5"></i>
                <span>Setingan Profil</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Search & Add Bar -->
        <div class="bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex items-center gap-2">
          <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="searchDosenInput" oninput="App.loadDosenList()" placeholder="Cari Nama / NIDOS Dosen..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-900">
          </div>
          <button id="btnTambahDosen" onclick="App.openModal('modalTambahDosen')" class="px-3 py-1.5 bg-blue-900 hover:bg-blue-950 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 flex-shrink-0 shadow-sm transition-all hidden" title="Tambah Dosen Baru">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Tambah Dosen</span>
          </button>
        </div>

        <!-- List Dosen -->
        <div id="dosenListContainer" class="space-y-2.5">
          <!-- Rendered by JS -->
        </div>

      </section>

    </main>

    <!-- Bottom Navigation Bar (Android Mobile Style) -->
    <nav class="bottom-nav">
      <div class="nav-item active" data-tab="beranda">
        <i data-lucide="home" class="w-5 h-5 mb-0.5"></i>
        <span>Beranda</span>
      </div>
      <div class="nav-item" data-tab="iuran">
        <i data-lucide="wallet" class="w-5 h-5 mb-0.5"></i>
        <span>Iuran</span>
      </div>
      <div class="nav-item" data-tab="pengeluaran">
        <i data-lucide="receipt" class="w-5 h-5 mb-0.5"></i>
        <span>Pengeluaran</span>
      </div>
      <div class="nav-item" data-tab="laporan">
        <i data-lucide="file-spreadsheet" class="w-5 h-5 mb-0.5"></i>
        <span>Laporan</span>
      </div>
      <div class="nav-item" data-tab="dosen">
        <i data-lucide="users" class="w-5 h-5 mb-0.5"></i>
        <span>Dosen</span>
      </div>
    </nav>

  </div>

  <!-- ==================== MODALS (BOTTOM SHEETS) ==================== -->

  <!-- Modal 1: Login -->
  <div id="modalLogin" class="modal-overlay modal-centered <?= $isLoggedIn ? '' : 'active' ?>">
    <div class="modal-dialog">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2.5">
          <img src="assets/img/logo_unpam.png" alt="UNPAM" class="w-8 h-8 object-contain">
          <div>
            <h3 class="text-sm font-bold text-slate-900 leading-tight">Masuk Akun Kas Dosen</h3>
            <p class="text-[10px] text-slate-500">Sistem Informasi Universitas Pamulang</p>
          </div>
        </div>
        <button onclick="App.closeModal('modalLogin')" class="btn-close-login text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100" title="Tutup">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <!-- Notice Akses Terkunci Sebelum Login -->
      <div class="bg-amber-50 border border-amber-200/80 rounded-xl p-2.5 mb-3.5 flex items-center gap-2.5 shadow-sm">
        <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center flex-shrink-0 text-xs shadow-sm">
          <i data-lucide="lock" class="w-4 h-4"></i>
        </div>
        <div class="text-[11px] text-amber-950 font-medium leading-tight">
          Silakan <strong>masuk dengan NIDOS</strong> Anda untuk membuka dan melihat dashboard kas.
        </div>
      </div>

      <form onsubmit="App.login(event)" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Username / NIDOS Dosen</label>
          <input type="text" name="username" required placeholder="Masukkan NIDOS Anda" autocapitalize="none" autocorrect="off" spellcheck="false" autocomplete="username" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Kata Sandi (Password)</label>
            <button type="button" onclick="const p=this.closest('div').nextElementSibling.querySelector('input'); p.type = p.type === 'password' ? 'text' : 'password'; this.querySelector('span').textContent = p.type === 'password' ? 'Lihat' : 'Sembunyi';" class="text-[10px] text-blue-900 font-semibold hover:underline cursor-pointer">
              <span>Lihat</span> Sandi
            </button>
          </div>
          <div class="relative">
            <input type="password" name="password" required placeholder="Kata sandi akun" autocomplete="current-password" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
        </div>
        <div class="p-2.5 bg-blue-50 text-blue-900 rounded-xl text-[11px] leading-relaxed">
          💡 <strong>Petunjuk Login Dosen:</strong><br>
          Gunakan <strong>NIDOS</strong> Anda sebagai Username.<br>
          <span class="text-[10px] text-slate-600">(Password default: NIDOS Anda atau <code>unpam123</code>)</span>
        </div>
        <button type="submit" class="w-full py-2.5 bg-blue-900 text-white rounded-xl text-xs font-bold hover:bg-blue-950 transition-colors shadow-md flex items-center justify-center gap-1.5">
          <i data-lucide="log-in" class="w-4 h-4"></i>
          <span>Masuk Sekarang</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Modal: Tata Cara Pembayaran Kas (Untuk Dosen / Civitas SI) -->
  <div id="modalCaraBayar" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-900 flex items-center justify-center flex-shrink-0">
            <i data-lucide="help-circle" class="w-5 h-5"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-800">Tata Cara Pembayaran Kas</h3>
            <p class="text-[10px] text-slate-500">Prodi Sistem Informasi Universitas Pamulang</p>
          </div>
        </div>
        <button onclick="App.closeModal('modalCaraBayar')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <div class="space-y-3">
        <!-- Notice Khusus Bendahara -->
        <div class="p-3 bg-amber-50 border border-amber-200/80 rounded-xl text-xs text-amber-900 leading-relaxed">
          <div class="flex items-center gap-1.5 font-bold mb-1 text-amber-800">
            <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600"></i>
            <span>Pencatatan & Validasi Kas:</span>
          </div>
          Pencatatan dan validasi pembayaran kas ke dalam aplikasi <strong>hanya dilakukan oleh Bendahara</strong>. Dosen/Civitas SI dapat menyetor iuran melalui <strong>Transfer Bank</strong> atau <strong>Bayar Tunai</strong> langsung ke Bendahara.
        </div>

        <!-- Metode 1: Transfer Bank -->
        <div class="p-3.5 bg-gradient-to-br from-blue-900 to-blue-950 text-white rounded-2xl shadow-sm space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span id="modalBankTitle" class="font-bold text-amber-400 uppercase tracking-wide flex items-center gap-1.5">
              <i data-lucide="credit-card" class="w-4 h-4"></i> Transfer Bank (BTN)
            </span>
            <span id="modalRekeningNominalBadge" class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-semibold">Rp 20.000 / bln</span>
          </div>

          <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/10 flex items-center justify-between">
            <div>
              <div id="modalBankLabel" class="text-[10px] text-blue-200">Nomor Rekening BTN:</div>
              <div id="rekeningModalText" class="text-base font-bold font-mono tracking-wider text-white">4401500586720</div>
              <div id="modalAtasNamaText" class="text-[10px] text-amber-300 font-medium">a.n. Ayu Ernawati, S.Kom., M.Kom.</div>
            </div>
            <button onclick="App.copyRekening()" class="px-3 py-1.5 bg-amber-400 hover:bg-amber-300 text-slate-900 rounded-lg text-xs font-bold flex items-center gap-1 shadow transition-colors" title="Salin Nomor Rekening">
              <i data-lucide="copy" class="w-3.5 h-3.5"></i> Salin
            </button>
          </div>
        </div>

        <!-- Metode 2: Bayar Tunai / Manual -->
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
          <div class="font-bold text-slate-800 flex items-center gap-1.5">
            <i data-lucide="banknote" class="w-4 h-4 text-emerald-600"></i>
            <span>Metode Pembayaran Tunai / Manual:</span>
          </div>
          <p class="text-slate-600 text-[11px] leading-relaxed">
            Dosen dapat menyerahkan iuran tunai langsung kepada Bendahara (<strong id="modalNamaBendahara">Ibu Ayu Ernawati, S.Kom., M.Kom.</strong>) di <strong>Ruang R2</strong>.
          </p>
        </div>

        <!-- Tombol Konfirmasi WhatsApp -->
        <a id="btnWaKonfirmasi" href="https://wa.me/6281298765432?text=Assalamu%27alaikum%20Ibu%20Bendahara%2C%20saya%20sudah%20melakukan%20pembayaran%20iuran%20kas%20dosen%20SI%20UNPAM.%20Berikut%20bukti%20transfernya%3A" target="_blank" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-md transition-colors">
          <i data-lucide="message-circle" class="w-4 h-4"></i>
          <span>Kirim Bukti Transfer ke WhatsApp Bendahara</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Modal 2: Bayar Iuran Kas (Khusus Bendahara) -->
  <div id="modalBayarIuran" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-sm font-bold text-slate-800">Pencatatan Iuran Kas (Khusus Bendahara)</h3>
          <p class="text-[10px] text-emerald-600 font-medium">Input & validasi iuran dosen yang telah membayar</p>
        </div>
        <button onclick="App.closeModal('modalBayarIuran')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <form id="formBayarIuran" onsubmit="App.submitBayarIuran(event)" enctype="multipart/form-data" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Dosen *</label>
          <select id="formIuranDosen" name="dosen_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
            <!-- Populated by JS -->
          </select>
        </div>
        
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-semibold text-slate-700">Bulan Iuran (Bisa pilih lebih dari 1) *</label>
            <div class="flex items-center gap-1">
              <button type="button" onclick="App.quickSelectBulan('now')" class="px-2 py-0.5 text-[10px] font-bold bg-blue-50 text-blue-900 rounded-lg hover:bg-blue-100 transition-colors">Bulan Ini</button>
              <button type="button" onclick="App.quickSelectBulan('all')" class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors">Semua</button>
              <button type="button" onclick="App.quickSelectBulan('clear')" class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-500 rounded-lg hover:bg-rose-50 hover:text-rose-600 transition-colors">Hapus</button>
            </div>
          </div>
          <div id="formIuranBulanCheckboxContainer" class="grid grid-cols-4 gap-1.5 text-xs text-slate-700">
            <?php
            $bulanNama = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            $blnNow = (int)date('n');
            foreach ($bulanNama as $num => $nama):
            ?>
              <label class="flex items-center gap-1 p-1.5 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-blue-50 transition-colors">
                <input type="checkbox" name="bulan[]" value="<?= $num ?>" <?= $num === $blnNow ? 'checked' : '' ?> class="rounded text-blue-900 bulan-checkbox">
                <span class="text-[11px] font-medium"><?= $nama ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun *</label>
            <input type="number" id="formIuranTahun" name="tahun" value="<?= date('Y') ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal per Bulan (Rp) *</label>
            <input type="number" name="nominal" id="formIuranNominal" value="30000" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Bayar *</label>
            <input type="date" id="formIuranTanggalBayar" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Bayar *</label>
            <select name="metode_bayar" id="formIuranMetode" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium">
              <option value="transfer">Transfer Bank</option>
              <option value="tunai">Tunai / Cash</option>
              <option value="qris">QRIS</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Bukti Transfer / Kuitansi (Opsional)</label>
          <input type="file" id="formIuranBuktiFile" name="bukti_bayar" accept="image/*" onchange="App.handleIuranBuktiFileChange(this)" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100">
          <div id="formIuranBuktiPreviewWrap" class="mt-2 hidden">
            <img id="formIuranBuktiPreviewImg" src="" alt="Pratinjau Bukti" class="max-h-36 rounded-lg border border-slate-200 object-contain mx-auto shadow-sm">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Tambahan</label>
          <input type="text" id="formIuranKeterangan" name="keterangan" placeholder="Contoh: Titip lewat Pak Budi / Iuran rutin" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
        </div>

        <button type="submit" id="btnSubmitBayarIuran" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5 mt-2">
          <i data-lucide="check-circle" class="w-4 h-4"></i>
          <span>Simpan Iuran Kas</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Modal 3: Tambah Pengeluaran -->
  <div id="modalTambahPengeluaran" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-slate-800">Catat Pengeluaran Kas Baru</h3>
        <button onclick="App.closeModal('modalTambahPengeluaran')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <form onsubmit="App.submitPengeluaran(event)" enctype="multipart/form-data" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Keperluan / Judul Pengeluaran *</label>
          <input type="text" name="judul" required placeholder="Contoh: Snack Rapat Pleno Dosen" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal (Rp) *</label>
            <input type="number" name="nominal" required placeholder="Contoh: 150000" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori *</label>
            <select id="formKatPengeluaran" name="kategori_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
              <!-- Populated by JS -->
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal *</label>
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">PJ / Penerima Dana</label>
            <input type="text" name="pj_penerima" placeholder="Nama penerima / PJ" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Foto Bukti Nota / Kuitansi</label>
          <input type="file" name="bukti_nota" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-900 hover:file:bg-rose-100">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Rincian Belanja</label>
          <textarea name="keterangan" rows="2" placeholder="Tuliskan rincian item jika ada..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs"></textarea>
        </div>

        <button type="submit" class="w-full py-2.5 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition-colors shadow-md mt-2">
          Simpan Pengeluaran Kas
        </button>
      </form>
    </div>
  </div>

  <!-- Modal 4: Tambah Dosen Baru -->
  <div id="modalTambahDosen" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-slate-800">Tambah Dosen Prodi SI UNPAM</h3>
        <button onclick="App.closeModal('modalTambahDosen')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <form onsubmit="App.submitTambahDosen(event)" class="space-y-3">
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">NIDOS *</label>
            <input type="text" name="nidn" required placeholder="0412345678" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Gelar (Opsional)</label>
            <input type="text" name="gelar" placeholder="M.Kom. / Ph.D" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
          <input type="text" name="nama" required placeholder="Contoh: Muhammad Ilham" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
        </div>

        <input type="hidden" name="jabatan" value="Dosen Tetap">
        <input type="hidden" name="no_hp" value="">
        <input type="hidden" name="email" value="">

        <button type="submit" class="w-full py-2.5 bg-blue-900 text-white rounded-xl text-xs font-bold hover:bg-blue-950 transition-colors shadow-md mt-2">
          Tambahkan Dosen
        </button>
      </form>
    </div>
  </div>

  <!-- Modal 5: Detail Dosen & Riwayat -->
  <div id="modalDosenDetail" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
          <div class="relative flex-shrink-0">
            <div id="modalDetailDosenFoto" class="w-12 h-12 rounded-full bg-blue-900 text-amber-400 font-bold text-sm flex items-center justify-center overflow-hidden border border-slate-200 shadow-sm">
              <!-- Foto / Inisial -->
            </div>
            <label id="btnUploadDetailFoto" for="inputUploadDetailFoto" class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-amber-400 text-slate-900 flex items-center justify-center cursor-pointer shadow hover:bg-amber-300" title="Ganti Foto Dosen">
              <i data-lucide="camera" class="w-3 h-3"></i>
            </label>
            <input type="file" id="inputUploadDetailFoto" accept="image/*" class="hidden" onchange="App.uploadFotoDosenDetail(this)">
          </div>
          <div>
            <h3 id="modalDetailDosenTitle" class="text-sm font-bold text-slate-800">Detail Dosen</h3>
            <p id="modalDetailDosenNidn" class="text-[11px] text-slate-500 font-mono">NIDOS: -</p>
          </div>
        </div>
        <button onclick="App.closeModal('modalDosenDetail')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <div class="mt-3">
        <h4 class="text-xs font-bold text-slate-700 mb-2">Riwayat Iuran Kas Dosen</h4>
        <div id="modalDetailDosenRiwayat" class="space-y-2 max-h-60 overflow-y-auto">
          <!-- Rendered by JS -->
        </div>
      </div>

      <!-- Tombol Aksi Tambahan (Khusus Bendahara) -->
      <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
        <button id="btnCatatIuranDosenDetail" onclick="App.openModalBayarIuran(App.activeDetailDosenId)" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1.5 shadow-sm hidden">
          <i data-lucide="plus-circle" class="w-4 h-4"></i>
          <span>+ Catat Iuran Dosen Ini</span>
        </button>
        <button id="btnHapusDosenDetail" onclick="App.deleteActiveDosen()" class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1.5 hidden">
          <i data-lucide="trash-2" class="w-4 h-4"></i>
          <span>Hapus Dosen dari Sistem</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Modal 6: Template WhatsApp Reminder -->
  <div id="modalWaReminder" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2 text-emerald-600">
          <i data-lucide="message-circle" class="w-5 h-5"></i>
          <h3 class="text-sm font-bold text-slate-800">Kirim Pengingat Kas via WhatsApp</h3>
        </div>
        <button onclick="App.closeModal('modalWaReminder')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pratinjau Pesan Resmi</label>
          <textarea id="waTemplatePreview" rows="7" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-sans text-slate-700 leading-relaxed"></textarea>
        </div>
        <a id="waSendActionBtn" href="#" target="_blank" class="w-full py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition-colors shadow-md flex items-center justify-center gap-2">
          <i data-lucide="send" class="w-4 h-4"></i> Buka WhatsApp & Kirim Pesan
        </a>
      </div>
    </div>
  </div>

  <!-- Modal 7: Preview Gambar / Bukti -->
  <div id="modalImageViewer" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-3">
        <h3 id="imageViewerTitle" class="text-sm font-bold text-slate-800">Bukti Transaksi</h3>
        <button onclick="App.closeModal('modalImageViewer')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="bg-slate-100 rounded-xl p-2 flex items-center justify-center max-h-96 overflow-hidden">
        <img id="imageViewerContent" src="" alt="Bukti" class="max-h-80 w-auto rounded-lg object-contain shadow">
      </div>
    </div>
  </div>

  <!-- Modal 8: Kirim Bukti Transfer Iuran (Untuk Dosen Biasa / Terbuka) -->
  <div id="modalKirimBuktiTf" class="modal-overlay">
    <div class="modal-sheet max-h-[90vh] overflow-y-auto">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-800">Kirim Bukti Transfer Iuran</h3>
          <p class="text-[10px] text-blue-600 font-medium">Unggah bukti transfer untuk divalidasi Bendahara</p>
        </div>
        <button onclick="App.closeModal('modalKirimBuktiTf')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <!-- Info Rekening Singkat -->
      <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl mb-3 text-xs space-y-1">
        <div class="flex items-center justify-between text-[11px] font-bold text-blue-900">
          <span>Rekening Tujuan:</span>
          <span id="kirimTfNominalBadge" class="bg-blue-600 text-white px-2 py-0.5 rounded-full text-[10px] font-mono">Rp 20.000 / bln</span>
        </div>
        <div class="font-mono text-slate-800 font-bold" id="kirimTfBankRek">Bank BTN: 4401500586720</div>
        <div class="text-[11px] text-slate-600" id="kirimTfAtasNama">a.n. Ayu Ernawati, S.Kom., M.Kom.</div>
      </div>

      <form onsubmit="App.submitBuktiTransfer(event)" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Dosen Pengirim *</label>
          <select id="kirimTfDosenSelect" name="dosen_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
            <!-- Populated by JS -->
          </select>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Bulan Iuran *</label>
            <select id="kirimTfBulanSelect" name="bulan" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
              <option value="1">Januari</option>
              <option value="2">Februari</option>
              <option value="3">Maret</option>
              <option value="4">April</option>
              <option value="5">Mei</option>
              <option value="6">Juni</option>
              <option value="7">Juli</option>
              <option value="8">Agustus</option>
              <option value="9">September</option>
              <option value="10">Oktober</option>
              <option value="11">November</option>
              <option value="12">Desember</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun *</label>
            <input type="number" id="kirimTfTahunInput" name="tahun" value="<?= date('Y') ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
        </div>

        <!-- Upload Foto Bukti -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Bukti Transfer (JPG/PNG/WebP) *</label>
          <div class="border-2 border-dashed border-blue-200 bg-blue-50/30 rounded-xl p-3 text-center hover:bg-blue-50/60 cursor-pointer relative transition-all" onclick="document.getElementById('inputBuktiTfFile').click()">
            <input type="file" id="inputBuktiTfFile" accept="image/*" class="hidden" onchange="App.handleBuktiTfFileChange(this)">
            <div id="buktiTfPlaceholder">
              <i data-lucide="image-plus" class="w-7 h-7 mx-auto text-blue-600 mb-1"></i>
              <span class="text-xs text-slate-700 font-semibold block">Pilih / Ambil Foto Bukti Transfer</span>
              <span class="text-[10px] text-slate-400">JPG, PNG, atau WebP kamera HP</span>
            </div>
            <div id="buktiTfPreviewWrap" class="hidden">
              <img id="buktiTfPreviewImg" src="" alt="Pratinjau" class="max-h-48 mx-auto rounded-lg shadow-sm object-contain mb-1">
              <span class="text-[10px] text-blue-600 font-semibold block">Ketuk untuk ganti foto</span>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
          <input type="text" name="keterangan" placeholder="Contoh: Transfer via m-Banking BCA/Mandiri" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
        </div>

        <button type="submit" id="btnSubmitBuktiTf" class="w-full py-2.5 bg-blue-900 hover:bg-blue-950 text-white rounded-xl text-xs font-bold transition-colors shadow-md flex items-center justify-center gap-1.5 mt-2">
          <i data-lucide="send" class="w-4 h-4"></i>
          <span>Kirim Bukti Transfer</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Modal 9: Pengaturan Kas (Khusus Bendahara) -->
  <div id="modalPengaturanKas" class="modal-overlay">
    <div class="modal-sheet max-h-[90vh] overflow-y-auto">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-sm font-bold text-slate-800">Pengaturan Kas Prodi SI</h3>
          <p class="text-[10px] text-amber-600 font-medium">Atur tarif iuran bulanan dan nomor rekening bendahara</p>
        </div>
        <button onclick="App.closeModal('modalPengaturanKas')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <form onsubmit="App.submitSettings(event)" class="space-y-3">
        <!-- Nominal Iuran Bulanan -->
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
          <label class="block text-xs font-bold text-amber-900 mb-1">Tarif Iuran Bulanan per Dosen (Rp) *</label>
          <input type="number" id="settingNominalIuran" name="nominal_iuran_bulanan" required placeholder="20000" class="w-full px-3 py-2 bg-white border border-amber-300 rounded-xl text-xs font-mono font-bold text-slate-800 focus:ring-2 focus:ring-blue-900 focus:outline-none">
          <span class="text-[10px] text-amber-700 mt-1 block">Nominal ini otomatis dipakai saat dosen mengirim bukti dan validasi iuran.</span>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Bank *</label>
            <input type="text" id="settingNamaBank" name="nama_bank" required placeholder="Bank BTN" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rekening *</label>
            <input type="text" id="settingNoRek" name="nomor_rekening" required placeholder="4401500586720" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Atas Nama Rekening *</label>
          <input type="text" id="settingAtasNama" name="atas_nama" required placeholder="Ayu Ernawati, S.Kom., M.Kom." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Bendahara *</label>
            <input type="text" id="settingNamaBendahara" name="nama_bendahara" required placeholder="Ayu Ernawati, S.Kom., M.Kom." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">No. WA Bendahara *</label>
            <input type="text" id="settingKontakBendahara" name="kontak_bendahara" required placeholder="6281298765432" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
        </div>

        <button type="submit" id="btnSubmitSettings" class="w-full py-2.5 bg-blue-900 hover:bg-blue-950 text-white rounded-xl text-xs font-bold transition-colors shadow-md flex items-center justify-center gap-1.5 mt-2">
          <i data-lucide="save" class="w-4 h-4"></i>
          <span>Simpan Perubahan Pengaturan</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Modal 10: Setingan Profil & Akun -->
  <div id="modalEditProfile" class="modal-overlay">
    <div class="modal-sheet max-h-[90vh] overflow-y-auto">
      <div class="sheet-handle"></div>
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
            <i data-lucide="user-cog" class="w-4 h-4 text-blue-900"></i>
            <span>Setingan Profil Akun</span>
          </h3>
          <p class="text-[10px] text-slate-500 font-medium">Perbarui biodata, foto profil, dan kata sandi akun Anda</p>
        </div>
        <button onclick="App.closeModal('modalEditProfile')" class="text-slate-400 hover:text-slate-600">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <form onsubmit="App.submitUpdateProfile(event)" class="space-y-3.5">
        <!-- Foto Profil Avatar Preview & Quick Change -->
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-3.5">
          <div class="relative flex-shrink-0">
            <div id="modalProfilePhotoPreview" class="w-16 h-16 rounded-full bg-amber-400 text-slate-900 font-extrabold text-base flex items-center justify-center overflow-hidden border-2 border-white shadow">
              <!-- Avatar or photo -->
            </div>
            <label for="inputModalUploadFoto" class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-blue-900 hover:bg-blue-950 text-white flex items-center justify-center cursor-pointer shadow transition-transform active:scale-95" title="Pilih Foto Baru">
              <i data-lucide="camera" class="w-3.5 h-3.5"></i>
            </label>
            <input type="file" id="inputModalUploadFoto" name="foto_profil" accept="image/*" class="hidden" onchange="App.previewModalProfilePhoto(this)">
          </div>
          <div class="flex-1 min-w-0">
            <span id="modalProfileBadge" class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-900 tracking-wider mb-1">
              BENDAHARA
            </span>
            <label for="inputModalUploadFoto" class="block text-xs font-bold text-blue-900 hover:text-blue-950 cursor-pointer">
              Ganti Foto Profil
            </label>
            <p class="text-[10px] text-slate-400 mt-0.5">Format JPG, PNG, WEBP maks. 5MB</p>
          </div>
        </div>

        <!-- NIDOS / Username (Read only) -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">NIDOS / Username</label>
          <input type="text" id="editProfileNidn" name="nidn" readonly class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs text-slate-500 font-mono cursor-not-allowed">
        </div>

        <!-- Nama Lengkap & Gelar -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap (Tanpa Gelar) *</label>
            <input type="text" id="editProfileNama" name="nama" required placeholder="Contoh: Ayu Ernawati" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Gelar Akademik</label>
            <input type="text" id="editProfileGelar" name="gelar" placeholder="S.Kom., M.Kom." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
        </div>

        <!-- Kontak No WA & Email -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp / HP *</label>
            <input type="tel" id="editProfileNoHp" name="no_hp" required placeholder="0812xxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email</label>
            <input type="email" id="editProfileEmail" name="email" placeholder="nama@unpam.ac.id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
        </div>

        <!-- Ganti Password Section (Opsional) -->
        <div class="pt-2 border-t border-slate-100">
          <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl space-y-2.5">
            <div class="flex items-center gap-1.5 text-amber-800">
              <i data-lucide="key-round" class="w-4 h-4"></i>
              <span class="text-xs font-bold">Ganti Kata Sandi (Opsional)</span>
            </div>
            <p class="text-[10px] text-amber-700 leading-tight">Kosongkan kolom jika tidak ingin mengubah kata sandi login.</p>
            
            <div>
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">Kata Sandi Baru</label>
              <input type="password" id="editProfilePasswordBaru" name="password_baru" placeholder="Minimal 4 karakter" class="w-full px-3 py-2 bg-white border border-amber-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">Ulangi Kata Sandi Baru</label>
              <input type="password" id="editProfilePasswordKonf" name="password_konfirmasi" placeholder="Ketik ulang password baru" class="w-full px-3 py-2 bg-white border border-amber-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>
          </div>
        </div>

        <!-- Tombol Simpan -->
        <button type="submit" id="btnSubmitEditProfile" class="w-full py-2.5 bg-blue-900 hover:bg-blue-950 text-white rounded-xl text-xs font-bold transition-colors shadow-md flex items-center justify-center gap-1.5 mt-2">
          <i data-lucide="save" class="w-4 h-4"></i>
          <span>Simpan Perubahan Profil</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Main JavaScript App Logic -->
  <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
