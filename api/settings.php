<?php
/**
 * Settings API
 * Kas Dosen UNPAM Prodi Sistem Informasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        $rows = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);
        jsonResponse(['status' => 'success', 'data' => $rows]);
        break;

    case 'update':
        requireAuth(['bendahara', 'kaprodi']);
        $input = getJsonInput();

        $allowedKeys = [
            'nama_kampus', 'nama_fakultas', 'nama_prodi', 'nominal_iuran_bulanan',
            'nama_bank', 'nomor_rekening', 'atas_nama', 'kontak_bendahara', 'nama_bendahara'
        ];

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO pengaturan (setting_key, setting_value) VALUES (?, ?)");

        foreach ($input as $key => $val) {
            if (in_array($key, $allowedKeys)) {
                $stmt->execute([$key, trim((string)$val)]);
            }
        }

        jsonResponse(['status' => 'success', 'message' => 'Pengaturan aplikasi berhasil disimpan!']);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak valid.'], 400);
}
