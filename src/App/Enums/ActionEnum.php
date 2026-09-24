<?php
declare(strict_types=1);

namespace App\Enums;

enum ActionEnum: string
{
    case LOG_IN = 'log_in';
    case LOG_OUT = 'log_out';
}