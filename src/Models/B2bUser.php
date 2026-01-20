<?php
// src/Models/B2bUser.php

declare(strict_types=1);

namespace App\Models;

class B2bUser
{
    public ?int $id = null;
    public int $company_id;
    public ?string $cari_code = null;
    public string $username;
    public string $email;
    public string $password;
    public int $status = 1;
    public string $role;
    public ?string $created_at = null;
    public ?string $updated_at = null;
}

