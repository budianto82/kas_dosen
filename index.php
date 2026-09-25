<?php
/**
 * Kas Dosen UNPAM - Mobile Web Application
 * Program Studi Sistem Informasi - Universitas Pamulang
 */
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
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

  <!-- Google Fonts & Tailwind CDN -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Lucide Icons & Chart.js -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Custom Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

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
    <header class="app-header px-4 pt-4 pb-4 sticky top-0 z-30 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Logo Lambang UNPAM Resmi -->
          <div class="w-11 h-11 rounded-xl bg-white p-1 flex items-center justify-center shadow-md flex-shrink-0 border border-amber-400/40">
            <img src="assets/img/logo_unpam.png" alt="Logo UNPAM" class="w-full h-full object-contain">
          </div>
          <div>
            <h1 class="text-xs font-extrabold text-white tracking-wide uppercase">KAS DOSEN SISTEM INFORMASI</h1>
            <p class="text-[10px] text-amber-300 font-medium">Universitas Pamulang</p>
            <p class="text-[10px] text-slate-200 font-medium">Ruang R2</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Avatar Foto Profil User -->
          <div id="userHeaderAvatar" class="w-9 h-9 rounded-full bg-amber-400 text-slate-900 font-extrabold text-xs flex items-center justify-center overflow-hidden flex-shrink-0 border border-white/30 shadow cursor-pointer hidden" onclick="App.switchTab('dosen')" title="Lihat Profil Dosen">
            <!-- Rendered by JS -->
          </div>
          <div class="text-right cursor-pointer" onclick="App.state.user ? App.switchTab('dosen') : App.openModal('modalLogin')" title="Klik untuk profil / login">
            <span id="userRoleBadge" class="inline-block px-2 py-0.5 rounded-full text-[9px] font-extrabold tracking-wider bg-white/20 text-white">
              MEMUAT...
            </span>
            <div id="userNameDisplay" class="text-[11px] text-white font-bold max-w-[140px] truncate">
              Tamu
            </div>
          </div>
          <button id="btnSettingsAction" onclick="App.openSettingsModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors flex-shrink-0 hidden" title="Pengaturan Kas (Bendahara)">
            <i data-lucide="settings" class="w-4 h-4"></i>
          </button>
          <button id="authActionBtn" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors flex-shrink-0" title="Login / Logout">
            <i data-lucide="log-in" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
    </header>

    <!-- Content Area (Scrollable) -->
    <main class="content-area px-4 pt-4">

      <!-- ==================== TAB 1: BERANDA ==================== -->
      <section id="tab-beranda" class="tab-pane space-y-4">
        
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
            <button id="btnTambahIuran" onclick="App.openModal('modalBayarIuran')" class="px-3 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold flex items-center gap-1 shadow-sm hover:bg-emerald-700 hidden">
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
        <div id="userProfileCard" class="bg-gradient-to-br from-blue-900 via-blue-950 to-slate-900 text-white rounded-2xl p-4 shadow-sm border border-blue-800/40 space-y-3 hidden">
          <div class="flex items-center gap-3.5">
            <div class="relative flex-shrink-0">
              <div id="userProfilePhotoPreview" class="w-14 h-14 rounded-full bg-amber-400 text-slate-950 font-extrabold text-base flex items-center justify-center overflow-hidden border-2 border-white/30 shadow-md">
                <!-- Foto or Initials -->
              </div>
              <label for="inputUploadFotoProfil" class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-900 flex items-center justify-center cursor-pointer shadow-md transition-transform active:scale-95" title="Ganti Foto Profil">
                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
              </label>
              <input type="file" id="inputUploadFotoProfil" accept="image/*" class="hidden" onchange="App.uploadFotoProfil(this)">
            </div>
            <div class="flex-1 min-w-0">
              <span id="userProfileBadge" class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-400 text-slate-900 tracking-wider mb-1">
                BENDAHARA
              </span>
              <h3 id="userProfileNama" class="text-xs sm:text-sm font-bold text-white truncate leading-tight">Nama Dosen</h3>
              <p id="userProfileNidn" class="text-[11px] text-blue-200 font-mono mt-0.5">NIDOS: -</p>
            </div>
          </div>
          <div class="pt-2.5 border-t border-white/10 flex items-center justify-between text-xs">
            <button onclick="App.logout()" type="button" class="text-[11px] text-rose-300 hover:text-rose-200 font-bold flex items-center gap-1.5 transition-colors">
              <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
              <span>Keluar Akun</span>
            </button>
            <label for="inputUploadFotoProfil" class="text-[11px] text-amber-300 hover:text-amber-200 font-bold cursor-pointer flex items-center gap-1.5">
              <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
              <span>Unggah Foto Profil</span>
            </label>
          </div>
        </div>

        <!-- Search & Add Bar -->
        <div class="bg-white rounded-2xl p-3 shadow-sm border border-slate-100 flex items-center gap-2">
          <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="searchDosenInput" oninput="App.loadDosenList()" placeholder="Cari Nama / NIDN Dosen..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-900">
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
  <div id="modalLogin" class="modal-overlay modal-centered">
    <div class="modal-dialog">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2.5">
          <img src="assets/img/logo_unpam.png" alt="UNPAM" class="w-8 h-8 object-contain">
          <div>
            <h3 class="text-sm font-bold text-slate-900 leading-tight">Masuk Akun Kas Dosen</h3>
            <p class="text-[10px] text-slate-500">Sistem Informasi Universitas Pamulang</p>
          </div>
        </div>
        <button onclick="App.closeModal('modalLogin')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100" title="Tutup">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <form onsubmit="App.login(event)" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Username / NIDN Dosen</label>
          <input type="text" name="username" required placeholder="Masukkan NIDN / NIDOS Anda" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi (Password)</label>
          <input type="password" name="password" required placeholder="Kata sandi akun" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
        </div>
        <div class="p-2.5 bg-blue-50 text-blue-900 rounded-xl text-[11px] leading-relaxed">
          💡 <strong>Info Login Dosen:</strong><br>
          Gunakan <strong>NIDN / NIDOS</strong> Anda sebagai Username.<br>
          <span class="text-[10px] text-slate-600">(Password default: NIDN/NIDOS Anda atau <code>unpam123</code>)</span>
        </div>
        <button type="submit" class="w-full py-2.5 bg-blue-900 text-white rounded-xl text-xs font-bold hover:bg-blue-950 transition-colors shadow-md">
          Masuk Sekarang
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
            <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-semibold">Rp 30.000 / bln</span>
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
      <form onsubmit="App.submitBayarIuran(event)" enctype="multipart/form-data" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Dosen *</label>
          <select id="formIuranDosen" name="dosen_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
            <!-- Populated by JS -->
          </select>
        </div>
        
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Bulan Iuran (Bisa pilih lebih dari 1) *</label>
          <div class="grid grid-cols-4 gap-1.5 text-xs text-slate-700">
            <?php
            $bulanNama = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            $blnNow = (int)date('n');
            foreach ($bulanNama as $num => $nama):
            ?>
              <label class="flex items-center gap-1 p-1.5 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-blue-50">
                <input type="checkbox" name="bulan[]" value="<?= $num ?>" <?= $num === $blnNow ? 'checked' : '' ?> class="rounded text-blue-900">
                <span class="text-[11px] font-medium"><?= $nama ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun *</label>
            <input type="number" name="tahun" value="<?= date('Y') ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal per Bulan *</label>
            <input type="number" name="nominal" value="30000" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Bayar *</label>
            <input type="date" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Bayar *</label>
            <select name="metode_bayar" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
              <option value="transfer">Transfer Bank</option>
              <option value="tunai">Tunai / Cash</option>
              <option value="qris">QRIS</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Bukti Transfer (Opsional / Foto)</label>
          <input type="file" name="bukti_bayar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Tambahan</label>
          <input type="text" name="keterangan" placeholder="Contoh: Titip lewat Pak Budi / Iuran rutin" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
        </div>

        <button type="submit" class="w-full py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition-colors shadow-md mt-2">
          Simpan Iuran Kas
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
            <label class="block text-xs font-semibold text-slate-700 mb-1">NIDN / NIDK *</label>
            <input type="text" name="nidn" required placeholder="0412345678" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Gelar</label>
            <input type="text" name="gelar" placeholder="M.Kom. / Ph.D" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap (tanpa gelar) *</label>
          <input type="text" name="nama" required placeholder="Contoh: Muhammad Ilham" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-900 focus:outline-none">
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp *</label>
            <input type="text" name="no_hp" required placeholder="08123456789" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan di Prodi</label>
            <input type="text" name="jabatan" value="Dosen Tetap" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Email Kampus (Opsional)</label>
          <input type="email" name="email" placeholder="dosen@unpam.ac.id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
        </div>

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

      <!-- Tombol Hapus Dosen (Khusus Bendahara) -->
      <div class="mt-4 pt-3 border-t border-slate-100">
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
          <span id="kirimTfNominalBadge" class="bg-blue-600 text-white px-2 py-0.5 rounded-full text-[10px] font-mono">Rp 30.000 / bln</span>
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
          <input type="number" id="settingNominalIuran" name="nominal_iuran_bulanan" required placeholder="30000" class="w-full px-3 py-2 bg-white border border-amber-300 rounded-xl text-xs font-mono font-bold text-slate-800 focus:ring-2 focus:ring-blue-900 focus:outline-none">
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

  <!-- Main JavaScript App Logic -->
  <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
