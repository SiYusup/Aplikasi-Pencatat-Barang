<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Controller;

use UCrazy\AplikasiPencatatBarangCrud\App\View;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Exception\ValidationException;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserLoginRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserPasswordUpdateRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserProfileUpdateRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserRegisterRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;
use UCrazy\AplikasiPencatatBarangCrud\Service\UserService;

class UserController
{
    private UserService $userService;
    private SessionService $sessionService;

    public function __construct()
    {
        $connection = Database::getConnection();
        $userRepository = new UserRepository($connection);
        $this->userService = new UserService($userRepository);
        $this->sessionService = new SessionService(new SessionRepository($connection), $userRepository);
    }

    public function register(): void
    {
        View::render('Auth/register', ['title' => 'Registrasi', 'user' => null]);
    }

    public function postRegister(): void
    {
        $request = new UserRegisterRequest();
        $request->name = $_POST['name'] ?? null;
        $request->email = $_POST['email'] ?? null;
        $request->password = $_POST['password'] ?? null;
        if (($_POST['password_confirmation'] ?? null) !== $request->password) {
            View::render('Auth/register', ['title' => 'Registrasi', 'error' => 'Konfirmasi password tidak sama', 'user' => null]);
            return;
        }
        try {
            $user = $this->userService->register($request);
            $this->sessionService->create($user->id);
            View::redirect('/');
        } catch (ValidationException $e) {
            View::render('Auth/register', ['title' => 'Registrasi', 'error' => $e->getMessage(), 'user' => null]);
        }
    }

    public function login(): void
    {
        View::render('Auth/login', ['title' => 'Login', 'user' => null]);
    }

    public function postLogin(): void
    {
        $request = new UserLoginRequest();
        $request->email = $_POST['email'] ?? null;
        $request->password = $_POST['password'] ?? null;
        try {
            $user = $this->userService->login($request);
            $this->sessionService->create($user->id);
            View::redirect('/');
        } catch (ValidationException $e) {
            View::render('Auth/login', ['title' => 'Login', 'error' => $e->getMessage(), 'user' => null]);
        }
    }

    public function logout(): void
    {
        $this->sessionService->destroy();
        View::redirect('/users/login?logout=1');
    }

    public function profile(): void
    {
        View::render('User/profile', ['title' => 'Profile', 'user' => $this->sessionService->current()]);
    }

    public function postUpdateProfile(): void
    {
        $current = $this->sessionService->current();
        $request = new UserProfileUpdateRequest();
        $request->id = $current->id;
        $request->name = $_POST['name'] ?? '';
        $request->email = $_POST['email'] ?? $current->email;
        try {
            $this->userService->updateProfile($request);
            View::redirect('/users/profile?ok=Profil berhasil diperbarui');
        } catch (ValidationException $e) {
            View::render('User/profile', ['title' => 'Profile', 'error' => $e->getMessage(), 'user' => $current]);
        }
    }

    public function password(): void
    {
        View::render('User/password', ['title' => 'Ganti Password', 'user' => $this->sessionService->current()]);
    }

    public function postUpdatePassword(): void
    {
        $current = $this->sessionService->current();
        $request = new UserPasswordUpdateRequest();
        $request->id = $current->id;
        $request->oldPassword = $_POST['oldPassword'] ?? null;
        $request->newPassword = $_POST['newPassword'] ?? null;
        try {
            $this->userService->updatePassword($request);
            View::redirect('/users/profile?ok=Password berhasil diganti');
        } catch (ValidationException $e) {
            View::render('User/password', ['title' => 'Ganti Password', 'error' => $e->getMessage(), 'user' => $current]);
        }
    }
}
