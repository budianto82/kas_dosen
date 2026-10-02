<?php
/**
 * Helper Utilities for Kas Dosen UNPAM
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('AUTH_SECRET')) {
    define('AUTH_SECRET', 'kas_dosen_unpam_secret_key_2026_x99');
}

function generateAuthToken(array $user): string {
    // Pastikan token ringkas, cepat, dan aman (< 300 bytes)
    // JANGAN PERNAH menyertakan data biner/base64 foto ke dalam token/cookie
    $safeUser = [
        'id' => $user['id'] ?? null,
        'username' => $user['username'] ?? '',
        'role' => $user['role'] ?? 'dosen',
        'nidn' => $user['nidn'] ?? '',
        'nama' => $user['nama'] ?? ''
    ];
    $data = [
        'user' => $safeUser,
        'exp' => time() + (86400 * 30) // 30 hari
    ];
    $payload = base64_encode(json_encode($data));
    $signature = hash_hmac('sha256', $payload, AUTH_SECRET);
    return $payload . '.' . $signature;
}

function getAuthUser(): ?array {
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    // Ambil token dari header Authorization, X-Auth-Token, Cookie, atau Request
    $token = null;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    
    if (empty($authHeader) && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            $lower = strtolower($k);
            if ($lower === 'authorization' || $lower === 'x-auth-token') {
                $authHeader = $v;
                break;
            }
        }
    }

    if (!empty($authHeader)) {
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } elseif (strpos($authHeader, '.') !== false) {
            $token = trim($authHeader);
        }
    }

    if (empty($token) && !empty($_COOKIE['kas_token'])) {
        $token = $_COOKIE['kas_token'];
    }
    
    if (empty($token) && !empty($_REQUEST['auth_token'])) {
        $token = $_REQUEST['auth_token'];
    }
    
    if (empty($token) && !empty($_POST['auth_token'])) {
        $token = $_POST['auth_token'];
    }

    if (!empty($token)) {
        // Abaikan token jika ukurannya tidak wajar (mencegah payload bloat / HTTP 431)
        if (strlen($token) > 2048) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) === 2) {
            [$payload, $sig] = $parts;
            if (hash_equals(hash_hmac('sha256', $payload, AUTH_SECRET), $sig)) {
                $decoded = json_decode(base64_decode($payload), true);
                if (is_array($decoded) && isset($decoded['user']) && ($decoded['exp'] ?? 0) > time()) {
                    $_SESSION['user'] = $decoded['user'];
                    return $decoded['user'];
                }
            }
        }
    }

    return null;
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
    $user = getAuthUser();
    if (!$user) {
        jsonResponse([
            'status' => 'error',
            'code' => 'UNAUTHORIZED',
            'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.'
        ], 401);
    }

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
        @mkdir($absDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $destination = $absDir . '/' . $filename;

    if (@move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . trim($targetDir, '/') . '/' . $filename;
    }

    // Fallback untuk Vercel / Cloud serverless (filesystem read-only)
    // Simpan gambar langsung sebagai Base64 Data URI yang dikompresi
    if (str_starts_with($mimeType, 'image/')) {
        $content = @file_get_contents($file['tmp_name']);
        if ($content !== false) {
            // Jika GD tersedia dan file > 50KB, perkecil resolusi agar base64 ringkas (< 30KB)
            if (function_exists('imagecreatefromstring') && strlen($content) > 50000) {
                $srcImg = @imagecreatefromstring($content);
                if ($srcImg !== false) {
                    $w = imagesx($srcImg);
                    $h = imagesy($srcImg);
                    $maxDim = 320;
                    if ($w > $maxDim || $h > $maxDim) {
                        $scaled = imagescale($srcImg, $maxDim);
                    } else {
                        $scaled = $srcImg;
                    }
                    ob_start();
                    imagejpeg($scaled, null, 80);
                    $content = ob_get_clean();
                    $mimeType = 'image/jpeg';
                    imagedestroy($srcImg);
                    if ($scaled !== $srcImg) imagedestroy($scaled);
                }
            }
            return 'data:' . $mimeType . ';base64,' . base64_encode($content);
        }
    }

    return null;
}
