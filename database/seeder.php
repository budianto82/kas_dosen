<?php
/**
 * Database Seeder for Kas Dosen UNPAM Prodi Sistem Informasi
 */

require_once __DIR__ . '/../config/database.php';

function runSeeder(): array {
    $pdo = getDBConnection();

    $results = [];

    // 1. Seed Pengaturan
    $settings = [
        'nama_kampus' => 'Universitas Pamulang',
        'nama_fakultas' => 'Fakultas Ilmu Komputer',
        'nama_prodi' => 'Sistem Informasi (S1)',
        'nominal_iuran_bulanan' => '30000',
        'nama_bank' => 'Bank BCA',
        'nomor_rekening' => '7401234567',
        'atas_nama' => 'Kas Dosen SI UNPAM (Bendahara)',
        'kontak_bendahara' => '6281298765432',
        'nama_bendahara' => 'Siti Rohmah, S.Kom., M.Kom.'
    ];

    $stmt = $pdo->prepare("REPLACE INTO pengaturan (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $key => $val) {
        $stmt->execute([$key, $val]);
    }
    $results[] = "Pengaturan default berhasil disimpan.";

    // 2. Seed Akun Pengurus / Users
    $checkUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUser == 0) {
        $users = [
            [
                'username' => 'bendahara',
                'password_hash' => password_hash('unpam123', PASSWORD_BCRYPT),
                'nama' => 'Siti Rohmah, S.Kom., M.Kom.',
                'role' => 'bendahara',
                'nidn' => '0412058801',
                'no_hp' => '081298765432'
            ],
            [
                'username' => 'kaprodi',
                'password_hash' => password_hash('unpam123', PASSWORD_BCRYPT),
                'nama' => 'Dr. Ir. Ahmad Sudrajat, M.Kom.',
                'role' => 'kaprodi',
                'nidn' => '0408037501',
                'no_hp' => '081311223344'
            ]
        ];

        $stmtUser = $pdo->prepare("INSERT INTO users (username, password_hash, nama, role, nidn, no_hp) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($users as $u) {
            $stmtUser->execute([$u['username'], $u['password_hash'], $u['nama'], $u['role'], $u['nidn'], $u['no_hp']]);
        }
        $results[] = "Akun Bendahara & Kaprodi berhasil dibuat (Username: bendahara / kaprodi, Password: unpam123).";
    }

    // 3. Seed Kategori Pengeluaran
    $kategori = [
        ['Rapat & Koordinasi Prodi', 'Konsumsi dan kebutuhan rapat rutin dosen program studi'],
        ['Konsumsi Harian Ruang Dosen', 'Air mineral galon, kopi, teh, dan snack di ruang dosen'],
        ['Tali Kasih & Santunan', 'Bantuan untuk dosen/keluarga yang sakit, melahirkan, atau berduka'],
        ['ATK & Operasional', 'Kertas, spidol whiteboard, tinta printer, dan perlengkapan prodi'],
        ['Kegiatan & Akreditasi', 'Dukungan akreditasi LAM-INFOKOM, lokakarya kurikulum, & seminar']
    ];

    $checkKategori = $pdo->query("SELECT COUNT(*) FROM kategori_pengeluaran")->fetchColumn();
    if ($checkKategori == 0) {
        $stmtKat = $pdo->prepare("INSERT INTO kategori_pengeluaran (nama_kategori, deskripsi) VALUES (?, ?)");
        foreach ($kategori as $k) {
            $stmtKat->execute([$k[0], $k[1]]);
        }
        $results[] = "Kategori pengeluaran berhasil diisi.";
    }

    // 4. Seed Data Dosen Prodi Sistem Informasi
    $checkDosen = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
    if ($checkDosen == 0) {
        $dosenList = [
            ['0408037501', 'Dr. Ir. Ahmad Sudrajat', 'M.Kom.', '081311223344', 'ahmad.sudrajat@unpam.ac.id', 'Ketua Program Studi'],
            ['0412058801', 'Siti Rohmah', 'S.Kom., M.Kom.', '081298765432', 'siti.rohmah@unpam.ac.id', 'Sekretaris / Bendahara'],
            ['0419078602', 'Budi Santoso', 'M.Kom.', '081288991122', 'budi.santoso@unpam.ac.id', 'Dosen Tetap'],
            ['0422048903', 'Nurul Hidayati', 'S.Kom., M.M.S.I.', '085712345678', 'nurul.hidayati@unpam.ac.id', 'Dosen Tetap'],
            ['0415108401', 'Hendro Wicaksono', 'M.Kom.', '087890123456', 'hendro.w@unpam.ac.id', 'Dosen Tetap'],
            ['0430069002', 'Dian Pratama', 'S.Kom., M.Kom.', '082134567890', 'dian.pratama@unpam.ac.id', 'Dosen Tetap'],
            ['0405118704', 'Rina Anggraini', 'M.Kom.', '081345678901', 'rina.anggraini@unpam.ac.id', 'Dosen Tetap'],
            ['0418018502', 'Fajar Ramadhan', 'S.Kom., M.Kom.', '085612345678', 'fajar.r@unpam.ac.id', 'Dosen Tetap'],
            ['0425098803', 'Eka Setiawan', 'M.T.I.', '081223344556', 'eka.setiawan@unpam.ac.id', 'Dosen Tetap'],
            ['0411129101', 'Tri Wahyuni', 'S.Kom., M.Kom.', '087788990011', 'tri.wahyuni@unpam.ac.id', 'Dosen Tetap']
        ];

        $stmtDosen = $pdo->prepare("INSERT INTO dosen (nidn, nama, gelar, no_hp, email, jabatan) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($dosenList as $d) {
            $stmtDosen->execute([$d[0], $d[1], $d[2], $d[3], $d[4], $d[5]]);
        }
        $results[] = count($dosenList) . " Dosen Prodi Sistem Informasi berhasil ditambahkan.";

        // 5. Seed Transaksi Iuran (Tahun Berjalan)
        $tahunSekarang = (int)date('Y');
        $bulanSekarang = (int)date('n');

        $stmtIuran = $pdo->prepare("INSERT INTO iuran (dosen_id, bulan, tahun, nominal, tanggal_bayar, metode_bayar, status, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Tambahkan Saldo Awal Kas Periode
        $stmtIuran->execute([
            1,
            max(1, $bulanSekarang - 2),
            $tahunSekarang,
            2500000,
            date('Y-m-d', strtotime('-60 days')),
            'transfer',
            'lunas',
            'Saldo Kas Dosen Awal Tahun / Semester'
        ]);
        
        // Simulasikan beberapa dosen sudah bayar bulan ini dan bulan lalu
        $dosenRows = $pdo->query("SELECT id FROM dosen ORDER BY id ASC LIMIT 8")->fetchAll();
        foreach ($dosenRows as $idx => $row) {
            $dId = $row['id'];
            // Bulan lalu
            $bulanLalu = $bulanSekarang > 1 ? $bulanSekarang - 1 : 12;
            $tahunLalu = $bulanSekarang > 1 ? $tahunSekarang : $tahunSekarang - 1;
            $stmtIuran->execute([
                $dId,
                $bulanLalu,
                $tahunLalu,
                30000,
                sprintf('%04d-%02d-10', $tahunLalu, $bulanLalu),
                $idx % 2 === 0 ? 'transfer' : 'tunai',
                'lunas',
                'Iuran rutin bulanan'
            ]);

            // Bulan sekarang (sebagian sudah lunas)
            if ($idx < 5) {
                $stmtIuran->execute([
                    $dId,
                    $bulanSekarang,
                    $tahunSekarang,
                    30000,
                    sprintf('%04d-%02d-05', $tahunSekarang, $bulanSekarang),
                    'transfer',
                    'lunas',
                    'Iuran rutin bulanan'
                ]);
            }
        }
        $results[] = "Data riwayat iuran awal berhasil digenerate.";

        // 6. Seed Beberapa Pengeluaran
        $stmtPengeluaran = $pdo->prepare("INSERT INTO pengeluaran (kategori_id, judul, nominal, tanggal, keterangan, pj_penerima) VALUES (?, ?, ?, ?, ?, ?)");
        $pengeluaranList = [
            [1, 'Konsumsi Rapat Pleno Kurikulum OBE', 150000, date('Y-m') . '-03', 'Snack box 15 paket untuk rapat kurikulum prodi', 'Siti Rohmah'],
            [2, 'Refill Air Galon & Kopi Teh Ruang Dosen', 65000, date('Y-m') . '-05', '3 Galon Aqua + Kopi sachet & gula', 'Pak Ujang (OB)'],
            [3, 'Tali Kasih Sakit Dosen (Ibu Nurul)', 200000, date('Y-m') . '-08', 'Santunan rawat inap Rumah Sakit Buah Hati', 'Ahmad Sudrajat']
        ];

        foreach ($pengeluaranList as $p) {
            $stmtPengeluaran->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5]]);
        }
        $results[] = "Data transaksi pengeluaran awal berhasil ditambahkan.";
    }

    return $results;
}

// Jika dijalankan langsung via CLI atau browser
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $res = runSeeder();
    echo json_encode(['status' => 'success', 'messages' => $res], JSON_PRETTY_PRINT);
}
