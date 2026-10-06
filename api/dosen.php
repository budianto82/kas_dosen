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

        // Ambil mapping foto dari users untuk fallback dosen yang fotonya tersimpan di tabel users / profil
        $userPhotos = [];
        try {
            $uStmt = $pdo->query("SELECT nidn, username, foto FROM users WHERE foto IS NOT NULL AND foto != ''");
            while ($uRow = $uStmt->fetch()) {
                if (!empty($uRow['nidn'])) {
                    $nidnClean = trim($uRow['nidn']);
                    $userPhotos[$nidnClean] = $uRow['foto'];
                    $userPhotos[ltrim($nidnClean, '0')] = $uRow['foto'];
                }
                if (!empty($uRow['username'])) {
                    $uNameClean = trim($uRow['username']);
                    $userPhotos[$uNameClean] = $uRow['foto'];
                    $userPhotos[ltrim($uNameClean, '0')] = $uRow['foto'];
                }
            }
        } catch (Throwable $e) {}

        // Format data dosen
        foreach ($dosenList as &$d) {
            $nidnClean = trim((string)($d['nidn'] ?? ''));
            $uPhoto = $userPhotos[$nidnClean] ?? ($userPhotos[ltrim($nidnClean, '0')] ?? null);
            if (empty($d['foto']) && !empty($uPhoto)) {
                $d['foto'] = $uPhoto;
            } elseif (!empty($uPhoto) && str_starts_with($uPhoto, 'data:') && !str_starts_with((string)$d['foto'], 'data:')) {
                // Utamakan Base64 Data URI jika tersimpan di profil user
                $d['foto'] = $uPhoto;
            }

            $d['nama_lengkap'] = $d['nama'] . ($d['gelar'] ? ', ' . $d['gelar'] : '');
            $d['lunas_bulan_ini'] = (int)$d['status_bulan_ini'] > 0;
            $d['total_kontribusi_formatted'] = formatRupiah($d['total_kontribusi']);
            // Nomor WA yang siap dipakai wa.me
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)($d['no_hp'] ?? ''));
            if (!empty($cleanPhone) && str_starts_with($cleanPhone, '0')) {
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

        // Sinkronkan fallback foto dengan tabel users
        if (!empty($dosen['nidn'])) {
            try {
                $nidnClean = trim($dosen['nidn']);
                $uStmt = $pdo->prepare("SELECT foto FROM users WHERE (nidn = ? OR username = ? OR nidn = ? OR username = ?) AND foto IS NOT NULL AND foto != '' ORDER BY id DESC LIMIT 1");
                $uStmt->execute([$nidnClean, $nidnClean, ltrim($nidnClean, '0'), ltrim($nidnClean, '0')]);
                $uPhoto = $uStmt->fetchColumn();
                if (empty($dosen['foto']) && !empty($uPhoto)) {
                    $dosen['foto'] = $uPhoto;
                } elseif (!empty($uPhoto) && str_starts_with($uPhoto, 'data:') && !str_starts_with((string)$dosen['foto'], 'data:')) {
                    $dosen['foto'] = $uPhoto;
                }
            } catch (Throwable $e) {}
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

        if (empty($nidn) || empty($nama)) {
            jsonResponse(['status' => 'error', 'message' => 'NIDOS dan Nama Lengkap wajib diisi.'], 400);
        }

        // Cek duplikasi NIDOS
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM dosen WHERE nidn = ?");
        $stmtCheck->execute([$nidn]);
        if ($stmtCheck->fetchColumn() > 0) {
            jsonResponse(['status' => 'error', 'message' => 'Dosen dengan NIDOS ini sudah terdaftar.'], 400);
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
            jsonResponse(['status' => 'error', 'message' => 'NIDOS dan Nama Lengkap wajib diisi.'], 400);
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
        $currentUser = requireAuth();

        $target = trim($_POST['target'] ?? '');
        $dosenId = (int)($_POST['dosen_id'] ?? 0);
        $nidn = trim($_POST['nidn'] ?? '');

        $fotoPath = handleFileUpload('foto_profil', 'foto_profil');
        if (!$fotoPath && !empty($_POST['foto_base64'])) {
            $fotoPath = $_POST['foto_base64'];
        }

        if (!$fotoPath) {
            jsonResponse(['status' => 'error', 'message' => 'Gagal mengupload foto. Pastikan format JPG, PNG, atau WEBP dan ukuran maksimal 5MB.'], 400);
        }

        $isBendahara = in_array($currentUser['role'] ?? '', ['bendahara', 'kaprodi']);

        // Jika upload foto untuk detail dosen tertentu oleh bendahara
        if ($target === 'dosen_detail' && $dosenId > 0) {
            if (!$isBendahara) {
                jsonResponse(['status' => 'error', 'message' => 'Hanya bendahara yang berhak mengubah foto dosen lain.'], 403);
            }

            try {
                $stmtD = $pdo->prepare("SELECT id, nidn FROM dosen WHERE id = ?");
                $stmtD->execute([$dosenId]);
                $targetDosen = $stmtD->fetch();

                if ($targetDosen) {
                    $stmtUpdate = $pdo->prepare("UPDATE dosen SET foto = ? WHERE id = ?");
                    $stmtUpdate->execute([$fotoPath, $dosenId]);

                    if (!empty($targetDosen['nidn'])) {
                        $stmtUserUpdate = $pdo->prepare("UPDATE users SET foto = ? WHERE nidn = ? OR username = ?");
                        $stmtUserUpdate->execute([$fotoPath, $targetDosen['nidn'], $targetDosen['nidn']]);
                    }

                    if (($currentUser['nidn'] ?? '') === $targetDosen['nidn']) {
                        $_SESSION['user']['foto'] = $fotoPath;
                        $currentUser['foto'] = $fotoPath;
                        $newToken = generateAuthToken($_SESSION['user']);
                        setcookie('kas_token', $newToken, time() + (86400 * 30), '/', '', false, false);
                    }
                }
            } catch (Throwable $e) {}

            jsonResponse([
                'status' => 'success',
                'message' => 'Foto dosen berhasil diunggah!',
                'foto' => $fotoPath
            ]);
        } else {
            // Upload foto profil akun sendiri (Self)
            $userNidn = $currentUser['nidn'] ?? '';
            $userId = (int)($currentUser['id'] ?? 0);
            $username = $currentUser['username'] ?? '';

            try {
                // Update di tabel users (semua record yang cocok)
                if ($userId || !empty($username) || !empty($userNidn)) {
                    $stmtUp = $pdo->prepare("UPDATE users SET foto = ? WHERE id = ? OR username = ? OR (nidn IS NOT NULL AND nidn != '' AND nidn = ?)");
                    $stmtUp->execute([$fotoPath, $userId, $username, $userNidn]);
                }

                // Update di tabel dosen jika ada record NIDN atau dosen_id
                $dosenNidn = !empty($userNidn) ? $userNidn : $username;
                if (!empty($dosenNidn)) {
                    $stmtDosenUpdate = $pdo->prepare("UPDATE dosen SET foto = ? WHERE nidn = ? OR nidn = ?");
                    $stmtDosenUpdate->execute([$fotoPath, $dosenNidn, ltrim($dosenNidn, '0')]);
                }
                if (!empty($currentUser['dosen_id'])) {
                    $stmtDosenId = $pdo->prepare("UPDATE dosen SET foto = ? WHERE id = ?");
                    $stmtDosenId->execute([$fotoPath, (int)$currentUser['dosen_id']]);
                }
            } catch (Throwable $e) {}

            $_SESSION['user']['foto'] = $fotoPath;
            $currentUser['foto'] = $fotoPath;
            $newToken = generateAuthToken($_SESSION['user']);
            setcookie('kas_token', $newToken, time() + (86400 * 30), '/', '', false, false);

            jsonResponse([
                'status' => 'success',
                'message' => 'Foto profil berhasil diunggah!',
                'foto' => $fotoPath,
                'user' => $currentUser,
                'token' => $newToken
            ]);
        }
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak dikenali.'], 400);
}
