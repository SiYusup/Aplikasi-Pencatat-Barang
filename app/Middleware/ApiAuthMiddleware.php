<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Middleware;

use UCrazy\AplikasiPencatatBarangCrud\App\ApiResponse;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;

class ApiAuthMiddleware implements Middleware
{
    function before(): void
    {
        $connection = Database::getConnection();
        $service = new SessionService(new SessionRepository($connection), new UserRepository($connection));
        if ($service->current() === null) {
            ApiResponse::error('Unauthorized. Token tidak valid.', 401);
        }
    }
}
