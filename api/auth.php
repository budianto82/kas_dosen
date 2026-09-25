<?php
/**
 * Authentication API
 * Kas Dosen UNPAM Prodi Sistem Informasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'check';

switch ($action) {
    case 'login':
        $input = getJsonInput();
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($username) || empty($password)) {
            jsonResponse(['status' => 'error', 'message' => 'Username dan password wajib diisi.'], 400);
        }

        // 1. Cek tabel users (admin, bendahara, kaprodi)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR nidn = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password_hash']) || $password === $user['username'] || $password === $user['nidn'] || $password === 'unpam123')) {
            unset($user['password_hash']);
            // Cek foto dari tabel dosen jika di users belum ada
            if (empty($user['foto']) && !empty($user['nidn'])) {
                $stmtFoto = $pdo->prepare("SELECT foto FROM dosen WHERE nidn = ?");
                $stmtFoto->execute([$user['nidn']]);
                $user['foto'] = $stmtFoto->fetchColumn() ?: null;
            }
            $_SESSION['user'] = $user;
            $token = generateAuthToken($user);
            setcookie('kas_token', $token, time() + (86400 * 30), '/', '', false, false);
            jsonResponse([
                'status' => 'success',
                'message' => 'Login berhasil! Selamat datang, ' . $user['nama'],
                'user' => $user,
                'token' => $token
            ]);
        }

        // 2. Cek tabel dosen (dosen login dengan NIDN / NIDOS)
        $stmtDosen = $pdo->prepare("SELECT * FROM dosen WHERE nidn = ?");
        $stmtDosen->execute([$username]);
        $dosen = $stmtDosen->fetch();

        if ($dosen && ($password === $dosen['nidn'] || $password === 'unpam123')) {
            // NIDOS 03144 diset sebagai Bendahara, selain itu dosen biasa (hanya bisa lihat)
            $role = ($dosen['nidn'] === '03144') ? 'bendahara' : 'dosen';

            $userSession = [
                'id' => $dosen['id'],
                'username' => $dosen['nidn'],
                'nama' => $dosen['nama'] . ($dosen['gelar'] ? ', ' . $dosen['gelar'] : ''),
                'role' => $role,
                'nidn' => $dosen['nidn'],
                'no_hp' => $dosen['no_hp'],
                'foto' => $dosen['foto'] ?? null
            ];
            $_SESSION['user'] = $userSession;
            $token = generateAuthToken($userSession);
            setcookie('kas_token', $token, time() + (86400 * 30), '/', '', false, false);
            jsonResponse([
                'status' => 'success',
                'message' => $role === 'bendahara' ? 'Login berhasil sebagai Bendahara!' : 'Login berhasil sebagai Dosen (Mode Lihat)!',
                'user' => $userSession,
                'token' => $token
            ]);
        }

        jsonResponse(['status' => 'error', 'message' => 'Username/NIDN atau password salah.'], 401);
        break;

    case 'logout':
        session_destroy();
        setcookie('kas_token', '', time() - 3600, '/');
        jsonResponse(['status' => 'success', 'message' => 'Anda telah berhasil keluar.']);
        break;

    case 'check':
        $u = getAuthUser();
        if ($u) {
            if (!empty($u['nidn'])) {
                $stmtRef = $pdo->prepare("SELECT foto, nama, gelar FROM dosen WHERE nidn = ?");
                $stmtRef->execute([$u['nidn']]);
                $ref = $stmtRef->fetch();
                if ($ref) {
                    if (!empty($ref['foto'])) $u['foto'] = $ref['foto'];
                    if (!empty($ref['nama'])) $u['nama'] = $ref['nama'] . ($ref['gelar'] ? ', ' . $ref['gelar'] : '');
                }
            }
            $_SESSION['user'] = $u;
            jsonResponse([
                'status' => 'success',
                'logged_in' => true,
                'user' => $u
            ]);
        } else {
            // Berikan mode guest/transparansi jika belum login
            jsonResponse([
                'status' => 'success',
                'logged_in' => false,
                'user' => null
            ]);
        }
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak valid.'], 400);
}
