<?php

declare(strict_types=1);

namespace App\Enums;

enum GradingSkillMode: string
{
    case Single = 'single_skill';
    case Multiple = 'multiple_skills';
}
