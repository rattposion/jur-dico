<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Exception;

class DB
{
    private static ?PDO $pdo = null;

    public static function init(array $config): void
    {
        if (self::$pdo) return;

        $host = $config['db']['host'];
        $port = $config['db']['port'];
        $name = $config['db']['database']; // Config says 'database' not 'name'
        $user = $config['db']['username']; // Config says 'username'
        $pass = $config['db']['password']; // Config says 'password'

        try {
            self::$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Create Database if not exists
            self::$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            self::$pdo->exec("USE `$name`");
            
            self::initSchema();

        } catch (Exception $e) {
            die("DB Connection Error: " . $e->getMessage());
        }
    }

    public static function pdo(): PDO
    {
        return self::$pdo;
    }

    private static function initSchema(): void
    {
        // Users Table
        $sqlUsers = 'CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT "user",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlUsers);

        // Categories Table
        $sqlCats = 'CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlCats);

        // Records Table
        $sqlRecords = 'CREATE TABLE IF NOT EXISTS records (
            id VARCHAR(50) PRIMARY KEY, -- DataJud ID is string
            numeroProcesso VARCHAR(50) NULL,
            numeroRegistro VARCHAR(50) NULL,
            siglaClasse VARCHAR(50) NULL,
            descricaoClasse VARCHAR(255) NULL,
            nomeOrgaoJulgador VARCHAR(255) NULL,
            codOrgaoJulgador VARCHAR(50) NULL,
            ministroRelator VARCHAR(150) NULL,
            dataPublicacao VARCHAR(20) NULL,
            ementa TEXT NULL,
            tipoDeDecisao VARCHAR(100) NULL,
            dataDecisao VARCHAR(20) NULL,
            decisao TEXT NULL,
            category_id INT UNSIGNED NULL,
            ai_label VARCHAR(50) NULL,
            ai_confidence FLOAT NULL,
            ai_metadata JSON NULL,
            payment_status VARCHAR(20) DEFAULT NULL, -- added for bulk ops
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_processo (numeroProcesso),
            INDEX idx_classe (siglaClasse),
            INDEX idx_relator (ministroRelator),
            INDEX idx_data (dataDecisao),
            INDEX idx_cat (category_id),
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlRecords);

        // Ensure payment_status column exists (simple migration)
        try {
            self::pdo()->exec("ALTER TABLE records ADD COLUMN payment_status VARCHAR(20) DEFAULT NULL");
        } catch (Exception $e) {
            // Ignore if column already exists
        }

        // Ensure codOrgaoJulgador column exists
        try {
            self::pdo()->exec("ALTER TABLE records ADD COLUMN codOrgaoJulgador VARCHAR(50) DEFAULT NULL");
        } catch (Exception $e) {
            // Ignore if column already exists
        }

        // Audit Table
        $sqlAudit = 'CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            action VARCHAR(50) NOT NULL,
            details JSON NULL,
            record_id VARCHAR(50) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_record_id (record_id)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlAudit);

        $sqlNotifications = 'CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlNotifications);
        $sqlReads = 'CREATE TABLE IF NOT EXISTS notification_reads (
            user_id BIGINT UNSIGNED NOT NULL,
            notification_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, notification_id),
            INDEX idx_notification (notification_id)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlReads);

        $sqlConversations = 'CREATE TABLE IF NOT EXISTS conversations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlConversations);
        $sqlConvUsers = 'CREATE TABLE IF NOT EXISTS conversation_users (
            conversation_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT "client",
            PRIMARY KEY (conversation_id, user_id)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlConvUsers);
        $sqlMsgs = 'CREATE TABLE IF NOT EXISTS messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL, -- NULL if AI
            content TEXT NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT "text", -- text, file, system
            metadata JSON NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_conv (conversation_id)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlMsgs);

        // User API Keys Table
        $sqlApiKeys = 'CREATE TABLE IF NOT EXISTS user_api_keys (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(50) NOT NULL, -- openai, gemini, datajud
            enc_key TEXT NOT NULL, -- Encrypted key
            model VARCHAR(50) NULL, -- Preferred model
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_user_provider (user_id, provider)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlApiKeys);

        // Cache Table
        $sqlCache = 'CREATE TABLE IF NOT EXISTS cache (
            `key` VARCHAR(191) PRIMARY KEY,
            `value` LONGTEXT NOT NULL,
            expiration INT UNSIGNED NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlCache);

        // Deadlines Table
        $sqlDeadlines = 'CREATE TABLE IF NOT EXISTS deadlines (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            conversation_id BIGINT UNSIGNED NULL,
            process_number VARCHAR(50) NULL,
            description VARCHAR(255) NOT NULL,
            due_date DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            created_at DATETIME NOT NULL,
            INDEX idx_user_status (user_id, status),
            INDEX idx_due_date (due_date)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlDeadlines);

        // Saved Searches Table
        $sqlSavedSearches = 'CREATE TABLE IF NOT EXISTS saved_searches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            name VARCHAR(100) NOT NULL,
            criteria JSON NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlSavedSearches);

        // Search History Table
        $sqlSearchHistory = 'CREATE TABLE IF NOT EXISTS search_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            term VARCHAR(255) NULL,
            criteria JSON NULL,
            searched_at DATETIME NOT NULL,
            INDEX idx_user_time (user_id, searched_at)
        ) ENGINE=InnoDB';
        self::pdo()->exec($sqlSearchHistory);
    }
}
