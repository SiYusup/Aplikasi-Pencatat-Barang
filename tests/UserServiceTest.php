<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Test;

use PHPUnit\Framework\TestCase;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserLoginRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserRegisterRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;
use UCrazy\AplikasiPencatatBarangCrud\Service\UserService;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private SessionService $sessionService;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        Database::reset();
        $connection = Database::getConnection('test');
        $connection->exec("TRUNCATE sessions, users RESTART IDENTITY CASCADE");
        $this->userRepository = new UserRepository($connection);
        $this->userService = new UserService($this->userRepository);
        $this->sessionService = new SessionService(new SessionRepository($connection), $this->userRepository);
    }

    public function testRegisterLoginLogout(): void
    {
        $req = new UserRegisterRequest();
        $req->name = 'Ucup';
        $req->email = 'ucup@example.com';
        $req->password = 'rahasia123';
        $user = $this->userService->register($req);
        self::assertNotNull($user->id);
        self::assertEquals('admin', $user->role); // pendaftar pertama jadi admin

        $login = new UserLoginRequest();
        $login->email = 'ucup@example.com';
        $login->password = 'rahasia123';
        $loggedIn = $this->userService->login($login);
        self::assertEquals($user->id, $loggedIn->id);

        $session = $this->sessionService->create($user->id);
        $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;
        self::assertNotNull($this->sessionService->current());

        $this->sessionService->destroy();
        self::assertNull($this->sessionService->current());
    }

    public function testRegisterDuplicateEmailFails(): void
    {
        $req = new UserRegisterRequest();
        $req->name = 'A';
        $req->email = 'a@example.com';
        $req->password = 'rahasia123';
        $this->userService->register($req);

        $this->expectException(\Exception::class);
        $req2 = new UserRegisterRequest();
        $req2->name = 'B';
        $req2->email = 'a@example.com';
        $req2->password = 'rahasia123';
        $this->userService->register($req2);
    }
}
