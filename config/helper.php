<?php
/**
 * Helper Utilities for Kas Dosen UNPAM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? array_merge($_POST, $data) : $_POST;
}

function requireAuth(array $allowedRoles = []): array {
    if (!isset($_SESSION['user'])) {
        jsonResponse([
            'status' => 'error',
            'code' => 'UNAUTHORIZED',
            'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.'
        ], 401);
    }

    $user = $_SESSION['user'];
    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
        jsonResponse([
            'status' => 'error',
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki hak akses untuk fitur ini.'
        ], 403);
    }

    return $user;
}

function formatRupiah(float|int $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function handleFileUpload(string $inputName, string $targetDir): ?string {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$inputName];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    
    // Validasi tipe mime
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }

    // Maksimal 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $absDir = __DIR__ . '/../uploads/' . trim($targetDir, '/');
    if (!is_dir($absDir)) {
        mkdir($absDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $destination = $absDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . trim($targetDir, '/') . '/' . $filename;
    }

    return null;
}
