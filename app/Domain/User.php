<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Domain;

class User
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'staff';
    public ?string $avatarUrl = null;
    public bool $isActive = true;
    public ?string $createdAt = null;
}
