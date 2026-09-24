<?php
declare(strict_types=1);

namespace App\Enums;

enum UserRoleEnum: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case SUPERADMIN = 'superadmin';
}