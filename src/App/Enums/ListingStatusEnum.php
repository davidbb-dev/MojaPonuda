<?php
declare(strict_types=1);

namespace App\Enums;

enum ListingStatusEnum: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case DRAFT = 'draft';
    case SOLD = 'sold';
    case EXPIRED = 'expired';
}