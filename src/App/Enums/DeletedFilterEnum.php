<?php
declare(strict_types=1);

namespace App\Enums;

enum DeletedFilterEnum: string
{
    case ONLY_ACTIVE = 'only_active';
    case ONLY_DELETED = 'only_deleted';
    case ALL = 'all';
}