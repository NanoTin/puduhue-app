<?php

namespace ApiExternal\DTOs;

/**
 * DTO para request de Produccion de Leche hacia Finnegans.
 */
class ProduccionLecheRequestDTO
{
    public function __construct(
        private string $empresaId,
        private string $fecha,
        private string $establecimientoCodigo,
        private string $loteCodigo,
        private string $haciendaCategoriaCodigo,
        private string $identificacionExterna,
        private int $cabezas,
        private string $descripcion,
        private array $movimientoHaciendaProduccionLeche,
        private ?string $tropa = '',
        private ?string $numeroDocumento = '',
        private ?string $campanaCodigo = null,
        private string $transaccionTipo = 'OPER',
        private string $transaccionSubtipoCodigo = 'PRODLECH',
        private array $operacionCotizaciones = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['EmpresaID'] ?? ''),
            (string)($data['Fecha'] ?? ''),
            (string)($data['EstablecimientoCodigo'] ?? ''),
            (string)($data['LoteCodigo'] ?? ''),
            (string)($data['HaciendaCategoriaCodigo'] ?? ''),
            (string)($data['IdentificacionExterna'] ?? ''),
            (int)($data['Cabezas'] ?? 0),
            (string)($data['Descripcion'] ?? ''),
            (array)($data['MovimientoHaciendaProduccionLeche'] ?? []),
            $data['Tropa'] ?? '',
            $data['NumeroDocumento'] ?? '',
            $data['CampanaCodigo'] ?? null,
            (string)($data['TransaccionTipo'] ?? 'OPER'),
            (string)($data['TransaccionSubtipoCodigo'] ?? 'PRODLECH'),
            (array)($data['OperacionCotizaciones'] ?? [])
        );
    }

    public function toArray(): array
    {
        return [
            'EmpresaID' => $this->empresaId,
            'Fecha' => $this->fecha,
            'EstablecimientoCodigo' => $this->establecimientoCodigo,
            'LoteCodigo' => $this->loteCodigo,
            'HaciendaCategoriaCodigo' => $this->haciendaCategoriaCodigo,
            'CampanaCodigo' => $this->campanaCodigo,
            'NumeroDocumento' => $this->numeroDocumento,
            'IdentificacionExterna' => $this->identificacionExterna,
            'Tropa' => $this->tropa,
            'Cabezas' => $this->cabezas,
            'TransaccionTipo' => $this->transaccionTipo,
            'TransaccionSubtipoCodigo' => $this->transaccionSubtipoCodigo,
            'Descripcion' => $this->descripcion,
            'OperacionCotizaciones' => $this->operacionCotizaciones,
            'MovimientoHaciendaProduccionLeche' => $this->movimientoHaciendaProduccionLeche,
        ];
    }
}
