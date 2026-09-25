/**
 * Kas Dosen UNPAM - Mobile Application Core Logic
 * Program Studi Sistem Informasi - Universitas Pamulang
 */

const App = {
  state: {
    currentTab: 'beranda',
    user: null,
    dashboardData: null,
    dosenList: [],
    kategoriList: [],
    currentTahun: new Date().getFullYear(),
    currentBulan: new Date().getMonth() + 1,
    deferredInstallPrompt: null
  },

  init() {
    this.registerServiceWorker();
    this.setupInstallPrompt();
    this.checkSession();
    this.bindEvents();
  },

  // 1. PWA Service Worker & Install Banner
  registerServiceWorker() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('./sw.js')
        .then(() => console.log('PWA Service Worker terdaftar.'))
        .catch(err => console.log('SW registration failed:', err));
    }
  },

  setupInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      this.state.deferredInstallPrompt = e;
      const banner = document.getElementById('pwaInstallBanner');
      if (banner) banner.classList.remove('hidden');
    });
  },

  installPwa() {
    if (this.state.deferredInstallPrompt) {
      this.state.deferredInstallPrompt.prompt();
      this.state.deferredInstallPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          console.log('User menginstall PWA Kas Dosen');
        }
        this.state.deferredInstallPrompt = null;
        const banner = document.getElementById('pwaInstallBanner');
        if (banner) banner.classList.add('hidden');
      });
    }
  },

  // 2. Authentication & Session
  async checkSession() {
    try {
      const res = await fetch('api/auth.php?action=check');
      const data = await res.json();
      if (data.status === 'success' && data.logged_in) {
        this.state.user = data.user;
      } else {
        this.state.user = null;
      }
      this.updateUserUI();
      this.loadDashboard();
      this.loadKategori();
      this.loadDosenList();
    } catch (err) {
      console.error('Check session error:', err);
      this.loadDashboard();
    }
  },

  updateUserUI() {
    const userRoleEl = document.getElementById('userRoleBadge');
    const userNameEl = document.getElementById('userNameDisplay');
    const userAvatarEl = document.getElementById('userHeaderAvatar');
    const authBtnEl = document.getElementById('authActionBtn');

    const isBendahara = this.state.user && ['bendahara', 'kaprodi'].includes(this.state.user.role);

    if (this.state.user) {
      const u = this.state.user;
      const roleName = u.role ? u.role.toUpperCase() : 'DOSEN';
      const nidos = u.nidn || u.username;

      if (userRoleEl) {
        userRoleEl.textContent = nidos ? `${roleName} • ${nidos}` : roleName;
      }
      if (userNameEl) {
        userNameEl.textContent = u.nama;
        userNameEl.title = u.nama;
      }
      if (userAvatarEl) {
        userAvatarEl.classList.remove('hidden');
        if (u.foto) {
          userAvatarEl.innerHTML = `<img src="${u.foto}?v=${Date.now()}" class="w-full h-full object-cover">`;
        } else {
          const initials = u.nama ? u.nama.substring(0, 2).toUpperCase() : 'SI';
          userAvatarEl.textContent = initials;
        }
      }
      if (authBtnEl) {
        authBtnEl.innerHTML = '<i data-lucide="log-out" class="w-4 h-4"></i>';
        authBtnEl.onclick = () => this.logout();
      }

      // Update Kartu Profil Dosen di Tab Dosen
      const profCard = document.getElementById('userProfileCard');
      if (profCard) {
        profCard.classList.remove('hidden');
        const pPhoto = document.getElementById('userProfilePhotoPreview');
        const pBadge = document.getElementById('userProfileBadge');
        const pNama = document.getElementById('userProfileNama');
        const pNidn = document.getElementById('userProfileNidn');

        if (pBadge) pBadge.textContent = roleName;
        if (pNama) pNama.textContent = u.nama;
        if (pNidn) pNidn.textContent = `NIDOS: ${nidos}`;
        if (pPhoto) {
          if (u.foto) {
            pPhoto.innerHTML = `<img src="${u.foto}?v=${Date.now()}" class="w-full h-full object-cover">`;
          } else {
            pPhoto.textContent = u.nama ? u.nama.substring(0, 2).toUpperCase() : 'SI';
          }
        }
      }
    } else {
      if (userRoleEl) userRoleEl.textContent = 'MODE TERBUKA';
      if (userNameEl) userNameEl.textContent = 'Dosen / Civitas SI';
      if (userAvatarEl) userAvatarEl.classList.add('hidden');
      const profCard = document.getElementById('userProfileCard');
      if (profCard) profCard.classList.add('hidden');

      if (authBtnEl) {
        authBtnEl.innerHTML = '<i data-lucide="log-in" class="w-4 h-4"></i>';
        authBtnEl.onclick = () => this.openModal('modalLogin');
      }
    }

    // Visibilitas tombol input khusus Bendahara / Kaprodi
    const btnTambahIuran = document.getElementById('btnTambahIuran');
    const btnCaraBayar = document.getElementById('btnCaraBayar');
    const btnCatatPengeluaran = document.getElementById('btnCatatPengeluaran');
    const btnTambahDosen = document.getElementById('btnTambahDosen');

    if (btnTambahIuran) btnTambahIuran.classList.toggle('hidden', !isBendahara);
    if (btnCaraBayar) btnCaraBayar.classList.toggle('hidden', isBendahara);
    if (btnCatatPengeluaran) btnCatatPengeluaran.classList.toggle('hidden', !isBendahara);
    if (btnTambahDosen) btnTambahDosen.classList.toggle('hidden', !isBendahara);

    // Menu Cepat di Beranda
    const quickBayarText = document.getElementById('quickActionBayarText');
    const quickBayarIconWrap = document.getElementById('quickActionBayarIconWrap');
    if (quickBayarText) {
      quickBayarText.textContent = isBendahara ? 'Catat Iuran' : 'Cara Bayar';
    }
    if (quickBayarIconWrap) {
      quickBayarIconWrap.className = isBendahara 
        ? 'quick-action-icon bg-emerald-50 text-emerald-600' 
        : 'quick-action-icon bg-blue-50 text-blue-700';
      quickBayarIconWrap.innerHTML = isBendahara 
        ? '<i data-lucide="plus-circle" class="w-6 h-6"></i>' 
        : '<i data-lucide="help-circle" class="w-6 h-6"></i>';
    }

    const quickPengeluaranText = document.getElementById('quickActionPengeluaranText');
    if (quickPengeluaranText) {
      quickPengeluaranText.textContent = isBendahara ? 'Catat Keluar' : 'Pengeluaran';
    }

    if (window.lucide) lucide.createIcons();
  },

  handleQuickActionBayar() {
    const isBendahara = this.state.user && ['bendahara', 'kaprodi'].includes(this.state.user.role);
    if (isBendahara) {
      this.openModal('modalBayarIuran');
    } else {
      this.openModal('modalCaraBayar');
    }
  },

  handleQuickActionPengeluaran() {
    const isBendahara = this.state.user && ['bendahara', 'kaprodi'].includes(this.state.user.role);
    if (isBendahara) {
      this.openModal('modalTambahPengeluaran');
    } else {
      this.switchTab('pengeluaran');
    }
  },

  copyRekening() {
    const textEl = document.getElementById('rekeningModalText');
    const text = textEl ? textEl.textContent.trim() : '4401500586720';
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(() => {
        this.showToast('Nomor rekening BTN berhasil disalin!', 'success');
      }).catch(() => {
        this.showToast('Nomor rekening: ' + text, 'info');
      });
    } else {
      const input = document.createElement('input');
      input.value = text;
      document.body.appendChild(input);
      input.select();
      document.execCommand('copy');
      document.body.removeChild(input);
      this.showToast('Nomor rekening BTN berhasil disalin!', 'success');
    }
  },

  async login(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const body = Object.fromEntries(formData.entries());

    try {
      const res = await fetch('api/auth.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if (data.status === 'success') {
        this.showToast(data.message, 'success');
        this.state.user = data.user;
        this.closeModal('modalLogin');
        this.updateUserUI();
        this.switchTab('beranda');
        this.loadDashboard();
        this.loadDosenList();
      } else {
        this.showToast(data.message || 'Login gagal.', 'error');
      }
    } catch (err) {
      this.showToast('Terjadi kesalahan jaringan.', 'error');
    }
  },

  async logout() {
    if (!confirm('Apakah Anda yakin ingin keluar?')) return;
    try {
      await fetch('api/auth.php?action=logout');
      this.state.user = null;
      this.updateUserUI();
      this.showToast('Berhasil keluar.', 'success');
      this.loadDashboard();
      this.loadDosenList();
    } catch (err) {
      console.error(err);
    }
  },

  // 3. Tab Navigation
  switchTab(tabId) {
    this.state.currentTab = tabId;

    // Update Bottom Nav Styling
    document.querySelectorAll('.nav-item').forEach(item => {
      if (item.dataset.tab === tabId) {
        item.classList.add('active');
      } else {
        item.classList.remove('active');
      }
    });

    // Toggle Content Views
    document.querySelectorAll('.tab-pane').forEach(pane => {
      if (pane.id === 'tab-' + tabId) {
        pane.classList.remove('hidden');
        pane.classList.add('fade-in');
      } else {
        pane.classList.add('hidden');
        pane.classList.remove('fade-in');
      }
    });

    // Refresh Data Sesuai Tab
    if (tabId === 'beranda') this.loadDashboard();
    if (tabId === 'iuran') this.loadIuranTab();
    if (tabId === 'pengeluaran') this.loadPengeluaran();
    if (tabId === 'laporan') this.loadLaporan();
    if (tabId === 'dosen') this.loadDosenList();

    if (window.lucide) lucide.createIcons();
  },

  bindEvents() {
    // Nav Click
    document.querySelectorAll('.nav-item').forEach(item => {
      item.addEventListener('click', () => {
        this.switchTab(item.dataset.tab);
      });
    });

    // Click outside modal to close
    document.querySelectorAll('.modal-overlay').forEach(modal => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.classList.remove('active');
        }
      });
    });
  },

  // 4. Dashboard View
  async loadDashboard() {
    try {
      const res = await fetch('api/dashboard.php');
      const data = await res.json();
      if (data.status === 'success') {
        this.state.dashboardData = data.data;
        this.renderDashboard(data.data);
      }
    } catch (err) {
      console.error('Error load dashboard:', err);
    }
  },

  renderDashboard(d) {
    // Saldo Utama
    const elSaldo = document.getElementById('dashSaldo');
    if (elSaldo) elSaldo.textContent = d.saldo_kas_formatted;

    // Pemasukan & Pengeluaran Bulan Ini
    const elIn = document.getElementById('dashPemasukanBulan');
    if (elIn) elIn.textContent = d.pemasukan_bulan_ini_formatted;
    const elOut = document.getElementById('dashPengeluaranBulan');
    if (elOut) elOut.textContent = d.pengeluaran_bulan_ini_formatted;

    // Partisipasi Dosen
    const elPartisipasi = document.getElementById('dashPartisipasi');
    if (elPartisipasi) {
      elPartisipasi.textContent = `${d.dosen_lunas_bulan_ini} dari ${d.total_dosen} Dosen`;
    }
    const elProgress = document.getElementById('dashProgressBar');
    if (elProgress) {
      elProgress.style.width = `${d.persen_lunas}%`;
    }
    const elPersen = document.getElementById('dashPersenLunas');
    if (elPersen) {
      elPersen.textContent = `${d.persen_lunas}% Lunas (${d.periode_aktif})`;
    }

    // Info Rekening Kas
    const rek = d.pengaturan;
    if (rek) {
      const rekEl = document.getElementById('dashRekeningInfo');
      if (rekEl) {
        rekEl.innerHTML = `
          <div class="flex items-center justify-between text-xs text-blue-100">
            <span>${rek.nama_bank || 'Bank BTN'}</span>
            <span class="font-mono font-bold">${rek.nomor_rekening || '4401500586720'}</span>
          </div>
          <div class="text-[11px] text-amber-300 truncate">a.n. ${rek.atas_nama || 'Ayu Ernawati, S.Kom., M.Kom.'}</div>
        `;
      }
      const modalRek = document.getElementById('rekeningModalText');
      if (modalRek && rek.nomor_rekening) {
        modalRek.textContent = rek.nomor_rekening;
      }
      const modalAtasNama = document.getElementById('modalAtasNamaText');
      if (modalAtasNama && rek.atas_nama) {
        modalAtasNama.textContent = `a.n. ${rek.atas_nama}`;
      }
      const modalBankTitle = document.getElementById('modalBankTitle');
      if (modalBankTitle && rek.nama_bank) {
        const shortBank = rek.nama_bank.replace('Bank ', '');
        modalBankTitle.innerHTML = `<i data-lucide="credit-card" class="w-4 h-4"></i> Transfer Bank (${shortBank})`;
      }
      const modalBankLabel = document.getElementById('modalBankLabel');
      if (modalBankLabel && rek.nama_bank) {
        const shortBank = rek.nama_bank.replace('Bank ', '');
        modalBankLabel.textContent = `Nomor Rekening ${shortBank}:`;
      }
      const modalNamaBendahara = document.getElementById('modalNamaBendahara');
      if (modalNamaBendahara && rek.nama_bendahara) {
        modalNamaBendahara.textContent = `Ibu ${rek.nama_bendahara}`;
      }
      const btnWa = document.getElementById('btnWaKonfirmasi');
      if (btnWa && rek.kontak_bendahara) {
        let clean = rek.kontak_bendahara.replace(/[^0-9]/g, '');
        if (clean.startsWith('0')) clean = '62' + clean.slice(1);
        btnWa.href = `https://wa.me/${clean}?text=${encodeURIComponent("Assalamu'alaikum Ibu Bendahara, saya sudah melakukan pembayaran iuran kas dosen SI UNPAM. Berikut bukti transfernya:")}`;
      }
      if (window.lucide) lucide.createIcons();
    }

    // Render Recent Transactions
    const txContainer = document.getElementById('dashRecentTxList');
    if (txContainer) {
      if (!d.transaksi_terbaru || d.transaksi_terbaru.length === 0) {
        txContainer.innerHTML = '<div class="text-center py-6 text-xs text-slate-400">Belum ada transaksi</div>';
      } else {
        txContainer.innerHTML = d.transaksi_terbaru.map(t => {
          const isMasuk = t.tipe === 'masuk';
          const icon = isMasuk ? 'arrow-down-left' : 'arrow-up-right';
          const color = isMasuk ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
          const sign = isMasuk ? '+' : '-';
          return `
            <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg ${color} flex items-center justify-center flex-shrink-0">
                  <i data-lucide="${icon}" class="w-4 h-4"></i>
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-800 line-clamp-1">${t.judul}</div>
                  <div class="text-[11px] text-slate-400">${this.formatDateIndo(t.tanggal)} • ${t.keterangan || '-'}</div>
                </div>
              </div>
              <div class="text-xs font-bold ${isMasuk ? 'text-emerald-600' : 'text-slate-800'} text-right flex-shrink-0 ml-2">
                ${sign} ${this.formatRupiah(t.nominal)}
              </div>
            </div>
          `;
        }).join('');
      }
    }

    // Render Chart Keuangan
    this.renderChart(d.chart);

    if (window.lucide) lucide.createIcons();
  },

  chartInstance: null,
  renderChart(chartData) {
    const canvas = document.getElementById('keuanganChart');
    if (!canvas || !window.Chart) return;

    if (this.chartInstance) {
      this.chartInstance.destroy();
    }

    const ctx = canvas.getContext('2d');
    this.chartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: chartData.labels,
        datasets: [
          {
            label: 'Masuk',
            data: chartData.pemasukan,
            backgroundColor: '#10B981',
            borderRadius: 6,
            barPercentage: 0.6
          },
          {
            label: 'Keluar',
            data: chartData.pengeluaran,
            backgroundColor: '#EF4444',
            borderRadius: 6,
            barPercentage: 0.6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { boxWidth: 10, font: { size: 10 } }
          },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${ctx.dataset.label}: ${App.formatRupiah(ctx.raw)}`
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 } } },
          y: {
            grid: { color: '#F1F5F9' },
            ticks: {
              font: { size: 9 },
              callback: (v) => v >= 1000000 ? (v/1000000) + 'jt' : (v >= 1000 ? (v/1000) + 'rb' : v)
            }
          }
        }
      }
    });
  },

  // 5. Iuran Tab & Matrix
  iuranSubView: 'matrix', // 'matrix' or 'list'
  async loadIuranTab() {
    const matrixBtn = document.getElementById('btnViewMatrix');
    const listBtn = document.getElementById('btnViewList');
    
    if (this.iuranSubView === 'matrix') {
      if (matrixBtn) matrixBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-900 text-white shadow-sm';
      if (listBtn) listBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100';
      await this.loadIuranMatrix();
    } else {
      if (matrixBtn) matrixBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100';
      if (listBtn) listBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-900 text-white shadow-sm';
      await this.loadIuranList();
    }
  },

  switchIuranView(view) {
    this.iuranSubView = view;
    this.loadIuranTab();
  },

  async loadIuranMatrix() {
    const container = document.getElementById('iuranContentArea');
    if (!container) return;
    container.innerHTML = '<div class="text-center py-10 text-xs text-slate-400">Memuat matriks iuran...</div>';

    try {
      const res = await fetch(`api/iuran.php?action=matrix&tahun=${this.state.currentTahun}`);
      const data = await res.json();
      if (data.status === 'success') {
        this.renderIuranMatrix(data);
      }
    } catch (err) {
      console.error(err);
      container.innerHTML = '<div class="text-center py-10 text-xs text-rose-500">Gagal memuat matriks iuran</div>';
    }
  },

  renderIuranMatrix(data) {
    const container = document.getElementById('iuranContentArea');
    if (!container) return;

    const bulanHeaders = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    let html = `
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 mb-4">
        <div class="flex items-center justify-between mb-3">
          <div class="text-xs font-bold text-slate-800">Matriks Iuran Dosen SI (${data.tahun})</div>
          <div class="text-[11px] text-slate-500">Iuran: Rp 30.000 / bln</div>
        </div>

        <div class="matrix-scroll">
          <table class="matrix-table text-left">
            <thead>
              <tr>
                <th class="sticky left-0 z-10" style="min-width: 140px;">Nama Dosen</th>
                ${bulanHeaders.map((b, idx) => `<th class="text-center" style="min-width: 38px;">${b}</th>`).join('')}
                <th class="text-center" style="min-width: 70px;">Total</th>
                <th class="text-center" style="min-width: 44px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              ${data.matrix.map(d => {
                let cells = '';
                for (let m = 1; m <= 12; m++) {
                  const b = d.bulan[m];
                  const isLunas = b.status === 'lunas';
                  cells += `
                    <td class="matrix-cell ${isLunas ? 'lunas' : 'belum'}" 
                        title="${d.nama_lengkap} - Bulan ${m}: ${isLunas ? 'Lunas' : 'Belum'}">
                      ${isLunas ? '✓' : '-'}
                    </td>
                  `;
                }

                return `
                  <tr class="border-b border-slate-100">
                    <td class="sticky left-0 bg-white z-10 py-2.5 pr-2">
                      <div class="text-xs font-semibold text-slate-800 line-clamp-1">${d.nama_lengkap}</div>
                      <div class="text-[10px] text-slate-400 font-mono">NIDN: ${d.nidn}</div>
                    </td>
                    ${cells}
                    <td class="text-center text-xs font-bold text-slate-800">${d.total_bayar_formatted}</td>
                    <td class="text-center py-2">
                      <button onclick="App.openWaReminderModal(${d.dosen_id})" 
                              class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center mx-auto"
                              title="Kirim Pengingat WhatsApp">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                      </button>
                    </td>
                  </tr>
                `;
              }).join('')}
            </tbody>
          </table>
        </div>

        <!-- Legenda -->
        <div class="flex items-center gap-4 mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
          <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded bg-emerald-500 text-white flex items-center justify-center text-[9px] font-bold">✓</span>
            <span>Lunas</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded bg-slate-100 border border-slate-300 text-slate-400 flex items-center justify-center text-[9px] font-bold">-</span>
            <span>Belum Bayar</span>
          </div>
        </div>
      </div>
    `;

    container.innerHTML = html;
    if (window.lucide) lucide.createIcons();
  },

  async loadIuranList() {
    const container = document.getElementById('iuranContentArea');
    if (!container) return;
    container.innerHTML = '<div class="text-center py-10 text-xs text-slate-400">Memuat riwayat transaksi...</div>';

    try {
      const res = await fetch(`api/iuran.php?action=list&tahun=${this.state.currentTahun}`);
      const data = await res.json();
      if (data.status === 'success') {
        if (data.data.length === 0) {
          container.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">Belum ada data iuran di tahun ini.</div>';
          return;
        }

        container.innerHTML = `
          <div class="space-y-2.5">
            ${data.data.map(item => `
              <div class="p-3 bg-white rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
                <div>
                  <div class="text-xs font-bold text-slate-800">${item.nama_lengkap}</div>
                  <div class="text-[11px] text-slate-500">Iuran Bulan ke-${item.bulan} (${item.tahun}) • ${item.metode_bayar.toUpperCase()}</div>
                  <div class="text-[10px] text-slate-400">${this.formatDateIndo(item.tanggal_bayar)}</div>
                </div>
                <div class="text-right">
                  <div class="text-xs font-bold text-emerald-600">${item.nominal_formatted}</div>
                  <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold ${item.status === 'lunas' ? 'badge-lunas' : 'badge-pending'}">
                    ${item.status.toUpperCase()}
                  </span>
                  ${item.bukti_bayar ? `
                    <button onclick="App.viewImageModal('${item.bukti_bayar}', 'Bukti Bayar Iuran')" class="block text-[10px] text-blue-600 mt-1 hover:underline">
                      Lihat Bukti
                    </button>
                  ` : ''}
                </div>
              </div>
            `).join('')}
          </div>
        `;
      }
    } catch (err) {
      console.error(err);
      container.innerHTML = '<div class="text-center py-8 text-xs text-rose-500">Gagal memuat riwayat iuran.</div>';
    }
  },

  // 6. Pengeluaran Tab
  async loadPengeluaran() {
    const container = document.getElementById('pengeluaranListContainer');
    const filterKat = document.getElementById('filterKatPengeluaran')?.value || '';
    if (!container) return;

    container.innerHTML = '<div class="text-center py-10 text-xs text-slate-400">Memuat pengeluaran...</div>';

    try {
      let url = `api/pengeluaran.php?action=list&tahun=${this.state.currentTahun}`;
      if (filterKat) url += `&kategori_id=${filterKat}`;

      const res = await fetch(url);
      const data = await res.json();
      if (data.status === 'success') {
        const totalEl = document.getElementById('totalPengeluaranHeader');
        if (totalEl) totalEl.textContent = data.total_pengeluaran_formatted;

        if (data.data.length === 0) {
          container.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">Belum ada catatan pengeluaran.</div>';
          return;
        }

        container.innerHTML = data.data.map(p => `
          <div class="p-3.5 bg-white rounded-xl border border-slate-100 shadow-sm">
            <div class="flex items-start justify-between">
              <div>
                <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-800 rounded-md text-[10px] font-semibold mb-1">
                  ${p.nama_kategori || 'Operasional'}
                </span>
                <div class="text-xs font-bold text-slate-800">${p.judul}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">${p.keterangan || '-'}</div>
                <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-2">
                  <span>${this.formatDateIndo(p.tanggal)}</span>
                  ${p.pj_penerima ? `<span>• PJ: ${p.pj_penerima}</span>` : ''}
                </div>
              </div>
              <div class="text-right flex-shrink-0 ml-2">
                <div class="text-xs font-bold text-rose-600">- ${p.nominal_formatted}</div>
                ${p.bukti_nota ? `
                  <button onclick="App.viewImageModal('${p.bukti_nota}', 'Bukti Nota Pengeluaran')" class="mt-2 inline-flex items-center gap-1 text-[10px] text-blue-600 font-medium hover:underline">
                    <i data-lucide="receipt" class="w-3 h-3"></i> Bukti Nota
                  </button>
                ` : ''}
              </div>
            </div>
          </div>
        `).join('');
        if (window.lucide) lucide.createIcons();
      }
    } catch (err) {
      console.error(err);
      container.innerHTML = '<div class="text-center py-8 text-xs text-rose-500">Gagal memuat pengeluaran.</div>';
    }
  },

  async loadKategori() {
    try {
      const res = await fetch('api/pengeluaran.php?action=categories');
      const data = await res.json();
      if (data.status === 'success') {
        this.state.kategoriList = data.data;

        // Populate selects
        const filterSelect = document.getElementById('filterKatPengeluaran');
        const formSelect = document.getElementById('formKatPengeluaran');

        const options = data.data.map(k => `<option value="${k.id}">${k.nama_kategori}</option>`).join('');

        if (filterSelect) {
          filterSelect.innerHTML = '<option value="">Semua Kategori</option>' + options;
        }
        if (formSelect) {
          formSelect.innerHTML = options;
        }
      }
    } catch (err) {
      console.error(err);
    }
  },

  // 7. Laporan Kas Tab (Buku Kas Umum)
  async loadLaporan() {
    const startDate = document.getElementById('laporanStartDate')?.value || `${this.state.currentTahun}-${String(this.state.currentBulan).padStart(2,'0')}-01`;
    const endDate = document.getElementById('laporanEndDate')?.value || `${this.state.currentTahun}-${String(this.state.currentBulan).padStart(2,'0')}-31`;

    const container = document.getElementById('laporanMutasiList');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-10 text-xs text-slate-400">Membuat laporan buku kas...</div>';

    try {
      const res = await fetch(`api/laporan.php?start_date=${startDate}&end_date=${endDate}`);
      const data = await res.json();
      if (data.status === 'success') {
        this.renderLaporan(data);
      }
    } catch (err) {
      console.error(err);
      container.innerHTML = '<div class="text-center py-8 text-xs text-rose-500">Gagal memuat laporan.</div>';
    }
  },

  renderLaporan(d) {
    const r = d.ringkasan;
    const elSaldoAwal = document.getElementById('lapSaldoAwal');
    const elTotalMasuk = document.getElementById('lapTotalMasuk');
    const elTotalKeluar = document.getElementById('lapTotalKeluar');
    const elSaldoAkhir = document.getElementById('lapSaldoAkhir');

    if (elSaldoAwal) elSaldoAwal.textContent = r.saldo_awal_formatted;
    if (elTotalMasuk) elTotalMasuk.textContent = r.total_debit_formatted;
    if (elTotalKeluar) elTotalKeluar.textContent = r.total_kredit_formatted;
    if (elSaldoAkhir) elSaldoAkhir.textContent = r.saldo_akhir_formatted;

    const container = document.getElementById('laporanMutasiList');
    if (!container) return;

    if (d.mutasi.length === 0) {
      container.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">Tidak ada mutasi pada periode ini.</div>';
      return;
    }

    container.innerHTML = `
      <div class="overflow-x-auto">
        <table class="w-full text-[11px] text-left">
          <thead class="bg-slate-100 text-slate-700 uppercase font-semibold">
            <tr>
              <th class="p-2">Tgl</th>
              <th class="p-2">Uraian Transaksi</th>
              <th class="p-2 text-right">Debit</th>
              <th class="p-2 text-right">Kredit</th>
              <th class="p-2 text-right">Saldo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            ${d.mutasi.map(m => `
              <tr class="hover:bg-slate-50">
                <td class="p-2 font-mono text-slate-500 whitespace-nowrap">${m.tanggal.substring(5)}</td>
                <td class="p-2 font-medium text-slate-800">
                  <div>${m.uraian}</div>
                  ${m.metode ? `<span class="text-[10px] text-slate-400">${m.metode}</span>` : ''}
                </td>
                <td class="p-2 text-right font-semibold text-emerald-600 whitespace-nowrap">${m.debit_formatted}</td>
                <td class="p-2 text-right font-semibold text-rose-600 whitespace-nowrap">${m.kredit_formatted}</td>
                <td class="p-2 text-right font-bold text-slate-800 whitespace-nowrap">${m.saldo_formatted}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
  },

  printLaporan() {
    const startDate = document.getElementById('laporanStartDate')?.value || '';
    const endDate = document.getElementById('laporanEndDate')?.value || '';
    window.open(`cetak_laporan.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
  },

  // 8. Dosen Tab
  async loadDosenList() {
    const search = document.getElementById('searchDosenInput')?.value || '';
    const container = document.getElementById('dosenListContainer');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">Memuat daftar dosen...</div>';

    try {
      const res = await fetch(`api/dosen.php?action=list&search=${encodeURIComponent(search)}`);
      const data = await res.json();
      if (data.status === 'success') {
        this.state.dosenList = data.data;
        this.renderDosenList(data.data);
        this.populateDosenSelects(data.data);
      }
    } catch (err) {
      console.error(err);
      container.innerHTML = '<div class="text-center py-8 text-xs text-rose-500">Gagal memuat data dosen.</div>';
    }
  },

  renderDosenList(list) {
    const container = document.getElementById('dosenListContainer');
    if (!container) return;

    if (list.length === 0) {
      container.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">Dosen tidak ditemukan.</div>';
      return;
    }

    const isBendahara = this.state.user && ['bendahara', 'kaprodi'].includes(this.state.user.role);
    const myNidn = this.state.user ? this.state.user.nidn : '';

    container.innerHTML = list.map(d => `
      <div class="p-3.5 bg-white rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-900 text-amber-400 font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm overflow-hidden border border-slate-200">
            ${d.foto ? `<img src="${d.foto}?v=${Date.now()}" class="w-full h-full object-cover">` : d.nama.substring(0, 2).toUpperCase()}
          </div>
          <div>
            <div class="text-xs font-bold text-slate-800">${d.nama_lengkap}</div>
            <div class="text-[11px] text-slate-500 font-mono">NIDOS: ${d.nidn}</div>
            <div class="text-[10px] text-slate-400 flex items-center gap-2 mt-0.5">
              <span>${d.jabatan || 'Dosen Tetap'}</span>
              <span>•</span>
              <span class="${d.lunas_bulan_ini ? 'text-emerald-600 font-semibold' : 'text-rose-500 font-semibold'}">
                ${d.lunas_bulan_ini ? '✓ Lunas Bulan Ini' : 'Belum Bayar Bulan Ini'}
              </span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <button onclick="App.openWaReminderModal(${d.id})" 
                  class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center flex-shrink-0"
                  title="Hubungi via WhatsApp">
            <i data-lucide="message-circle" class="w-4 h-4"></i>
          </button>
          <button onclick="App.openDosenDetailModal(${d.id})" 
                  class="w-8 h-8 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-100 flex items-center justify-center flex-shrink-0"
                  title="Detail & Riwayat">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
          </button>
          ${isBendahara && d.nidn !== myNidn ? `
            <button onclick="App.confirmDeleteDosen(${d.id}, '${d.nama.replace(/'/g, "\\'")}')" 
                    class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center flex-shrink-0"
                    title="Hapus Dosen">
              <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
          ` : ''}
        </div>
      </div>
    `).join('');

    if (window.lucide) lucide.createIcons();
  },

  populateDosenSelects(list) {
    const select = document.getElementById('formIuranDosen');
    if (select) {
      select.innerHTML = '<option value="">-- Pilih Dosen --</option>' + 
        list.map(d => `<option value="${d.id}">${d.nama_lengkap} (${d.nidn})</option>`).join('');
    }
  },

  // 9. Modals & Action Handlers
  openModal(modalId) {
    if (modalId === 'modalBayarIuran' || modalId === 'modalTambahPengeluaran' || modalId === 'modalTambahDosen') {
      const isBendahara = this.state.user && ['bendahara', 'kaprodi'].includes(this.state.user.role);
      if (!isBendahara) {
        if (modalId === 'modalBayarIuran') {
          this.openModal('modalCaraBayar');
          return;
        }
        this.showToast('Fitur ini hanya dapat diakses oleh Bendahara / Pengurus.', 'info');
        this.openModal('modalLogin');
        return;
      }
    }

    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('active');
    }
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
    }
  },

  async openDosenDetailModal(dosenId) {
    try {
      const res = await fetch(`api/dosen.php?action=detail&id=${dosenId}`);
      const data = await res.json();
      if (data.status === 'success') {
        const d = data.data.dosen;
        const riwayat = data.data.riwayat_iuran;

        this.activeDetailDosenId = d.id;
        this.activeDetailDosenNidn = d.nidn;

        document.getElementById('modalDetailDosenTitle').textContent = d.nama_lengkap;
        document.getElementById('modalDetailDosenNidn').textContent = `NIDOS: ${d.nidn} • ${d.no_hp}`;

        const photoEl = document.getElementById('modalDetailDosenFoto');
        if (photoEl) {
          if (d.foto) {
            photoEl.innerHTML = `<img src="${d.foto}?v=${Date.now()}" class="w-full h-full object-cover">`;
          } else {
            photoEl.textContent = d.nama.substring(0, 2).toUpperCase();
          }
        }

        const isBendahara = this.state.user && ['bendahara', 'kaprodi'].includes(this.state.user.role);
        const isSelf = this.state.user && this.state.user.nidn === d.nidn;

        const btnUpload = document.getElementById('btnUploadDetailFoto');
        if (btnUpload) {
          btnUpload.classList.toggle('hidden', !(isBendahara || isSelf));
        }

        const btnHapus = document.getElementById('btnHapusDosenDetail');
        if (btnHapus) {
          btnHapus.classList.toggle('hidden', !isBendahara || isSelf);
        }

        const container = document.getElementById('modalDetailDosenRiwayat');
        if (riwayat.length === 0) {
          container.innerHTML = '<div class="text-center py-4 text-xs text-slate-400">Belum ada riwayat pembayaran.</div>';
        } else {
          container.innerHTML = riwayat.map(r => `
            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg text-xs">
              <div>
                <span class="font-bold text-slate-800">Bulan ke-${r.bulan} / ${r.tahun}</span>
                <span class="text-[10px] text-slate-400 block">${r.tanggal_bayar} • ${r.metode_bayar.toUpperCase()}</span>
              </div>
              <div class="text-right">
                <span class="font-bold text-emerald-600">${r.nominal_formatted}</span>
                <span class="block text-[10px] font-semibold text-slate-500">${r.status.toUpperCase()}</span>
              </div>
            </div>
          `).join('');
        }

        this.openModal('modalDosenDetail');
        if (window.lucide) lucide.createIcons();
      }
    } catch (err) {
      console.error(err);
      this.showToast('Gagal memuat detail dosen.', 'error');
    }
  },

  async openWaReminderModal(dosenId) {
    try {
      const res = await fetch(`api/iuran.php?action=wa_template&dosen_id=${dosenId}&bulan=${this.state.currentBulan}&tahun=${this.state.currentTahun}`);
      const data = await res.json();
      if (data.status === 'success') {
        const msgEl = document.getElementById('waTemplatePreview');
        const sendBtn = document.getElementById('waSendActionBtn');
        if (msgEl) msgEl.value = data.pesan;
        if (sendBtn) sendBtn.href = data.wa_url;
        this.openModal('modalWaReminder');
      }
    } catch (err) {
      console.error(err);
      this.showToast('Gagal membuat pengingat WhatsApp.', 'error');
    }
  },

  viewImageModal(src, title) {
    const modal = document.getElementById('modalImageViewer');
    const img = document.getElementById('imageViewerContent');
    const titleEl = document.getElementById('imageViewerTitle');
    if (img && modal) {
      img.src = src;
      if (titleEl) titleEl.textContent = title || 'Bukti Transaksi';
      this.openModal('modalImageViewer');
    }
  },

  // 10. Form Submissions
  async submitBayarIuran(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
      const res = await fetch('api/iuran.php?action=pay', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.status === 'success') {
        this.showToast(data.message, 'success');
        form.reset();
        this.closeModal('modalBayarIuran');
        this.loadDashboard();
        if (this.state.currentTab === 'iuran') this.loadIuranTab();
      } else {
        this.showToast(data.message || 'Gagal menyimpan iuran.', 'error');
        if (res.status === 401 || res.status === 403) {
          this.closeModal('modalBayarIuran');
          this.openModal('modalLogin');
        }
      }
    } catch (err) {
      this.showToast('Terjadi kesalahan jaringan.', 'error');
    }
  },

  async submitPengeluaran(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
      const res = await fetch('api/pengeluaran.php?action=create', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.status === 'success') {
        this.showToast(data.message, 'success');
        form.reset();
        this.closeModal('modalTambahPengeluaran');
        this.loadDashboard();
        if (this.state.currentTab === 'pengeluaran') this.loadPengeluaran();
      } else {
        this.showToast(data.message || 'Gagal mencatat pengeluaran.', 'error');
      }
    } catch (err) {
      this.showToast('Terjadi kesalahan jaringan.', 'error');
    }
  },

  async submitTambahDosen(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const body = Object.fromEntries(formData.entries());

    try {
      const res = await fetch('api/dosen.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if (data.status === 'success') {
        this.showToast(data.message, 'success');
        form.reset();
        this.closeModal('modalTambahDosen');
        this.loadDosenList();
        this.loadDashboard();
      } else {
        this.showToast(data.message || 'Gagal menambahkan dosen.', 'error');
      }
    } catch (err) {
      this.showToast('Terjadi kesalahan jaringan.', 'error');
    }
  },

  // 10b. Foto Profil Upload Handlers
  async uploadFotoProfil(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    if (file.size > 5 * 1024 * 1024) {
      this.showToast('Ukuran foto maksimal 5MB.', 'error');
      input.value = '';
      return;
    }

    const formData = new FormData();
    formData.append('foto_profil', file);
    if (this.state.user && this.state.user.nidn) {
      formData.append('nidn', this.state.user.nidn);
    }
    if (this.state.user && this.state.user.id) {
      formData.append('dosen_id', this.state.user.id);
    }

    this.showToast('Mengunggah foto profil...', 'info');

    try {
      const res = await fetch('api/dosen.php?action=upload_foto', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.status === 'success') {
        this.showToast(data.message, 'success');
        if (this.state.user) {
          this.state.user.foto = data.foto;
        }
        this.updateUserUI();
        this.loadDosenList();
      } else {
        this.showToast(data.message || 'Gagal mengunggah foto.', 'error');
      }
    } catch (err) {
      console.error(err);
      this.showToast('Terjadi kesalahan saat mengunggah foto.', 'error');
    } finally {
      input.value = '';
    }
  },

  async uploadFotoDosenDetail(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    if (file.size > 5 * 1024 * 1024) {
      this.showToast('Ukuran foto maksimal 5MB.', 'error');
      input.value = '';
      return;
    }

    const formData = new FormData();
    formData.append('foto_profil', file);
    if (this.activeDetailDosenId) {
      formData.append('dosen_id', this.activeDetailDosenId);
    }
    if (this.activeDetailDosenNidn) {
      formData.append('nidn', this.activeDetailDosenNidn);
    }

    this.showToast('Mengunggah foto dosen...', 'info');

    try {
      const res = await fetch('api/dosen.php?action=upload_foto', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.status === 'success') {
        this.showToast(data.message, 'success');
        const photoEl = document.getElementById('modalDetailDosenFoto');
        if (photoEl) {
          photoEl.innerHTML = `<img src="${data.foto}?v=${Date.now()}" class="w-full h-full object-cover">`;
        }
        if (this.state.user && (this.state.user.nidn === this.activeDetailDosenNidn || this.state.user.id == this.activeDetailDosenId)) {
          this.state.user.foto = data.foto;
          this.updateUserUI();
        }
        this.loadDosenList();
      } else {
        this.showToast(data.message || 'Gagal mengunggah foto.', 'error');
      }
    } catch (err) {
      console.error(err);
      this.showToast('Terjadi kesalahan saat mengunggah foto.', 'error');
    } finally {
      input.value = '';
    }
  },

  async confirmDeleteDosen(dosenId, dosenNama) {
    const isBendahara = this.state.user && ['bendahara', 'kaprodi'].includes(this.state.user.role);
    if (!isBendahara) {
      this.showToast('Hanya Bendahara / Kaprodi yang dapat menghapus data dosen.', 'error');
      return;
    }

    if (!confirm(`Apakah Anda yakin ingin menghapus data dosen "${dosenNama}"?\n\nPerhatian: Data iuran terkait dosen ini juga akan ikut terhapus.`)) {
      return;
    }

    try {
      const res = await fetch('api/dosen.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: dosenId })
      });
      const data = await res.json();
      if (data.status === 'success') {
        this.showToast(data.message, 'success');
        this.closeModal('modalDosenDetail');
        this.loadDosenList();
        this.loadDashboard();
      } else {
        this.showToast(data.message || 'Gagal menghapus dosen.', 'error');
      }
    } catch (err) {
      console.error(err);
      this.showToast('Terjadi kesalahan jaringan.', 'error');
    }
  },

  deleteActiveDosen() {
    if (!this.activeDetailDosenId) return;
    const namaEl = document.getElementById('modalDetailDosenTitle');
    const nama = namaEl ? namaEl.textContent : 'Dosen ini';
    this.confirmDeleteDosen(this.activeDetailDosenId, nama);
  },

  // 11. Toast Utility
  showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    const bg = type === 'success' ? 'bg-emerald-600' : (type === 'error' ? 'bg-rose-600' : 'bg-slate-800');
    toast.className = `${bg} text-white px-4 py-2.5 rounded-xl shadow-lg text-xs flex items-center gap-2 transform transition-all duration-300 translate-y-2 opacity-0 font-medium`;
    toast.innerHTML = `<span>${message}</span>`;

    container.appendChild(toast);
    setTimeout(() => {
      toast.classList.remove('translate-y-2', 'opacity-0');
    }, 10);

    setTimeout(() => {
      toast.classList.add('opacity-0', 'translate-y-2');
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  },

  // Helpers
  formatRupiah(number) {
    return 'Rp ' + Number(number).toLocaleString('id-ID');
  },

  formatDateIndo(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
