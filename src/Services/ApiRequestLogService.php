<?php

class ApiRequestLogService
{
    private \Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function log(array $context): void
    {
        $sql = "
            INSERT INTO apirequestlog (
                requestid,
                usuarioid,
                usuarioapitokenid,
                apiversion,
                recurso,
                metodohttp,
                endpoint,
                iporigen,
                useragent,
                requestheadersjson,
                requestbodyjson,
                responsecode,
                responsetimems,
                fechahora
            ) VALUES (
                :requestid,
                :usuarioid,
                :usuarioapitokenid,
                :apiversion,
                :recurso,
                :metodohttp,
                :endpoint,
                :iporigen,
                :useragent,
                :requestheadersjson,
                :requestbodyjson,
                :responsecode,
                :responsetimems,
                NOW()
            )
        ";

        $this->db->execute($sql, [
            ':requestid' => (string)($context['requestid'] ?? ''),
            ':usuarioid' => $context['usuarioid'] ?? null,
            ':usuarioapitokenid' => $context['usuarioapitokenid'] ?? null,
            ':apiversion' => (string)($context['apiversion'] ?? 'v1'),
            ':recurso' => (string)($context['recurso'] ?? ''),
            ':metodohttp' => (string)($context['metodohttp'] ?? ''),
            ':endpoint' => (string)($context['endpoint'] ?? ''),
            ':iporigen' => (string)($context['iporigen'] ?? ''),
            ':useragent' => (string)($context['useragent'] ?? ''),
            ':requestheadersjson' => json_encode($context['requestheadersjson'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':requestbodyjson' => json_encode($context['requestbodyjson'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':responsecode' => (int)($context['responsecode'] ?? 500),
            ':responsetimems' => (int)($context['responsetimems'] ?? 0),
        ]);
    }
}
