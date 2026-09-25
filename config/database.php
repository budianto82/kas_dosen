<?php
/**
 * Database Configuration & Connection
 * Kas Dosen - Universitas Pamulang (Prodi Sistem Informasi)
 */

define('DB_DRIVER', 'mysql'); // 'mysql' (Laragon default) or 'sqlite'
define('SQLITE_FILE', __DIR__ . '/../database/kas_dosen.db');

// MySQL Settings (Laragon)
define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_PORT', '3306');
define('MYSQL_DB', 'kas_dosen_unpam');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // 1. Cek Koneksi Supabase PostgreSQL (untuk deployment Vercel / Cloud)
        $pgHost = getenv('SUPABASE_HOST') ?: getenv('POSTGRES_HOST');
        if (!empty($pgHost) || (defined('DB_DRIVER') && DB_DRIVER === 'pgsql')) {
            $dbHost = $pgHost ?: '127.0.0.1';
            $dbPort = getenv('SUPABASE_PORT') ?: getenv('POSTGRES_PORT') ?: '5432';
            $dbName = getenv('SUPABASE_DB') ?: getenv('POSTGRES_DATABASE') ?: 'postgres';
            $dbUser = getenv('SUPABASE_USER') ?: getenv('POSTGRES_USER') ?: 'postgres';
            $dbPass = getenv('SUPABASE_PASS') ?: getenv('POSTGRES_PASSWORD') ?: '';

            $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName};sslmode=require";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            try {
                $pdo->exec("ALTER TABLE iuran ALTER COLUMN bukti_bayar TYPE TEXT");
                $pdo->exec("ALTER TABLE iuran ADD COLUMN IF NOT EXISTS catatan_bendahara TEXT");
                $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");
            } catch (Throwable $e) {}
            return $pdo;
        }

        if (DB_DRIVER === 'mysql') {
            // Pastikan database ada terlebih dahulu
            $initPdo = new PDO("mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT, MYSQL_USER, MYSQL_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $initPdo->exec("CREATE DATABASE IF NOT EXISTS `" . MYSQL_DB . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // Hubungkan ke database kas_dosen_unpam
            $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DB . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            try {
                $pdo->exec("ALTER TABLE iuran MODIFY bukti_bayar LONGTEXT");
                $pdo->exec("ALTER TABLE iuran ADD COLUMN catatan_bendahara TEXT");
            } catch (Throwable $e) {}
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `pengaturan` (`setting_key` VARCHAR(100) PRIMARY KEY, `setting_value` TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            } catch (Throwable $e) {}

            // Cek apakah tabel sudah ada
            $checkTable = $pdo->query("SHOW TABLES LIKE 'dosen'")->rowCount();
            if ($checkTable === 0) {
                initMysqlSchema($pdo);
            }
        } else {
            // SQLite Fallback
            $dbDir = dirname(SQLITE_FILE);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }

            $isNew = !file_exists(SQLITE_FILE);
            $pdo = new PDO('sqlite:' . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            $pdo->exec('PRAGMA journal_mode = WAL;');

            if ($isNew || filesize(SQLITE_FILE) === 0) {
                initSqliteSchema($pdo);
            }
        }

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal terhubung ke database: ' . $e->getMessage()
        ]);
        exit;
    }
}

function initMysqlSchema(PDO $pdo): void {
    $schema = <<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        nama VARCHAR(150) NOT NULL,
        role ENUM('bendahara', 'kaprodi', 'dosen') NOT NULL DEFAULT 'bendahara',
        nidn VARCHAR(50) NULL,
        no_hp VARCHAR(50) NULL,
        foto VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS dosen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nidn VARCHAR(50) UNIQUE NOT NULL,
        nama VARCHAR(150) NOT NULL,
        gelar VARCHAR(100) NULL,
        no_hp VARCHAR(50) NOT NULL,
        email VARCHAR(100) NULL,
        foto VARCHAR(255) NULL,
        jabatan VARCHAR(100) DEFAULT 'Dosen Tetap',
        status ENUM('aktif', 'cuti', 'nonaktif') NOT NULL DEFAULT 'aktif',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS kategori_pengeluaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(150) UNIQUE NOT NULL,
        deskripsi TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS iuran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dosen_id INT NOT NULL,
        bulan INT NOT NULL,
        tahun INT NOT NULL,
        nominal DECIMAL(12,2) NOT NULL,
        tanggal_bayar DATE NOT NULL,
        metode_bayar ENUM('tunai', 'transfer', 'qris') NOT NULL DEFAULT 'transfer',
        bukti_bayar VARCHAR(255) NULL,
        status ENUM('lunas', 'pending', 'ditolak') NOT NULL DEFAULT 'lunas',
        keterangan TEXT NULL,
        catatan_bendahara TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dosen_tahun (dosen_id, tahun),
        CONSTRAINT fk_iuran_dosen FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS pengeluaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kategori_id INT NOT NULL,
        judul VARCHAR(255) NOT NULL,
        nominal DECIMAL(12,2) NOT NULL,
        tanggal DATE NOT NULL,
        keterangan TEXT NULL,
        pj_penerima VARCHAR(150) NULL,
        bukti_nota VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tanggal (tanggal),
        CONSTRAINT fk_pengeluaran_kat FOREIGN KEY (kategori_id) REFERENCES kategori_pengeluaran(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS pengaturan (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

    $pdo->exec($schema);
}

function initSqliteSchema(PDO $pdo): void {
    $schema = <<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        nama TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'bendahara',
        nidn TEXT,
        no_hp TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS dosen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nidn TEXT UNIQUE NOT NULL,
        nama TEXT NOT NULL,
        gelar TEXT,
        no_hp TEXT NOT NULL,
        email TEXT,
        jabatan TEXT DEFAULT 'Dosen Tetap',
        status TEXT NOT NULL DEFAULT 'aktif',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS kategori_pengeluaran (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_kategori TEXT UNIQUE NOT NULL,
        deskripsi TEXT
    );

    CREATE TABLE IF NOT EXISTS iuran (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        dosen_id INTEGER NOT NULL,
        bulan INTEGER NOT NULL,
        tahun INTEGER NOT NULL,
        nominal REAL NOT NULL,
        tanggal_bayar DATE NOT NULL,
        metode_bayar TEXT NOT NULL DEFAULT 'transfer',
        bukti_bayar TEXT,
        status TEXT NOT NULL DEFAULT 'lunas',
        keterangan TEXT,
        catatan_bendahara TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS pengeluaran (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kategori_id INTEGER NOT NULL,
        judul TEXT NOT NULL,
        nominal REAL NOT NULL,
        tanggal DATE NOT NULL,
        keterangan TEXT,
        pj_penerima TEXT,
        bukti_nota TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kategori_id) REFERENCES kategori_pengeluaran(id)
    );

    CREATE TABLE IF NOT EXISTS pengaturan (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT
    );
SQL;

    $pdo->exec($schema);
}
