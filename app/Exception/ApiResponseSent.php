<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Exception;

/** Dilempar setelah respons API dikirim saat APP_ENV=test agar eksekusi berhenti (pengganti exit()). */
class ApiResponseSent extends \Exception
{
    public array $body;
    public int $httpCode;

    public function __construct(array $body, int $httpCode)
    {
        parent::__construct($body['message'] ?? 'API response');
        $this->body = $body;
        $this->httpCode = $httpCode;
    }
}
