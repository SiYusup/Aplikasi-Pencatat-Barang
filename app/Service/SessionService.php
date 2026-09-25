<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Service;

use UCrazy\AplikasiPencatatBarangCrud\Domain\Session;
use UCrazy\AplikasiPencatatBarangCrud\Domain\User;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;

class SessionService
{
    public static string $COOKIE_NAME = "X-PZN-SESSION";

    private SessionRepository $sessionRepository;
    private UserRepository $userRepository;

    public function __construct(SessionRepository $sessionRepository, UserRepository $userRepository)
    {
        $this->sessionRepository = $sessionRepository;
        $this->userRepository = $userRepository;
    }

    public function create(int $userId): Session
    {
        $session = new Session();
        $session->id = bin2hex(random_bytes(32));
        $session->userId = $userId;
        $this->sessionRepository->save($session);
        setcookie(self::$COOKIE_NAME, $session->id, time() + (60 * 60 * 24 * 30), "/");

        return $session;
    }

    public function destroy(): void
    {
        $sessionId = $this->token();
        if ($sessionId !== '') {
            $this->sessionRepository->deleteById($sessionId);
        }
        setcookie(self::$COOKIE_NAME, '', 1, "/");
    }

    /** Token dari header Bearer (REST API) atau cookie (Web UI). */
    public function token(): string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (str_starts_with($auth, 'Bearer ')) {
            return trim(substr($auth, 7));
        }
        return $_COOKIE[self::$COOKIE_NAME] ?? '';
    }

    public function current(): ?User
    {
        $sessionId = $this->token();
        if ($sessionId === '') {
            return null;
        }
        $session = $this->sessionRepository->findById($sessionId);
        if ($session === null) {
            return null;
        }
        return $this->userRepository->findById($session->userId);
    }
}
