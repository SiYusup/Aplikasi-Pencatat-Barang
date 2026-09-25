<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Controller\Api;

use UCrazy\AplikasiPencatatBarangCrud\App\ApiResponse;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Domain\User;
use UCrazy\AplikasiPencatatBarangCrud\Exception\ValidationException;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserLoginRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserPasswordUpdateRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserProfileUpdateRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserRegisterRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;
use UCrazy\AplikasiPencatatBarangCrud\Service\UserService;

class AuthApiController
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

    private function tokenPayload(User $user, string $token): array
    {
        return [
            'user' => UserService::toArray($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 60 * 60 * 24 * 30,
        ];
    }

    public function register(): void
    {
        $input = ApiResponse::input();
        $request = new UserRegisterRequest();
        $request->name = $input['name'] ?? null;
        $request->email = $input['email'] ?? null;
        $request->password = $input['password'] ?? null;
        $request->role = $input['role'] ?? null;
        // validasi konfirmasi (sesuai openapi.yaml)
        if (($input['password_confirmation'] ?? null) !== $request->password) {
            ApiResponse::error('Validasi gagal', 422, ['password_confirmation' => ['Konfirmasi password tidak sama']]);
        }
        try {
            $user = $this->userService->register($request);
            $session = $this->sessionService->create($user->id);
            ApiResponse::json(['success' => true, 'message' => 'Registrasi berhasil', 'data' => $this->tokenPayload($user, $session->id)], 201);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function login(): void
    {
        $input = ApiResponse::input();
        $request = new UserLoginRequest();
        $request->email = $input['email'] ?? ($input['id'] ?? null);
        $request->password = $input['password'] ?? null;
        try {
            $user = $this->userService->login($request);
            $session = $this->sessionService->create($user->id);
            ApiResponse::json(['success' => true, 'message' => 'Login berhasil', 'data' => $this->tokenPayload($user, $session->id)]);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 401);
        }
    }

    public function logout(): void
    {
        $this->sessionService->destroy();
        ApiResponse::json(['success' => true, 'message' => 'Logout berhasil']);
    }

    public function profile(): void
    {
        $user = $this->sessionService->current();
        if ($user === null) {
            ApiResponse::error('Unauthorized. Token tidak valid.', 401);
        }
        ApiResponse::json(['success' => true, 'data' => UserService::toArray($user)]);
    }

    public function updateProfile(): void
    {
        $user = $this->sessionService->current();
        if ($user === null) {
            ApiResponse::error('Unauthorized. Token tidak valid.', 401);
        }
        $input = ApiResponse::input();
        $request = new UserProfileUpdateRequest();
        $request->id = $user->id;
        $request->name = $input['name'] ?? $user->name;
        $request->email = $input['email'] ?? $user->email;
        try {
            $updated = $this->userService->updateProfile($request);
            ApiResponse::json(['success' => true, 'message' => 'Profil berhasil diperbarui', 'data' => UserService::toArray($updated)]);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function updatePassword(): void
    {
        $user = $this->sessionService->current();
        if ($user === null) {
            ApiResponse::error('Unauthorized. Token tidak valid.', 401);
        }
        $input = ApiResponse::input();
        if (($input['password_confirmation'] ?? null) !== ($input['password'] ?? null) && isset($input['password'])) {
            ApiResponse::error('Validasi gagal', 422, ['password_confirmation' => ['Konfirmasi password tidak sama']]);
        }
        $request = new UserPasswordUpdateRequest();
        $request->id = $user->id;
        $request->oldPassword = $input['current_password'] ?? ($input['oldPassword'] ?? null);
        $request->newPassword = $input['password'] ?? ($input['newPassword'] ?? null);
        try {
            $this->userService->updatePassword($request);
            ApiResponse::json(['success' => true, 'message' => 'Password berhasil diganti']);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }
}
