<?php

declare(strict_types=1);

namespace App\Services\Rectorate;

use App\Models\EnrollmentContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EnrollmentContractDisablingService
{
    public function disable(EnrollmentContract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            $contract = EnrollmentContract::query()->lockForUpdate()->findOrFail($contract->id);

            if ($contract->payments()->exists() || $contract->charges()->where('paid_amount', '>', 0)->exists()) {
                throw ValidationException::withMessages([
                    'contract' => 'El contrato no puede inhabilitarse porque ya tiene cobros registrados.',
                ]);
            }

            abort_unless($contract->application?->status === 'completed', 409, 'Solo se pueden inhabilitar contratos de matrículas completadas.');
            abort_if($contract->status === 'cancelled', 409, 'El contrato ya se encuentra inhabilitado.');

            $student = $contract->student;
            $contract->charges()->delete();
            $contract->update(['status' => 'cancelled']);
            $student?->delete();
        });
    }
}
