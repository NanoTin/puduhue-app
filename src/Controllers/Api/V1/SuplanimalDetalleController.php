<?php

class SuplanimalDetalleController
{
    private SuplanimalDetalleApiService $service;

    public function __construct()
    {
        $this->service = new SuplanimalDetalleApiService();
    }

    public function query(array $payload, array $requestContext): array
    {
        $result = $this->service->query($payload);

        return [
            'status' => 200,
            'message' => 'Consulta realizada correctamente',
            'data' => $result['rows'],
            'meta' => [
                'request_id' => $requestContext['request_id'],
                'page' => $result['page'],
                'page_size' => $result['page_size'],
                'total_registros' => $result['total_registros'],
            ],
        ];
    }
}
