<?php
/**
 * Pengeluaran Kas API
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
        $kategoriId = isset($_GET['kategori_id']) && $_GET['kategori_id'] !== '' ? (int)$_GET['kategori_id'] : null;
        $search = trim($_GET['search'] ?? '');

        $query = "SELECT p.*, k.nama_kategori 
                  FROM pengeluaran p
                  LEFT JOIN kategori_pengeluaran k ON p.kategori_id = k.id
                  WHERE EXTRACT(YEAR FROM p.tanggal) = :tahun";
        $params = [':tahun' => $tahun];

        if ($bulan !== null) {
            $query .= " AND EXTRACT(MONTH FROM p.tanggal) = :bulan";
            $params[':bulan'] = $bulan;
        }

        if ($kategoriId !== null) {
            $query .= " AND p.kategori_id = :kategori_id";
            $params[':kategori_id'] = $kategoriId;
        }

        if (!empty($search)) {
            $query .= " AND (p.judul LIKE :search OR p.keterangan LIKE :search OR p.pj_penerima LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $query .= " ORDER BY p.tanggal DESC, p.id DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        $totalNominal = 0;
        foreach ($items as &$item) {
            $item['nominal_formatted'] = formatRupiah($item['nominal']);
            $totalNominal += (float)$item['nominal'];
        }

        jsonResponse([
            'status' => 'success',
            'data' => $items,
            'total_pengeluaran' => $totalNominal,
            'total_pengeluaran_formatted' => formatRupiah($totalNominal)
        ]);
        break;

    case 'categories':
        $items = $pdo->query("SELECT * FROM kategori_pengeluaran ORDER BY nama_kategori ASC")->fetchAll();
        jsonResponse(['status' => 'success', 'data' => $items]);
        break;

    case 'create':
        requireAuth(['bendahara', 'kaprodi']);
        $input = getJsonInput();

        $judul = trim($input['judul'] ?? '');
        $nominal = (float)($input['nominal'] ?? 0);
        $kategoriId = (int)($input['kategori_id'] ?? 1);
        $tanggal = $input['tanggal'] ?? date('Y-m-d');
        $keterangan = trim($input['keterangan'] ?? '');
        $pjPenerima = trim($input['pj_penerima'] ?? '');

        if (empty($judul) || $nominal <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Judul dan nominal pengeluaran wajib diisi dengan benar.'], 400);
        }

        $buktiNota = handleFileUpload('bukti_nota', 'bukti_pengeluaran');

        $stmt = $pdo->prepare("INSERT INTO pengeluaran (kategori_id, judul, nominal, tanggal, keterangan, pj_penerima, bukti_nota) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kategoriId, $judul, $nominal, $tanggal, $keterangan, $pjPenerima, $buktiNota]);

        jsonResponse([
            'status' => 'success',
            'message' => 'Pengeluaran kas berhasil dicatat!'
        ]);
        break;

    case 'update':
        requireAuth(['bendahara', 'kaprodi']);
        $input = getJsonInput();

        $id = (int)($input['id'] ?? 0);
        $judul = trim($input['judul'] ?? '');
        $nominal = (float)($input['nominal'] ?? 0);
        $kategoriId = (int)($input['kategori_id'] ?? 1);
        $tanggal = $input['tanggal'] ?? date('Y-m-d');
        $keterangan = trim($input['keterangan'] ?? '');
        $pjPenerima = trim($input['pj_penerima'] ?? '');

        if (!$id || empty($judul) || $nominal <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Data tidak valid.'], 400);
        }

        $buktiNota = handleFileUpload('bukti_nota', 'bukti_pengeluaran');

        if ($buktiNota) {
            $stmt = $pdo->prepare("UPDATE pengeluaran SET kategori_id = ?, judul = ?, nominal = ?, tanggal = ?, keterangan = ?, pj_penerima = ?, bukti_nota = ? WHERE id = ?");
            $stmt->execute([$kategoriId, $judul, $nominal, $tanggal, $keterangan, $pjPenerima, $buktiNota, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE pengeluaran SET kategori_id = ?, judul = ?, nominal = ?, tanggal = ?, keterangan = ?, pj_penerima = ? WHERE id = ?");
            $stmt->execute([$kategoriId, $judul, $nominal, $tanggal, $keterangan, $pjPenerima, $id]);
        }

        jsonResponse(['status' => 'success', 'message' => 'Pengeluaran berhasil diperbarui!']);
        break;

    case 'delete':
        requireAuth(['bendahara']);
        $input = getJsonInput();
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            jsonResponse(['status' => 'error', 'message' => 'ID pengeluaran tidak valid.'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM pengeluaran WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['status' => 'success', 'message' => 'Data pengeluaran berhasil dihapus.']);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak dikenali.'], 400);
}
