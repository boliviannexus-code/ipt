<?php

use App\Models\ProgramGradingScheme;
use App\Services\Academic\AttendanceGradingPolicy;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ProgramGradingScheme::withoutGlobalScopes()->where('status', 'draft')->orderBy('id')
            ->each(fn (ProgramGradingScheme $scheme) => AttendanceGradingPolicy::ensureDraft($scheme));
    }

    public function down(): void
    {
        // Preserve attendance configuration and any subsequent academic records.
    }
};
