<?php

namespace ApiExternal\DTOs;

/**
 * DTO para respuestas de Produccion de Leche desde Finnegans.
 */
class ProduccionLecheResponseDTO
{
    public function __construct(
        public readonly ?string $documento,
        public readonly ?string $id,
        public readonly ?string $message,
        public readonly ?int $status,
        public readonly ?string $error,
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['documento'] ?? null,
            $data['id'] ?? null,
            $data['message'] ?? null,
            isset($data['status']) ? (int)$data['status'] : null,
            $data['error'] ?? null,
            $data
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 200 && empty($this->error);
    }

    public function toArray(): array
    {
        return [
            'documento' => $this->documento,
            'id' => $this->id,
            'message' => $this->message,
            'status' => $this->status,
            'error' => $this->error,
            'raw' => $this->raw,
        ];
    }
}
