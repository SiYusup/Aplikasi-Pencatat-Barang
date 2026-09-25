<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Service;

use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Domain\User;
use UCrazy\AplikasiPencatatBarangCrud\Exception\ValidationException;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserLoginRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserPasswordUpdateRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserProfileUpdateRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserRegisterRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(UserRegisterRequest $request): User
    {
        if (
            $request->name === null || $request->email === null || $request->password === null ||
            trim($request->name) === '' || trim($request->email) === '' || trim($request->password) === ''
        ) {
            throw new ValidationException("Nama, Email, Password tidak boleh kosong");
        }
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Format email tidak valid");
        }
        if (strlen($request->password) < 8) {
            throw new ValidationException("Password minimal 8 karakter");
        }

        try {
            Database::beginTransaction();
            if ($this->userRepository->findByEmail($request->email) !== null) {
                throw new ValidationException("Email sudah terdaftar");
            }
            $isFirst = $this->userRepository->countAll() === 0;
            $user = new User();
            $user->name = trim($request->name);
            $user->email = strtolower(trim($request->email));
            $user->password = password_hash($request->password, PASSWORD_BCRYPT);
            $user->role = $request->role === 'admin' ? 'admin' : ($isFirst ? 'admin' : 'staff');
            $this->userRepository->save($user);
            Database::commitTransaction();
            return $user;
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function login(UserLoginRequest $request): User
    {
        if ($request->email === null || $request->password === null || trim($request->email) === '' || trim($request->password) === '') {
            throw new ValidationException("Email dan Password tidak boleh kosong");
        }
        $user = $this->userRepository->findByEmail($request->email);
        if ($user === null || !password_verify($request->password, $user->password)) {
            throw new ValidationException("Email atau password salah");
        }
        if (!$user->isActive) {
            throw new ValidationException("Akun dinonaktifkan");
        }
        return $user;
    }

    public function updateProfile(UserProfileUpdateRequest $request): User
    {
        if ($request->id === null || $request->name === null || trim($request->name) === '') {
            throw new ValidationException("Nama tidak boleh kosong");
        }
        if ($request->email !== null && $request->email !== '' && !filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Format email tidak valid");
        }
        try {
            Database::beginTransaction();
            $user = $this->userRepository->findById($request->id);
            if ($user === null) {
                throw new ValidationException("User tidak ditemukan");
            }
            if ($request->email && strtolower($request->email) !== strtolower($user->email)) {
                if ($this->userRepository->findByEmail($request->email) !== null) {
                    throw new ValidationException("Email sudah dipakai akun lain");
                }
                $user->email = strtolower(trim($request->email));
            }
            $user->name = trim($request->name);
            $this->userRepository->update($user);
            Database::commitTransaction();
            return $user;
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function updatePassword(UserPasswordUpdateRequest $request): User
    {
        if ($request->id === null || !$request->oldPassword || !$request->newPassword) {
            throw new ValidationException("Password lama & baru wajib diisi");
        }
        if (strlen($request->newPassword) < 8) {
            throw new ValidationException("Password baru minimal 8 karakter");
        }
        try {
            Database::beginTransaction();
            $user = $this->userRepository->findById($request->id);
            if ($user === null) {
                throw new ValidationException("User tidak ditemukan");
            }
            if (!password_verify($request->oldPassword, $user->password)) {
                throw new ValidationException("Password lama salah");
            }
            $user->password = password_hash($request->newPassword, PASSWORD_BCRYPT);
            $this->userRepository->update($user);
            Database::commitTransaction();
            return $user;
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public static function toArray(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => $user->avatarUrl,
            'is_active' => $user->isActive,
            'created_at' => $user->createdAt,
        ];
    }
}
