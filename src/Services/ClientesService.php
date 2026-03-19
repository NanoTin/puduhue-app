<?php

class ClientesService
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

    public function listarClientes(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroClienterut'         => $filtros['filtroClienterut'] ?? null,
            'filtroClienterazonsocial' => $filtros['filtroClienterazonsocial'] ?? null,
            'filtroClienteemail'       => $filtros['filtroClienteemail'] ?? null,
            'filtroClientecontacto'    => $filtros['filtroClientecontacto'] ?? null,
            'filtroClienteactivo'      => $filtros['filtroClienteactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_clientes_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarClientesFormSelect(?string $activoFilter = null): array
    {
        $sql = "SELECT clienteid, clienterut, clienterazonsocial FROM clientes";
        $params = [];
        if ($activoFilter !== null && ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1)) {
            $sql .= " WHERE clienteactivo = ?";
            $params[] = (int)$activoFilter;
        }
        $sql .= " ORDER BY clienterazonsocial ASC";
        return $this->db->select($sql, $params);
    }

    public function crearCliente(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['clienteid']);

        return $this->db->callSpMaint(
            'sp_clientes_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarCliente(int $clienteId, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['clienteid'] = $clienteId;

        return $this->db->callSpMaint(
            'sp_clientes_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularCliente(int $clienteId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['clienteid' => $clienteId];

        return $this->db->callSpMaint(
            'sp_clientes_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarClientePorId(int $clienteId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_clientes_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($clienteId) {
            return (int)($row['clienteid'] ?? 0) === $clienteId;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
