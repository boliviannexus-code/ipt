<?php

namespace App\Services\Enrollment;

use App\Models\AccountCharge;
use App\Models\AccountPayment;
use App\Models\CashRegister;
use App\Models\EnrollmentContract;
use App\Models\PaymentAllocation;
use App\Models\SinCatalogItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountPaymentService
{
    public function record(User $user, EnrollmentContract $contract, string $amount, array $data = []): AccountPayment
    {
        $paymentCents = $this->toCents($amount);
        if ($paymentCents <= 0) {
            throw ValidationException::withMessages(['amount' => 'El importe del pago debe ser mayor a cero.']);
        }

        return DB::transaction(function () use ($user, $contract, $paymentCents, $data): AccountPayment {
            $contract = EnrollmentContract::query()->lockForUpdate()->findOrFail($contract->id);
            if ((int) $contract->company_id !== (int) $user->company_id) {
                throw ValidationException::withMessages(['contract' => 'El contrato no pertenece a la empresa del usuario.']);
            }

            $cashRegister = CashRegister::query()
                ->where('user_id', $user->id)
                ->active()
                ->lockForUpdate()
                ->first();
            if ($cashRegister === null) {
                throw ValidationException::withMessages(['cash_register' => 'Debes abrir una caja antes de registrar pagos.']);
            }

            $paymentMethodCode = (int) ($data['payment_method_code'] ?? 0);
            $validPaymentMethod = SinCatalogItem::withoutGlobalScope('company')
                ->where('company_id', $contract->company_id)
                ->where('catalog_key', 'tipos_metodo_pago')
                ->where('classifier_code', (string) $paymentMethodCode)
                ->where('is_active', true)
                ->exists();
            if (! $validPaymentMethod) {
                throw ValidationException::withMessages(['payment_method_code' => 'Selecciona un método de pago vigente del catálogo del SIN.']);
            }

            $charges = AccountCharge::query()
                ->where('enrollment_contract_id', $contract->id)
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $outstandingCents = $charges->sum(fn (AccountCharge $charge): int => $this->toCents($charge->amount) - $this->toCents($charge->paid_amount));
            if ($paymentCents > $outstandingCents) {
                throw ValidationException::withMessages(['amount' => 'El pago no puede superar el saldo pendiente.']);
            }

            $payment = AccountPayment::query()->create([
                'company_id' => $contract->company_id,
                'enrollment_contract_id' => $contract->id,
                'cash_register_id' => $cashRegister->id,
                'received_by' => $user->id,
                'amount' => $this->fromCents($paymentCents),
                'payment_method_code' => $paymentMethodCode,
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $remaining = $paymentCents;
            foreach ($charges as $charge) {
                if ($remaining === 0) {
                    break;
                }
                $chargeOutstanding = $this->toCents($charge->amount) - $this->toCents($charge->paid_amount);
                $allocated = min($remaining, $chargeOutstanding);
                $newPaid = $this->toCents($charge->paid_amount) + $allocated;
                $charge->update([
                    'paid_amount' => $this->fromCents($newPaid),
                    'status' => $newPaid === $this->toCents($charge->amount) ? 'paid' : 'partial',
                ]);
                PaymentAllocation::create([
                    'account_payment_id' => $payment->id,
                    'account_charge_id' => $charge->id,
                    'amount' => $this->fromCents($allocated),
                    'created_at' => now(),
                ]);
                $remaining -= $allocated;
            }

            $initialCharge = AccountCharge::query()
                ->where('enrollment_contract_id', $contract->id)
                ->oldest('period')
                ->first();
            if ($contract->status === 'pre_enrolled' && $initialCharge?->status === 'paid') {
                $contract->update(['status' => 'enrolled', 'enrolled_at' => now()]);
            }

            return $payment->load('allocations');
        });
    }

    private function toCents(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
