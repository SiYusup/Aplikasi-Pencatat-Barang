<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Middleware;

use UCrazy\AplikasiPencatatBarangCrud\App\View;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;

class MustLoginMiddleware implements Middleware
{
    function before(): void
    {
        $connection = Database::getConnection();
        $service = new SessionService(new SessionRepository($connection), new UserRepository($connection));
        if ($service->current() === null) {
            View::redirect('/users/login');
        }
    }
}
