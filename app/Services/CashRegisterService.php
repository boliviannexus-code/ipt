<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashRegisterExpense;
use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterService
{
    public function openForUser(array $data, User $user): CashRegister
    {
        return DB::transaction(function () use ($data, $user): CashRegister {
            $this->ensureUserHasNoOpenRegister($user);

            $pointOfSale = PointOfSale::query()
                ->with(['warehouse', 'users'])
                ->where('is_active', true)
                ->when(CompanyContext::id($user), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->lockForUpdate()
                ->findOrFail((int) $data['point_of_sale_id']);

            $this->ensureUserCanUsePointOfSale($pointOfSale, $user);
            $this->ensurePointOfSaleHasNoOpenRegister($pointOfSale);

            return CashRegister::query()
                ->create([
                    'point_of_sale_id' => $pointOfSale->id,
                    'branch_id' => $pointOfSale->branch_id,
                    'user_id' => $user->id,
                    'opening_amount' => $data['opening_amount'],
                    'opened_at' => now(),
                    'status' => 'open',
                ])
                ->load(['pointOfSale.warehouse', 'branch', 'user']);
        });
    }

    public function openRegisterFor(User $user): ?CashRegister
    {
        return $this->openRegisterQueryFor($user)
            ->first();
    }

    public function cashSummary(CashRegister $cashRegister): array
    {
        $openingCents = $this->moneyToCents($cashRegister->opening_amount);
        $cashSalesCents = $this->cashSalesCents((int) $cashRegister->id);
        $expenseCents = $this->expenseCents((int) $cashRegister->id);
        $payments = $this->paymentSummary((int) $cashRegister->id);
        $sales = $this->salesForSummary((int) $cashRegister->id);
        $expenses = $this->expensesForSummary((int) $cashRegister->id);
        $completedSales = Sale::query()
            ->where('cash_register_id', $cashRegister->id)
            ->where('status', 'completed');
        $salesCents = $this->moneyToCents((clone $completedSales)->sum('total'));

        return [
            'opening' => $openingCents / 100,
            'sales_total' => $salesCents / 100,
            'sales_count' => (clone $completedSales)->count(),
            'cash_sales' => $cashSalesCents / 100,
            'expenses' => $expenseCents / 100,
            'available' => ($openingCents + $cashSalesCents - $expenseCents) / 100,
            'payments' => $payments,
            'sales' => $sales,
            'expense_details' => $expenses,
        ];
    }

    public function registerExpense(array $data, User $user): CashRegisterExpense
    {
        return DB::transaction(function () use ($data, $user): CashRegisterExpense {
            $cashRegister = $this->openRegisterQueryFor($user, CashRegister::query())
                ->lockForUpdate()
                ->first();

            if (! $cashRegister) {
                throw ValidationException::withMessages([
                    'amount' => 'Debes abrir caja antes de registrar egresos.',
                ])->errorBag('cashExpense');
            }

            $amountCents = $this->moneyToCents($data['amount']);
            $availableCents = $this->moneyToCents($cashRegister->opening_amount)
                + $this->cashSalesCents((int) $cashRegister->id)
                - $this->expenseCents((int) $cashRegister->id);

            if ($amountCents > $availableCents) {
                throw ValidationException::withMessages([
                    'amount' => 'No hay efectivo suficiente en esta caja para registrar el egreso.',
                ])->errorBag('cashExpense');
            }

            return CashRegisterExpense::query()->create([
                'cash_register_id' => $cashRegister->id,
                'point_of_sale_id' => $cashRegister->point_of_sale_id,
                'user_id' => $user->id,
                'responsible_name' => trim((string) $data['responsible_name']),
                'detail' => trim((string) $data['detail']),
                'amount' => $amountCents / 100,
                'spent_at' => now(),
            ]);
        });
    }

    public function closeForUser(array $data, User $user): CashRegister
    {
        return DB::transaction(function () use ($data, $user): CashRegister {
            $cashRegister = $this->openRegisterQueryFor($user, CashRegister::query())
                ->lockForUpdate()
                ->first();

            if (! $cashRegister) {
                throw ValidationException::withMessages([
                    'closing_amount' => 'No tienes una caja abierta para cerrar.',
                ])->errorBag('cashClose');
            }

            $cashRegister->update([
                'closing_amount' => $this->moneyToCents($data['closing_amount']) / 100,
                'closed_at' => now(),
                'status' => 'closed',
            ]);

            return $cashRegister->fresh(['pointOfSale', 'branch', 'user']);
        });
    }

    private function ensureUserCanUsePointOfSale(PointOfSale $pointOfSale, User $user): void
    {
        if (! $pointOfSale->users->contains($user)) {
            throw ValidationException::withMessages([
                'point_of_sale_id' => 'No tienes asignado este punto de venta.',
            ]);
        }
    }

    private function ensureUserHasNoOpenRegister(User $user): void
    {
        if ($this->openRegisterQueryFor($user)->exists()) {
            throw ValidationException::withMessages([
                'point_of_sale_id' => 'Ya tienes una caja abierta.',
            ]);
        }
    }

    private function openRegisterQueryFor(User $user, ?Builder $query = null): Builder
    {
        $query ??= CashRegister::query();

        return $query
            ->with(['pointOfSale.warehouse', 'branch'])
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->whereHas('pointOfSale', function (Builder $pointOfSale) use ($user): Builder {
                return $pointOfSale
                    ->where('is_active', true)
                    ->when(CompanyContext::id($user), fn (Builder $query, int $companyId): Builder => $query->where('company_id', $companyId))
                    ->whereHas('users', fn (Builder $users): Builder => $users->whereKey($user->id));
            })
            ->latest('opened_at');
    }

    private function ensurePointOfSaleHasNoOpenRegister(PointOfSale $pointOfSale): void
    {
        if (CashRegister::query()->where('point_of_sale_id', $pointOfSale->id)->where('status', 'open')->exists()) {
            throw ValidationException::withMessages([
                'point_of_sale_id' => 'Este punto de venta ya tiene una caja abierta.',
            ]);
        }
    }

    private function cashSalesCents(int $cashRegisterId): int
    {
        return $this->moneyToCents(SalePayment::query()
            ->where('payment_method_name', 'Efectivo')
            ->whereHas('sale', fn ($sale) => $sale
                ->where('cash_register_id', $cashRegisterId)
                ->where('status', 'completed'))
            ->sum('amount'));
    }

    private function paymentSummary(int $cashRegisterId)
    {
        return SalePayment::query()
            ->selectRaw('payment_method_name, SUM(amount) as total, COUNT(*) as payments_count')
            ->whereHas('sale', fn ($sale) => $sale
                ->where('cash_register_id', $cashRegisterId)
                ->where('status', 'completed'))
            ->groupBy('payment_method_name')
            ->orderBy('payment_method_name')
            ->get()
            ->map(fn ($payment): array => [
                'name' => (string) $payment->payment_method_name,
                'total' => (float) $payment->total,
                'payments_count' => (int) $payment->payments_count,
            ]);
    }

    private function salesForSummary(int $cashRegisterId)
    {
        return Sale::query()
            ->with(['payments' => fn ($payments) => $payments->orderBy('payment_method_name')])
            ->where('cash_register_id', $cashRegisterId)
            ->orderByDesc('sale_date')
            ->get(['id', 'receipt_number', 'sale_date', 'total', 'status']);
    }

    private function expensesForSummary(int $cashRegisterId)
    {
        return CashRegisterExpense::query()
            ->where('cash_register_id', $cashRegisterId)
            ->orderByDesc('spent_at')
            ->get(['id', 'responsible_name', 'detail', 'amount', 'spent_at']);
    }

    private function expenseCents(int $cashRegisterId): int
    {
        return $this->moneyToCents(CashRegisterExpense::query()
            ->where('cash_register_id', $cashRegisterId)
            ->sum('amount'));
    }

    private function moneyToCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
