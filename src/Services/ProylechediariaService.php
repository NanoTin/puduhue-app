<?php

class ProylechediariaService
{
    private \Database $db;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/Config/Database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $this->db = \Database::getInstance();
    }

    public function listarProylechediaria(array $filtros): array
    {
        $sql = 'SELECT proylechefecha, proylecheventatotlitros, proylecheventatotvacas, proylecheventatotltsxvaca,
                       proylecheanio, proylechemes
                FROM proylechediariaconsolidada
                WHERE 1=1';
        $params = [];

        if (!empty($filtros['filtroProylecheanio'])) {
            $sql .= ' AND proylecheanio = ?';
            $params[] = (int)$filtros['filtroProylecheanio'];
        }

        if (!empty($filtros['filtroProylechemes'])) {
            $sql .= ' AND proylechemes = ?';
            $params[] = (int)$filtros['filtroProylechemes'];
        }

        $sql .= ' ORDER BY proylechefecha DESC';

        return [
            'rows' => $this->db->select($sql, $params),
            'meta' => null,
        ];
    }

    public function listarProyLecheDiariaPorAMes(int $anio, int $mes): array
    {
       $sql = 'SELECT proylechefecha, proylecheventatotlitros, proylecheventatotvacas, proylecheventatotltsxvaca,
                       proylecheanio, proylechemes
                FROM proylechediariaconsolidada
                WHERE proylecheanio = ? AND proylechemes = ?
                ORDER BY proylechefecha ASC';
         return $this->db->select($sql, [$anio, $mes]);
    }

    public function consultarPorFecha(string $fecha): ?array
    {
        $sql = 'SELECT proylechefecha, proylecheventatotlitros, proylecheventatotvacas, proylecheventatotltsxvaca,
                       proylecheanio, proylechemes
                FROM proylechediariaconsolidada
                WHERE proylechefecha = ?';
        $rows = $this->db->select($sql, [$fecha]);
        return $rows[0] ?? null;
    }

    public function crearProylechediaria(array $data, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $fecha = $data['proylechefecha'] ?? null;
        if (!$fecha) {
            throw new \RuntimeException('La fecha es obligatoria.');
        }

        $exists = $this->db->select(
            'SELECT COUNT(*) AS total FROM proylechediariaconsolidada WHERE proylechefecha = ?',
            [$fecha]
        );
        if (!empty($exists[0]['total'])) {
            throw new \RuntimeException('Ya existe un registro para la fecha seleccionada.');
        }

        $sql = 'INSERT INTO proylechediariaconsolidada (
                    proylechefecha,
                    proylecheventatotlitros,
                    proylecheventatotvacas,
                    proylecheventatotltsxvaca,
                    auditcreacionusuarioid,
                    auditcreaciondispositivo,
                    auditcreacionip,
                    auditedicionusuarioid,
                    auditediciondispositivo,
                    auditedicionip
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $fecha,
            $data['proylecheventatotlitros'] ?? 0,
            $data['proylecheventatotvacas'] ?? 0,
            $data['proylecheventatotltsxvaca'] ?? 0,
            $usuarioId,
            $disp ?? '',
            $ip ?? '',
            null,
            $disp ?? '',
            $ip ?? '',
        ]);
    }

    public function actualizarProylechediaria(array $data, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $fecha = $data['proylechefecha'] ?? null;
        if (!$fecha) {
            throw new \RuntimeException('La fecha es obligatoria.');
        }

        $sql = 'UPDATE proylechediariaconsolidada
                SET proylecheventatotlitros = ?,
                    proylecheventatotvacas = ?,
                    proylecheventatotltsxvaca = ?,
                    auditedicionusuarioid = ?,
                    auditediciondispositivo = ?,
                    auditedicionip = ?
                WHERE proylechefecha = ?';

        $this->db->execute($sql, [
            $data['proylecheventatotlitros'] ?? 0,
            $data['proylecheventatotvacas'] ?? 0,
            $data['proylecheventatotltsxvaca'] ?? 0,
            $usuarioId,
            $disp ?? '',
            $ip ?? '',
            $fecha,
        ]);
    }

    public function eliminarProylechediaria(string $fecha): void
    {
        $this->db->execute(
            'DELETE FROM proylechediariaconsolidada WHERE proylechefecha = ?',
            [$fecha]
        );
    }

    public function cargarMasivaProylechediaria(array $rows, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_proylechediariaconsolidada_carga_masiva',
            $rows,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
