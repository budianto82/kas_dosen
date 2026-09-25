<?php
/**
 * Iuran Kas Management API
 * Kas Dosen UNPAM Prodi Sistem Informasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : null;
        $tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : (int)date('Y');
        $status = trim($_GET['status'] ?? '');
        $dosenId = isset($_GET['dosen_id']) ? (int)$_GET['dosen_id'] : null;

        $query = "SELECT i.*, d.nama, d.gelar, d.nidn, d.no_hp
                  FROM iuran i
                  JOIN dosen d ON i.dosen_id = d.id
                  WHERE i.tahun = :tahun";
        $params = [':tahun' => $tahun];

        if ($bulan !== null) {
            $query .= " AND i.bulan = :bulan";
            $params[':bulan'] = $bulan;
        }

        if (!empty($status)) {
            $query .= " AND i.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($dosenId)) {
            $query .= " AND i.dosen_id = :dosen_id";
            $params[':dosen_id'] = $dosenId;
        }

        $query .= " ORDER BY i.tanggal_bayar DESC, i.id DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['nama_lengkap'] = $item['nama'] . ($item['gelar'] ? ', ' . $item['gelar'] : '');
            $item['nominal_formatted'] = formatRupiah($item['nominal']);
        }

        jsonResponse(['status' => 'success', 'data' => $items]);
        break;

    case 'matrix':
        $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

        // Ambil semua dosen aktif
        $dosenList = $pdo->query("SELECT id, nidn, nama, gelar, no_hp FROM dosen WHERE status = 'aktif' ORDER BY nama ASC")->fetchAll();

        // Ambil semua iuran di tahun ini
        $stmtIuran = $pdo->prepare("SELECT dosen_id, bulan, status, nominal, tanggal_bayar, metode_bayar FROM iuran WHERE tahun = ?");
        $stmtIuran->execute([$tahun]);
        $allIuran = $stmtIuran->fetchAll();

        // Map iuran per dosen dan per bulan
        $iuranMap = [];
        foreach ($allIuran as $iu) {
            $key = $iu['dosen_id'] . '_' . $iu['bulan'];
            $iuranMap[$key] = $iu;
        }

        $matrix = [];
        foreach ($dosenList as $d) {
            $bulanStatus = [];
            $totalBayar = 0;
            $bulanLunasCount = 0;

            for ($m = 1; $m <= 12; $m++) {
                $key = $d['id'] . '_' . $m;
                if (isset($iuranMap[$key])) {
                    $item = $iuranMap[$key];
                    $bulanStatus[$m] = [
                        'status' => $item['status'],
                        'nominal' => $item['nominal'],
                        'tanggal' => $item['tanggal_bayar'],
                        'metode' => $item['metode_bayar']
                    ];
                    if ($item['status'] === 'lunas') {
                        $totalBayar += (float)$item['nominal'];
                        $bulanLunasCount++;
                    }
                } else {
                    $bulanStatus[$m] = [
                        'status' => 'belum_bayar',
                        'nominal' => 0,
                        'tanggal' => null,
                        'metode' => null
                    ];
                }
            }

            // Bersihkan format WA
            $cleanPhone = preg_replace('/[^0-9]/', '', $d['no_hp']);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }

            $matrix[] = [
                'dosen_id' => $d['id'],
                'nidn' => $d['nidn'],
                'nama' => $d['nama'],
                'gelar' => $d['gelar'],
                'nama_lengkap' => $d['nama'] . ($d['gelar'] ? ', ' . $d['gelar'] : ''),
                'no_hp' => $d['no_hp'],
                'wa_phone' => $cleanPhone,
                'bulan' => $bulanStatus,
                'total_bayar' => $totalBayar,
                'total_bayar_formatted' => formatRupiah($totalBayar),
                'bulan_lunas_count' => $bulanLunasCount
            ];
        }

        jsonResponse([
            'status' => 'success',
            'tahun' => $tahun,
            'matrix' => $matrix
        ]);
        break;

    case 'pay':
        requireAuth(['bendahara', 'kaprodi']);
        // Mendukung upload bukti transfer jika melalui FormData
        $input = getJsonInput();

        $dosenId = (int)($input['dosen_id'] ?? 0);
        $bulanList = $input['bulan'] ?? []; // bisa array bulan jika bayar borongan
        $tahun = (int)($input['tahun'] ?? date('Y'));
        $nominalPerBulan = (float)($input['nominal'] ?? 30000);
        $tanggalBayar = $input['tanggal_bayar'] ?? date('Y-m-d');
        $metodeBayar = $input['metode_bayar'] ?? 'transfer';
        $keterangan = trim($input['keterangan'] ?? '');
        $status = trim($input['status'] ?? 'lunas'); // default lunas jika diinput bendahara

        if (!$dosenId) {
            jsonResponse(['status' => 'error', 'message' => 'Dosen wajib dipilih.'], 400);
        }

        // Normalisasi bulan (bisa single int atau array)
        if (!is_array($bulanList)) {
            $bulanList = [(int)$bulanList];
        }

        if (empty($bulanList)) {
            jsonResponse(['status' => 'error', 'message' => 'Pilih minimal satu bulan iuran.'], 400);
        }

        // Cek bukti transfer jika ada
        $buktiPath = handleFileUpload('bukti_bayar', 'bukti_bayar');

        $stmtCheck = $pdo->prepare("SELECT id FROM iuran WHERE dosen_id = ? AND bulan = ? AND tahun = ?");
        $stmtInsert = $pdo->prepare("INSERT INTO iuran (dosen_id, bulan, tahun, nominal, tanggal_bayar, metode_bayar, bukti_bayar, status, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtUpdate = $pdo->prepare("UPDATE iuran SET nominal = ?, tanggal_bayar = ?, metode_bayar = ?, bukti_bayar = COALESCE(?, bukti_bayar), status = ?, keterangan = ? WHERE id = ?");

        $berhasil = 0;
        foreach ($bulanList as $bln) {
            $bln = (int)$bln;
            if ($bln < 1 || $bln > 12) continue;

            $stmtCheck->execute([$dosenId, $bln, $tahun]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                $stmtUpdate->execute([$nominalPerBulan, $tanggalBayar, $metodeBayar, $buktiPath, $status, $keterangan, $existingId]);
            } else {
                $stmtInsert->execute([$dosenId, $bln, $tahun, $nominalPerBulan, $tanggalBayar, $metodeBayar, $buktiPath, $status, $keterangan]);
            }
            $berhasil++;
        }

        jsonResponse([
            'status' => 'success',
            'message' => "Berhasil mencatat iuran untuk $berhasil bulan."
        ]);
        break;

    case 'submit_transfer':
        $input = getJsonInput();
        $dosenId = (int)($input['dosen_id'] ?? 0);
        $bulan = (int)($input['bulan'] ?? date('n'));
        $tahun = (int)($input['tahun'] ?? date('Y'));
        $keterangan = trim($input['keterangan'] ?? 'Transfer via Mobile');
        $bukti = $input['bukti_bayar'] ?? '';

        // Jika upload file gambar langsung
        if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = handleFileUpload('bukti_bayar', 'bukti_bayar');
            if ($uploadedPath) {
                $bukti = $uploadedPath;
            }
        }

        if (!$dosenId) {
            jsonResponse(['status' => 'error', 'message' => 'Pilih data dosen terlebih dahulu.'], 400);
        }
        if ($bulan < 1 || $bulan > 12) {
            jsonResponse(['status' => 'error', 'message' => 'Bulan iuran tidak valid.'], 400);
        }
        if (empty($bukti)) {
            jsonResponse(['status' => 'error', 'message' => 'Foto bukti transfer wajib dilampirkan.'], 400);
        }

        // Ambil tarif iuran bulanan dari pengaturan (default 30000)
        $tarifIuran = (float)($pdo->query("SELECT setting_value FROM pengaturan WHERE setting_key = 'nominal_iuran_bulanan'")->fetchColumn() ?: 30000);

        // Cek apakah sudah pernah bayar pada periode ini
        $stmtCheck = $pdo->prepare("SELECT id, status FROM iuran WHERE dosen_id = ? AND bulan = ? AND tahun = ?");
        $stmtCheck->execute([$dosenId, $bulan, $tahun]);
        $existing = $stmtCheck->fetch();

        $tanggalNow = date('Y-m-d');
        if ($existing) {
            if ($existing['status'] === 'lunas') {
                jsonResponse(['status' => 'error', 'message' => 'Iuran untuk bulan dan tahun ini sudah berstatus LUNAS.'], 400);
            }
            $stmtUpdate = $pdo->prepare("UPDATE iuran SET nominal = ?, tanggal_bayar = ?, metode_bayar = 'transfer', bukti_bayar = ?, status = 'pending', keterangan = ? WHERE id = ?");
            $stmtUpdate->execute([$tarifIuran, $tanggalNow, $bukti, $keterangan, $existing['id']]);
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO iuran (dosen_id, bulan, tahun, nominal, tanggal_bayar, metode_bayar, bukti_bayar, status, keterangan) VALUES (?, ?, ?, ?, ?, 'transfer', ?, 'pending', ?)");
            $stmtInsert->execute([$dosenId, $bulan, $tahun, $tarifIuran, $tanggalNow, $bukti, $keterangan]);
        }

        jsonResponse([
            'status' => 'success',
            'message' => 'Bukti transfer berhasil dikirim! Menunggu validasi oleh Bendahara.'
        ]);
        break;

    case 'pending_list':
        requireAuth(['bendahara', 'kaprodi']);
        $stmtPending = $pdo->query("
            SELECT i.*, d.nama, d.gelar, d.nidn, d.no_hp
            FROM iuran i
            JOIN dosen d ON i.dosen_id = d.id
            WHERE i.status = 'pending'
            ORDER BY i.id DESC
        ");
        $pendingItems = $stmtPending->fetchAll();
        $namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        foreach ($pendingItems as &$pItem) {
            $pItem['nama_lengkap'] = $pItem['nama'] . ($pItem['gelar'] ? ', ' . $pItem['gelar'] : '');
            $pItem['nominal_formatted'] = formatRupiah($pItem['nominal']);
            $pItem['periode_formatted'] = ($namaBulan[$pItem['bulan']] ?? $pItem['bulan']) . ' ' . $pItem['tahun'];
        }
        jsonResponse(['status' => 'success', 'data' => $pendingItems]);
        break;

    case 'verify':
        requireAuth(['bendahara', 'kaprodi']);
        $input = getJsonInput();
        $id = (int)($input['id'] ?? 0);
        $status = trim($input['status'] ?? 'lunas');
        $catatan = trim($input['catatan'] ?? '');

        if (!$id || !in_array($status, ['lunas', 'ditolak', 'pending'])) {
            jsonResponse(['status' => 'error', 'message' => 'Parameter verifikasi tidak valid.'], 400);
        }

        // Ambil nominal dinamis dari pengaturan jika nominal belum ada
        $tarifIuran = (float)($pdo->query("SELECT setting_value FROM pengaturan WHERE setting_key = 'nominal_iuran_bulanan'")->fetchColumn() ?: 30000);

        $stmt = $pdo->prepare("UPDATE iuran SET status = ?, nominal = CASE WHEN nominal <= 0 THEN ? ELSE nominal END, catatan_bendahara = ?, tanggal_bayar = CURRENT_DATE WHERE id = ?");
        $stmt->execute([$status, $tarifIuran, $catatan, $id]);

        $pesan = $status === 'lunas' 
            ? 'Bukti transfer berhasil divalidasi dan iuran langsung tercatat LUNAS!' 
            : 'Bukti transfer telah ditolak.';

        jsonResponse(['status' => 'success', 'message' => $pesan]);
        break;

    case 'delete':
        requireAuth(['bendahara']);
        $input = getJsonInput();
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            jsonResponse(['status' => 'error', 'message' => 'ID Iuran tidak valid.'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM iuran WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['status' => 'success', 'message' => 'Data iuran berhasil dihapus.']);
        break;

    case 'wa_template':
        $dosenId = (int)($_GET['dosen_id'] ?? 0);
        $bulan = (int)($_GET['bulan'] ?? date('n'));
        $tahun = (int)($_GET['tahun'] ?? date('Y'));

        $stmt = $pdo->prepare("SELECT nama, gelar, no_hp FROM dosen WHERE id = ?");
        $stmt->execute([$dosenId]);
        $dosen = $stmt->fetch();

        if (!$dosen) {
            jsonResponse(['status' => 'error', 'message' => 'Dosen tidak ditemukan.'], 404);
        }

        $pengaturan = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);
        $nominal = formatRupiah((float)($pengaturan['nominal_iuran_bulanan'] ?? 30000));
        $namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$bulan - 1];

        $pesan = "Assalamu'alaikum Wr. Wb.\n"
               . "Yth. Bpk/Ibu *" . $dosen['nama'] . ($dosen['gelar'] ? ', ' . $dosen['gelar'] : '') . "*,\n\n"
               . "Mengingatkan untuk partisipasi *Iuran Kas Dosen Prodi Sistem Informasi Universitas Pamulang*:\n"
               . "📌 Periode: *" . $namaBulan . " " . $tahun . "*\n"
               . "💰 Nominal: *" . $nominal . "*\n\n"
               . "Pembayaran dapat ditransfer melalui:\n"
               . "🏦 *" . ($pengaturan['nama_bank'] ?? 'Bank BCA') . "*\n"
               . "💳 Rek: *" . ($pengaturan['nomor_rekening'] ?? '-') . "*\n"
               . "👤 a.n: *" . ($pengaturan['atas_nama'] ?? 'Kas Dosen SI') . "*\n\n"
               . "Mohon konfirmasi bukti transfer via aplikasi kas dosen atau membalas pesan ini. Terima kasih atas partisipasi dan kebersamaannya 🙏.\n\n"
               . "Salam hangat,\n*Bendahara Kas Dosen SI UNPAM*";

        $cleanPhone = preg_replace('/[^0-9]/', '', $dosen['no_hp']);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $waUrl = "https://wa.me/" . $cleanPhone . "?text=" . urlencode($pesan);

        jsonResponse([
            'status' => 'success',
            'pesan' => $pesan,
            'wa_url' => $waUrl
        ]);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak dikenali.'], 400);
}
