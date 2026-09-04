<?php

declare(strict_types=1);

namespace App\Enums;

enum GradingScoringMethod: string
{
    case Percentage = 'percentage';
    case Simple = 'simple';
}
