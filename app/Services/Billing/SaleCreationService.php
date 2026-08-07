<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SinPointOfSale;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SaleCreationService
{
    public function __construct(
        private readonly InvoiceTotalsCalculator $totals,
        private readonly PaymentMethodPolicy $paymentMethods,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Sale
    {
        $companyId = (int) $user->company_id;

        if ($companyId <= 0) {
            throw ValidationException::withMessages(['company' => 'Selecciona una empresa antes de registrar la venta.']);
        }

        $issuanceKey = (string) ($data['issuance_key'] ?? Str::uuid());
        $existing = Sale::query()->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('issuance_key', $issuanceKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($user, $data, $companyId, $issuanceKey): Sale {
                $pointOfSale = SinPointOfSale::query()
                    ->withoutGlobalScope('company')
                    ->with('branch')
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->findOrFail((int) $data['sin_point_of_sale_id']);
                $customer = Customer::query()
                    ->withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->findOrFail((int) $data['customer_id']);
                $items = collect($data['items'])->values();
                $productIds = $items->pluck('product_id')->map(static fn (mixed $id): int => (int) $id)->unique();
                $products = Product::query()
                    ->withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

                if ($products->count() !== $productIds->count()) {
                    throw ValidationException::withMessages(['items' => 'Uno o mas productos no pertenecen a la empresa o no estan activos.']);
                }

                $snapshots = $items->map(function (array $item, int $position) use ($products, $data, $companyId): array {
                    $product = $products->get((int) $item['product_id']);
                    $description = trim((string) ($item['description'] ?? $product->description));
                    $additional = trim((string) ($item['additional_description'] ?? ''));

                    if ($additional !== '') {
                        $description .= ' - '.$additional;
                    }

                    return [
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'position' => $position + 1,
                        'internal_code' => $product->internal_code,
                        'description' => $description,
                        'economic_activity_code' => (string) ($product->economic_activity_code ?: $data['economic_activity_code']),
                        'siat_product_code' => $product->siat_product_code,
                        'measurement_unit_code' => $product->measurement_unit_code,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_type' => $item['discount_type'] ?? 'FIXED',
                        'discount_percentage' => $item['discount_percentage'] ?? null,
                        'discount_amount' => $item['discount'] ?? 0,
                    ];
                });
                $calculation = $this->totals->calculate(
                    $snapshots->all(), $data['total_discount'] ?? 0, (string) ($data['additional_discount_type'] ?? 'FIXED'),
                    $data['additional_discount_percentage'] ?? null, (int) $data['currency_code'], $data['exchange_rate'] ?? 1,
                    $data['gift_card_amount'] ?? 0, 1,
                    $this->paymentMethods->isGiftCard($companyId, (int) $data['payment_method_code']),
                );
                $snapshots = $snapshots->values()->map(function (array $snapshot, int $index) use ($calculation): array {
                    $amounts = $calculation['items'][$index];

                    return [...$snapshot, 'quantity' => $amounts['quantity'], 'unit_price' => $amounts['unit_price'],
                        'discount_type' => $amounts['discount_type'], 'discount_percentage' => $amounts['discount_type'] === 'PERCENTAGE' ? $amounts['discount_percentage'] : null,
                        'discount_amount' => $amounts['discount_amount'], 'subtotal_amount' => $amounts['subtotal']];
                });

                $sale = Sale::query()->create([
                    'company_id' => $companyId,
                    'user_id' => $user->id,
                    'customer_id' => $customer->id,
                    'sin_point_of_sale_id' => $pointOfSale->id,
                    'issuance_key' => $issuanceKey,
                    'sale_status' => SaleStatus::Confirmed,
                    'economic_activity_code' => (int) $data['economic_activity_code'],
                    'payment_method_code' => (int) $data['payment_method_code'],
                    'masked_card_number' => (int) $data['payment_method_code'] === 2
                        ? $this->maskCardNumber((string) $data['card_number'])
                        : null,
                    'currency_code' => (int) $data['currency_code'],
                    'subtotal_amount' => $calculation['subtotal_sum'],
                    'discount_amount' => $calculation['discount_additional'],
                    'additional_discount_type' => $data['additional_discount_type'] ?? 'FIXED',
                    'additional_discount_percentage' => ($data['additional_discount_type'] ?? 'FIXED') === 'PERCENTAGE' ? $data['additional_discount_percentage'] : null,
                    'total_amount' => $calculation['total_amount'],
                    'exchange_rate' => $calculation['exchange_rate'],
                    'gift_card_amount' => $calculation['gift_card_amount'],
                    'total_amount_currency' => $calculation['total_amount_currency'],
                    'total_amount_subject_to_vat' => $calculation['total_amount_subject_to_vat'],
                    // La fecha fiscal proviene siempre del servidor y queda congelada en la venta.
                    'issued_at' => now(),
                ]);
                $sale->items()->createMany($snapshots->all());

                return $sale->load(['items', 'customer', 'pointOfSale.branch', 'company', 'user']);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'sales_company_id_issuance_key_unique')) {
                throw $exception;
            }

            return Sale::query()->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('issuance_key', $issuanceKey)
                ->firstOrFail();
        }
    }

    private function maskCardNumber(string $cardNumber): string
    {
        $digits = preg_replace('/\D+/', '', $cardNumber) ?? '';

        if (strlen($digits) !== 16) {
            throw ValidationException::withMessages(['card_number' => 'El número de tarjeta debe contener exactamente 16 dígitos.']);
        }

        return substr($digits, 0, 4).'00000000'.substr($digits, -4);
    }
}
