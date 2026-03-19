<?php

/**
 * Core Database access layer for Puduhue App.
 *
 * Implements PDO access, transaction handling for maintenance SPs,
 * and utilities for calling query SPs and raw SQL.
 */

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class Database
{
    private static ?PDO $pdo = null;
    private static ?Database $instance = null;

    private bool $inTransaction = false;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Executes a maintenance stored procedure (insert/edit/anular/eliminar)
     * with transaction control and p_out_json handling.
     */
    public function callSpMaint(
        string $spName,
        array $dataJson,
        int $usuarioId,
        ?string $dispositivo,
        ?string $ip
    ): array {
        $pdo = $this->getConnection();
        $payload = json_encode($dataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sql = "CALL {$spName}(:p_in_json, :p_in_usuarioid, :p_in_dispositivo, :p_in_ip, @p_out_json);";

        try {
            $this->begin();

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':p_in_json' => $payload,
                ':p_in_usuarioid' => $usuarioId,
                ':p_in_dispositivo' => $dispositivo ?? '',
                ':p_in_ip' => $ip ?? '',
            ]);
            $stmt->closeCursor();

            $outRow = $pdo->query('SELECT @p_out_json AS p_out_json')->fetch(PDO::FETCH_ASSOC);
            $decoded = $this->decodeOutJson($outRow['p_out_json'] ?? null);

            if (($decoded['status'] ?? 500) !== 200) {
                $this->rollback();
                $this->logError($spName, $dataJson, 'SP returned error', $decoded);
                throw new RuntimeException($decoded['message'] ?? 'Error executing stored procedure');
            }

            $this->commit();
            return $decoded;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError($spName, $dataJson, $e->getMessage());
            throw new RuntimeException('Database error. Please try again later.');
        } catch (RuntimeException $e) {
            // already rolled back above when needed
            throw $e;
        } catch (Throwable $e) {
            $this->rollback();
            $this->logError($spName, $dataJson, $e->getMessage());
            throw new RuntimeException('Unexpected error. Please try again later.');
        }
    }

    /**
     * Executes a query stored procedure (listar/consultar) and returns rows/meta.
     */
    public function callSpQuery(
        string $spName,
        array $dataJson,
        int $usuarioId,
        ?string $dispositivo,
        ?string $ip
    ): array {
        $pdo = $this->getConnection();
        $payload = json_encode($dataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sql = "CALL {$spName}(:p_in_json, :p_in_usuarioid, :p_in_dispositivo, :p_in_ip, @p_out_json);";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':p_in_json' => $payload,
                ':p_in_usuarioid' => $usuarioId,
                ':p_in_dispositivo' => $dispositivo ?? '',
                ':p_in_ip' => $ip ?? '',
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $metaRow = $pdo->query('SELECT @p_out_json AS p_out_json')->fetch(PDO::FETCH_ASSOC);
            $meta = $this->decodeOutJson($metaRow['p_out_json'] ?? null, true);

            return [
                'rows' => $rows,
                'meta' => $meta,
            ];
        } catch (PDOException $e) {
            $this->logError($spName, $dataJson, $e->getMessage());
            throw new RuntimeException('Database error. Please try again later.');
        }
    }

    /**
     * Executes a prepared SELECT and returns all rows.
     */
    public function select(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($sql, $params, $e->getMessage());
            throw new RuntimeException('Database error. Please try again later.');
        }
    }

    /**
     * Executes INSERT/UPDATE/DELETE and returns affected row count.
     */
    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($sql, $params, $e->getMessage());
            throw new RuntimeException('Database error. Please try again later.');
        }
    }

    // --- Transaction helpers -------------------------------------------------
    private function begin(): void
    {
        if (!$this->inTransaction) {
            $this->getConnection()->beginTransaction();
            $this->inTransaction = true;
        }
    }

    private function commit(): void
    {
        if ($this->inTransaction) {
            $this->getConnection()->commit();
            $this->inTransaction = false;
        }
    }

    private function rollback(): void
    {
        if ($this->inTransaction) {
            $this->getConnection()->rollBack();
            $this->inTransaction = false;
        }
    }

    // --- Internal utilities --------------------------------------------------
    private function getConnection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = $this->loadDbConfig();
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        return self::$pdo;
    }

    private function loadDbConfig(): array
    {
        // Default values from environment
        $env = fn(string $key, string $default = '') => getenv($key) !== false ? getenv($key) : $default;
        $config = [
            'host' => $env('DB_HOST', '127.0.0.1'),
            'port' => $env('DB_PORT', '3306'),
            'database' => $env('DB_DATABASE', ''),
            'username' => $env('DB_USERNAME', ''),
            'password' => $env('DB_PASSWORD', ''),
            'charset' => $env('DB_CHARSET', 'utf8mb4'),
        ];

        if (class_exists('DBConfig')) {
            try {
                $dbConfig = new DBConfig();
                $config['host'] = $dbConfig->getHost();
                $config['port'] = $dbConfig->getPort();
                $config['database'] = $dbConfig->getDatabase();
                $config['username'] = $dbConfig->getUsername();
                $config['password'] = $dbConfig->getPassword();
                $config['charset'] = $dbConfig->getCharset();
            } catch (Throwable $e) {
                // fall back to env if DBConfig is not fully implemented
            }
        }

        return $config;
    }

    private function decodeOutJson(?string $json, bool $allowNull = false): ?array
    {
        if ($json === null) {
            return $allowNull ? null : ['status' => 500, 'message' => 'Invalid SP response'];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return $allowNull ? null : ['status' => 500, 'message' => 'Invalid SP response'];
        }
        return $decoded;
    }

    private function logError(string $context, array $params, string $message, array $extra = []): void
    {
        $payload = [
            'context' => $context,
            'params' => $params,
            'error' => $message,
            'extra' => $extra,
        ];

        if (class_exists('Logger') && method_exists('Logger', 'error')) {
            Logger::error(json_encode($payload));
        } else {
            error_log('[Database] ' . json_encode($payload));
        }
    }
}
