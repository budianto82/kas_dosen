<?php
/**
 * Dosen Management API
 * Kas Dosen UNPAM Prodi Sistem Informasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$bulanIni = (int)date('n');
$tahunIni = (int)date('Y');

switch ($action) {
    case 'list':
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? 'aktif');

        $query = "SELECT d.*, 
                    (SELECT COUNT(*) FROM iuran i WHERE i.dosen_id = d.id AND i.bulan = :bulan AND i.tahun = :tahun AND i.status = 'lunas') as status_bulan_ini,
                    (SELECT COALESCE(SUM(i.nominal), 0) FROM iuran i WHERE i.dosen_id = d.id AND i.status = 'lunas') as total_kontribusi
                  FROM dosen d 
                  WHERE 1=1";
        $params = [
            ':bulan' => $bulanIni,
            ':tahun' => $tahunIni
        ];

        if (!empty($status) && $status !== 'semua') {
            $query .= " AND d.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $query .= " AND (d.nama LIKE :search OR d.nidn LIKE :search OR d.no_hp LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $query .= " ORDER BY d.nama ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $dosenList = $stmt->fetchAll();

        // Format data dosen
        foreach ($dosenList as &$d) {
            $d['nama_lengkap'] = $d['nama'] . ($d['gelar'] ? ', ' . $d['gelar'] : '');
            $d['lunas_bulan_ini'] = (int)$d['status_bulan_ini'] > 0;
            $d['total_kontribusi_formatted'] = formatRupiah($d['total_kontribusi']);
            // Nomor WA yang siap dipakai wa.me
            $cleanPhone = preg_replace('/[^0-9]/', '', $d['no_hp']);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }
            $d['wa_phone'] = $cleanPhone;
        }

        jsonResponse(['status' => 'success', 'data' => $dosenList]);
        break;

    case 'detail':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(['status' => 'error', 'message' => 'ID Dosen tidak valid.'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM dosen WHERE id = ?");
        $stmt->execute([$id]);
        $dosen = $stmt->fetch();

        if (!$dosen) {
            jsonResponse(['status' => 'error', 'message' => 'Data dosen tidak ditemukan.'], 404);
        }

        $dosen['nama_lengkap'] = $dosen['nama'] . ($dosen['gelar'] ? ', ' . $dosen['gelar'] : '');

        // Riwayat Iuran Dosen
        $stmtIuran = $pdo->prepare("SELECT * FROM iuran WHERE dosen_id = ? ORDER BY tahun DESC, bulan DESC");
        $stmtIuran->execute([$id]);
        $riwayatIuran = $stmtIuran->fetchAll();

        foreach ($riwayatIuran as &$r) {
            $r['nominal_formatted'] = formatRupiah($r['nominal']);
        }

        jsonResponse([
            'status' => 'success',
            'data' => [
                'dosen' => $dosen,
                'riwayat_iuran' => $riwayatIuran
            ]
        ]);
        break;

    case 'create':
        requireAuth(['bendahara', 'kaprodi']);
        $input = getJsonInput();

        $nidn = trim($input['nidn'] ?? '');
        $nama = trim($input['nama'] ?? '');
        $gelar = trim($input['gelar'] ?? '');
        $no_hp = trim($input['no_hp'] ?? '');
        $email = trim($input['email'] ?? '');
        $jabatan = trim($input['jabatan'] ?? 'Dosen Tetap');

        if (empty($nidn) || empty($nama) || empty($no_hp)) {
            jsonResponse(['status' => 'error', 'message' => 'NIDN, Nama, dan No. WhatsApp wajib diisi.'], 400);
        }

        // Cek duplikasi NIDN
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM dosen WHERE nidn = ?");
        $stmtCheck->execute([$nidn]);
        if ($stmtCheck->fetchColumn() > 0) {
            jsonResponse(['status' => 'error', 'message' => 'Dosen dengan NIDN ini sudah terdaftar.'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO dosen (nidn, nama, gelar, no_hp, email, jabatan, status) VALUES (?, ?, ?, ?, ?, ?, 'aktif')");
        $stmt->execute([$nidn, $nama, $gelar, $no_hp, $email, $jabatan]);

        jsonResponse(['status' => 'success', 'message' => 'Dosen baru berhasil ditambahkan!']);
        break;

    case 'update':
        requireAuth(['bendahara', 'kaprodi']);
        $input = getJsonInput();

        $id = (int)($input['id'] ?? 0);
        $nidn = trim($input['nidn'] ?? '');
        $nama = trim($input['nama'] ?? '');
        $gelar = trim($input['gelar'] ?? '');
        $no_hp = trim($input['no_hp'] ?? '');
        $email = trim($input['email'] ?? '');
        $jabatan = trim($input['jabatan'] ?? 'Dosen Tetap');
        $status = trim($input['status'] ?? 'aktif');

        if (!$id || empty($nidn) || empty($nama)) {
            jsonResponse(['status' => 'error', 'message' => 'Data tidak lengkap.'], 400);
        }

        $stmt = $pdo->prepare("UPDATE dosen SET nidn = ?, nama = ?, gelar = ?, no_hp = ?, email = ?, jabatan = ?, status = ? WHERE id = ?");
        $stmt->execute([$nidn, $nama, $gelar, $no_hp, $email, $jabatan, $status, $id]);

        jsonResponse(['status' => 'success', 'message' => 'Data dosen berhasil diperbarui!']);
        break;

    case 'delete':
        requireAuth(['bendahara', 'kaprodi']);
        $input = getJsonInput();
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            jsonResponse(['status' => 'error', 'message' => 'ID Dosen tidak valid.'], 400);
        }

        $stmtD = $pdo->prepare("SELECT id, nidn, nama FROM dosen WHERE id = ?");
        $stmtD->execute([$id]);
        $dosen = $stmtD->fetch();

        if (!$dosen) {
            jsonResponse(['status' => 'error', 'message' => 'Data dosen tidak ditemukan.'], 404);
        }

        // Cegah bendahara menghapus akun sendiri
        $currentNidn = $_SESSION['user']['nidn'] ?? '';
        if ($currentNidn === $dosen['nidn']) {
            jsonResponse(['status' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 400);
        }

        // Hapus akun login jika ada di tabel users
        $stmtU = $pdo->prepare("DELETE FROM users WHERE nidn = ? OR username = ?");
        $stmtU->execute([$dosen['nidn'], $dosen['nidn']]);

        // Hapus data dosen (tabel iuran akan terhapus otomatis via ON DELETE CASCADE)
        $stmt = $pdo->prepare("DELETE FROM dosen WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['status' => 'success', 'message' => 'Dosen ' . $dosen['nama'] . ' berhasil dihapus.']);
        break;

    case 'upload_foto':
        if (!isset($_SESSION['user'])) {
            jsonResponse(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $currentUser = $_SESSION['user'];
        $dosenId = (int)($_POST['dosen_id'] ?? 0);
        $nidn = trim($_POST['nidn'] ?? '');

        // Jika dosen_id dan nidn kosong, gunakan data user yang sedang login
        if (!$dosenId && empty($nidn) && !empty($currentUser['nidn'])) {
            $stmtFind = $pdo->prepare("SELECT id FROM dosen WHERE nidn = ?");
            $stmtFind->execute([$currentUser['nidn']]);
            $dosenId = (int)$stmtFind->fetchColumn();
            $nidn = $currentUser['nidn'];
        } elseif (!$dosenId && !empty($nidn)) {
            $stmtFind = $pdo->prepare("SELECT id FROM dosen WHERE nidn = ?");
            $stmtFind->execute([$nidn]);
            $dosenId = (int)$stmtFind->fetchColumn();
        } elseif ($dosenId && empty($nidn)) {
            $stmtFind = $pdo->prepare("SELECT nidn FROM dosen WHERE id = ?");
            $stmtFind->execute([$dosenId]);
            $nidn = $stmtFind->fetchColumn() ?: '';
        }

        $isBendahara = in_array($currentUser['role'] ?? '', ['bendahara', 'kaprodi']);
        // Jika bukan bendahara/kaprodi, hanya boleh upload fotonya sendiri
        if (!$isBendahara) {
            $currentDosenId = $currentUser['id'] ?? 0;
            $currentNidn = $currentUser['nidn'] ?? '';
            if ($dosenId != $currentDosenId && $nidn != $currentNidn) {
                jsonResponse(['status' => 'error', 'message' => 'Anda hanya berhak mengubah foto profil Anda sendiri.'], 403);
            }
        }

        $fotoPath = handleFileUpload('foto_profil', 'foto_profil');
        if (!$fotoPath) {
            jsonResponse(['status' => 'error', 'message' => 'Gagal mengupload foto. Pastikan format JPG, PNG, atau WEBP dan ukuran maksimal 5MB.'], 400);
        }

        // Update foto di tabel dosen
        if ($dosenId) {
            $stmtUpdate = $pdo->prepare("UPDATE dosen SET foto = ? WHERE id = ?");
            $stmtUpdate->execute([$fotoPath, $dosenId]);
        }

        // Update foto di tabel users jika akunnya terdaftar
        if (!empty($nidn)) {
            $stmtUserUpdate = $pdo->prepare("UPDATE users SET foto = ? WHERE nidn = ? OR username = ?");
            $stmtUserUpdate->execute([$fotoPath, $nidn, $nidn]);
        }

        // Jika user yang login sedang mengupdate fotonya sendiri, perbarui session
        if (($currentUser['nidn'] ?? '') === $nidn || ($currentUser['id'] ?? 0) == $dosenId || ($currentUser['username'] ?? '') === $nidn) {
            $_SESSION['user']['foto'] = $fotoPath;
        }

        jsonResponse([
            'status' => 'success',
            'message' => 'Foto profil berhasil diunggah!',
            'foto' => $fotoPath
        ]);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak dikenali.'], 400);
}
