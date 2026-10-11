<?php

declare(strict_types=1);

namespace App\Enums;

enum GradingFrequency: string
{
    case Daily = 'daily';
    case Single = 'single';
}
