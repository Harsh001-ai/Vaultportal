<?php
namespace App;

use PDO;
use Exception;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (Exception $e) {
                // In production, never leak credentials
                error_log("Database connection error: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check configuration.");
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function insert(string $table, array $data): int {
        $keys = array_keys($data);
        $fields = implode(', ', array_map(fn($k) => "`$k`", $keys));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", $keys));
        $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
        self::query($sql, $data);
        return (int)self::getConnection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        $params = [];
        foreach ($data as $key => $val) {
            $setParts[] = "`$key` = :set_$key";
            $params["set_$key"] = $val;
        }
        $setSql = implode(', ', $setParts);
        $sql = "UPDATE `$table` SET $setSql WHERE $where";
        $mergedParams = array_merge($params, $whereParams);
        $stmt = self::query($sql, $mergedParams);
        return $stmt->rowCount();
    }

    public static function beginTransaction(): void {
        self::getConnection()->beginTransaction();
    }

    public static function commit(): void {
        self::getConnection()->commit();
    }

    public static function rollBack(): void {
        if (self::getConnection()->inTransaction()) {
            self::getConnection()->rollBack();
        }
    }
}
