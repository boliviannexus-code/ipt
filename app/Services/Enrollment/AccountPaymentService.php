<?php

namespace App\Services\Enrollment;

use App\Enums\AccountPaymentMethod;
use App\Models\AccountCharge;
use App\Models\AccountCreditApplication;
use App\Models\AccountPayment;
use App\Models\CashRegister;
use App\Models\EnrollmentContract;
use App\Models\PaymentAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountPaymentService
{
    public function record(User $user, EnrollmentContract $contract, string $amount, array $data = []): ?AccountPayment
    {
        $paymentCents = $this->toCents($amount);
        if ($paymentCents < 0) {
            throw ValidationException::withMessages(['amount' => 'El importe del pago no puede ser negativo.']);
        }

        return DB::transaction(function () use ($user, $contract, $paymentCents, $data): ?AccountPayment {
            [$contract, $cashRegister] = $this->lockAccount($user, $contract);
            $paymentMethodCode = (int) ($data['payment_method_code'] ?? 0);
            $paymentMethod = AccountPaymentMethod::tryFrom($paymentMethodCode);
            if ($paymentCents > 0 && $paymentMethod === null) {
                throw ValidationException::withMessages(['payment_method_code' => 'Selecciona Efectivo, QR o Transferencia.']);
            }
            $reference = trim((string) ($data['reference'] ?? ''));
            if ($paymentCents > 0 && $paymentMethod?->requiresReference() && $reference === '') {
                throw ValidationException::withMessages(['reference' => 'Ingresa la referencia del pago.']);
            }

            $charges = $this->pendingCharges($contract);
            $outstandingCents = $charges->sum(fn (AccountCharge $charge): int => $this->toCents($charge->amount) - $this->toCents($charge->paid_amount));
            $available = $contract->payments()->withSum('allocations', 'amount')->get()
                ->sum(fn (AccountPayment $payment): int => $this->toCents($payment->amount) - $this->toCents($payment->allocations_sum_amount ?? 0));
            $creditToApply = min($available, $outstandingCents);
            if ($paymentCents === 0 && ($creditToApply === 0 || $creditToApply < $outstandingCents)) {
                throw ValidationException::withMessages(['amount' => 'Ingresa un monto o cubre la cuota completa con el saldo a favor.']);
            }
            if ($creditToApply > 0) {
                $this->applyCredit($user, $contract, $this->fromCents($creditToApply));
                $charges = $this->pendingCharges($contract);
                $outstandingCents -= $creditToApply;
            }
            if ($paymentCents === 0) {
                return null;
            }
            $creditCents = max(0, $paymentCents - $outstandingCents);

            $payment = AccountPayment::query()->create([
                'company_id' => $contract->company_id,
                'enrollment_contract_id' => $contract->id,
                'cash_register_id' => $cashRegister->id,
                'received_by' => $user->id,
                'amount' => $this->fromCents($paymentCents),
                'credit_amount' => $this->fromCents($creditCents),
                'payment_method_code' => $paymentMethodCode,
                'reference' => $paymentMethod->requiresReference() ? $reference : null,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $this->allocate($payment, $charges, $paymentCents - $creditCents);
            $this->updateEnrollment($contract);

            return $payment->load('allocations');
        });
    }

    public function applyCredit(User $user, EnrollmentContract $contract, string $amount): AccountCreditApplication
    {
        $requested = $this->toCents($amount);
        if ($requested <= 0) {
            throw ValidationException::withMessages(['credit_amount' => 'El importe debe ser mayor a cero.']);
        }

        return DB::transaction(function () use ($user, $contract, $requested): AccountCreditApplication {
            [$contract, $cashRegister] = $this->lockAccount($user, $contract);
            $payments = $contract->payments()->withSum('allocations', 'amount')->orderBy('id')->lockForUpdate()->get();
            $available = $payments->sum(fn (AccountPayment $payment): int => $this->toCents($payment->amount) - $this->toCents($payment->allocations_sum_amount ?? 0));
            $charges = $this->pendingCharges($contract);
            $outstanding = $charges->sum(fn (AccountCharge $charge): int => $this->toCents($charge->amount) - $this->toCents($charge->paid_amount));
            if ($requested > $available || $requested > $outstanding) {
                throw ValidationException::withMessages(['credit_amount' => 'El importe supera el saldo a favor disponible o la deuda pendiente.']);
            }

            $remaining = $requested;
            foreach ($payments as $payment) {
                $availablePayment = $this->toCents($payment->amount) - $this->toCents($payment->allocations_sum_amount ?? 0);
                $allocated = min($remaining, max(0, $availablePayment));
                $this->allocate($payment, $charges, $allocated);
                $remaining -= $allocated;
                if ($remaining === 0) {
                    break;
                }
            }
            $this->updateEnrollment($contract);

            return AccountCreditApplication::create([
                'company_id' => $contract->company_id,
                'enrollment_contract_id' => $contract->id,
                'cash_register_id' => $cashRegister->id,
                'applied_by' => $user->id,
                'amount' => $this->fromCents($requested),
                'created_at' => now(),
            ]);
        });
    }

    private function lockAccount(User $user, EnrollmentContract $contract): array
    {
        $contract = EnrollmentContract::query()->lockForUpdate()->findOrFail($contract->id);
        if ((int) $contract->company_id !== (int) $user->company_id || $contract->status === 'cancelled') {
            throw ValidationException::withMessages(['contract' => 'La matrícula no está disponible para cobros.']);
        }
        $cashRegister = CashRegister::query()->where('company_id', $user->company_id)->where('user_id', $user->id)->active()->lockForUpdate()->first();
        if ($cashRegister === null) {
            throw ValidationException::withMessages(['cash_register' => 'Debes abrir una caja antes de registrar pagos.']);
        }

        return [$contract, $cashRegister];
    }

    private function pendingCharges(EnrollmentContract $contract): Collection
    {
        return $contract->charges()->whereIn('status', ['pending', 'partial'])->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();
    }

    private function allocate(AccountPayment $payment, Collection $charges, int $remaining): void
    {
        foreach ($charges as $charge) {
            if ($remaining === 0) {
                break;
            }
            $allocated = min($remaining, $this->toCents($charge->amount) - $this->toCents($charge->paid_amount));
            if ($allocated <= 0) {
                continue;
            }
            $newPaid = $this->toCents($charge->paid_amount) + $allocated;
            $charge->update(['paid_amount' => $this->fromCents($newPaid), 'status' => $newPaid === $this->toCents($charge->amount) ? 'paid' : 'partial']);
            $allocation = PaymentAllocation::firstOrNew(['account_payment_id' => $payment->id, 'account_charge_id' => $charge->id]);
            $allocation->amount = $this->fromCents($this->toCents($allocation->amount ?? 0) + $allocated);
            $allocation->created_at ??= now();
            $allocation->save();
            $remaining -= $allocated;
        }
    }

    private function updateEnrollment(EnrollmentContract $contract): void
    {
        if ($contract->status === 'pre_enrolled') {
            $firstPaymentAt = $contract->payments()->where('amount', '>', 0)->orderBy('paid_at')->orderBy('id')->value('paid_at');
            if ($firstPaymentAt !== null) {
                $contract->update(['status' => 'enrolled', 'enrolled_at' => $firstPaymentAt]);
            }
        }
        app(EnrollmentCompletionService::class)->evaluate($contract);
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
