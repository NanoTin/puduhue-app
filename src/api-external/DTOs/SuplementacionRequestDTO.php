<?php

namespace ApiExternal\DTOs;

/**
 * DTO para request de Suplementacion Animal hacia Finnegans.
 */
class SuplementacionRequestDTO
{
    public function __construct(
        private string $empresaId,
        private string $fecha,
        private string $descripcion,
        private string $identificacionExterna,
        private string $fechaComprobante,
        private array $items,
        private array $movimientoHaciendaSuplementacionInsumo,
        private string $numeroComprobante = '',
        private bool $resumirInsumos = false,
        private string $transaccionTipo = 'OPER',
        private string $transaccionSubtipoCodigo = 'SUPXCAB',
        private int $transaccionId = 0
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['EmpresaID'] ?? ''),
            (string)($data['Fecha'] ?? ''),
            (string)($data['Descripcion'] ?? ''),
            (string)($data['IdentificacionExterna'] ?? ''),
            (string)($data['FechaComprobante'] ?? ''),
            (array)($data['Items'] ?? []),
            (array)($data['MovimientoHaciendaSuplementacionInsumo'] ?? []),
            (string)($data['NumeroComprobante'] ?? ''),
            (bool)($data['ResumirInsumos'] ?? false),
            (string)($data['TransaccionTipo'] ?? 'OPER'),
            (string)($data['TransaccionSubtipoCodigo'] ?? 'SUPXCAB'),
            (int)($data['TransaccionID'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'EmpresaID' => $this->empresaId,
            'Fecha' => $this->fecha,
            'Descripcion' => $this->descripcion,
            'NumeroComprobante' => $this->numeroComprobante,
            'ResumirInsumos' => $this->resumirInsumos,
            'TransaccionTipo' => $this->transaccionTipo,
            'TransaccionSubtipoCodigo' => $this->transaccionSubtipoCodigo,
            'IdentificacionExterna' => $this->identificacionExterna,
            'TransaccionID' => $this->transaccionId,
            'FechaComprobante' => $this->fechaComprobante,
            'Items' => $this->items,
            'MovimientoHaciendaSuplementacionInsumo' => $this->movimientoHaciendaSuplementacionInsumo,
        ];
    }
}
