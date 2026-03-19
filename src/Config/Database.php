<?php

/**
 * Database access layer for Puduhue App.
 *
 * PDO singleton with helpers for Stored Procedures:
 *  - callSpMaint: SPs de mantenimiento, con control de transacción.
 *  - callSpQuery: SPs de consulta, retorna filas y meta.
 *  - select / execute: consultas simples cuando sea necesario.
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $db   = Env::get('DB_NAME', '');
        $user = Env::get('DB_USER', '');
        $pass = Env::get('DB_PASS', '');
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new \PDO($dsn, $user, $pass, $options);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Ejecuta SP de mantenimiento (insertar/editar/anular/eliminar) con transacción.
     */
    public function callSpMaint(string $spName, array $dataJson, int $usuarioId, ?string $dispositivo, ?string $ip): array
    {
        $jsonPayload = json_encode($dataJson, JSON_UNESCAPED_UNICODE);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("CALL {$spName}(?, ?, ?, ?, @p_out_json)");
            $stmt->execute([
                $jsonPayload,
                $usuarioId,
                $dispositivo,
                $ip
            ]);
            // Consumir todos los resultsets
            while ($stmt->nextRowset()) {
                // no-op
            }
            $stmt->closeCursor();

            $outStmt = $this->pdo->query("SELECT @p_out_json AS out_json");
            $outRow = $outStmt->fetch();
            $outJson = $outRow['out_json'] ?? null;

            $decoded = $outJson ? json_decode($outJson, true) : null;
            $status = $decoded['status'] ?? null;
            if ($status !== 200) {
                $this->pdo->rollBack();
                $message = $decoded['message'] ?? 'SP error';
                throw new \RuntimeException($message);
            }

            $this->pdo->commit();
            return $decoded ?? [];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Ejecuta SP de consulta y retorna filas + meta (p_out_json).
     */
    public function callSpQuery(string $spName, array $dataJson, int $usuarioId, ?string $dispositivo, ?string $ip): array
    {
        $jsonPayload = json_encode($dataJson, JSON_UNESCAPED_UNICODE);

        $stmt = $this->pdo->prepare("CALL {$spName}(?, ?, ?, ?, @p_out_json)");
        $stmt->execute([
            $jsonPayload,
            $usuarioId,
            $dispositivo,
            $ip
        ]);

        $rows = $stmt->fetchAll();
        while ($stmt->nextRowset()) {
            // consume additional sets if present
        }
        $stmt->closeCursor();

        $outStmt = $this->pdo->query("SELECT @p_out_json AS out_json");
        $outRow = $outStmt->fetch();
        $outJson = $outRow['out_json'] ?? null;
        $meta = $outJson ? json_decode($outJson, true) : null;

        return [
            'rows' => $rows,
            'meta' => $meta,
        ];
    }

    /**
     * Consulta SELECT simple.
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ejecuta INSERT/UPDATE/DELETE simple.
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Acceso directo a PDO si se requiere.
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
