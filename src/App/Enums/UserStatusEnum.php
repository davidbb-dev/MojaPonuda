<?php
declare(strict_types=1);

namespace App\Enums;

enum UserStatusEnum: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
}