<?php
declare(strict_types=1);

namespace App\Enums;

enum ListingTypeEnum: string
{
    case AUCTION = 'auction';
    case FIXED_PRICE = 'fixed_price';
}