<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceEmissionMode;
use App\Enums\InvoiceFiscalStatus;
use App\Enums\InvoiceTestBatchStatus;
use App\Enums\InvoiceTestItemStatus;
use App\Enums\SiatEnvironment;
use App\Enums\SignificantEventStatus;
use App\Models\InvoiceTestBatch;
use App\Models\InvoiceTestBatchItem;
use App\Models\Product;
use App\Models\SinAuthorization;
use App\Models\SinInvoiceIssue;
use App\Models\SinSignificantEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InvoiceTestBatchService
{
    /** @return Collection<int, InvoiceTestBatchItem> */
    public function prepareCancellation(InvoiceTestBatch $batch, int $reasonCode): Collection
    {
        $this->assertPilotEnvironment((int) $batch->company_id);

        return DB::transaction(function () use ($batch, $reasonCode): Collection {
            $locked = InvoiceTestBatch::query()->withoutGlobalScope('company')->lockForUpdate()->findOrFail($batch->id);
            if ($locked->cancellation_status?->isActive()) {
                throw ValidationException::withMessages(['cancellation' => 'La anulación de este lote ya está en ejecución.']);
            }

            $items = $locked->items()->withoutGlobalScope('company')
                ->where('item_status', InvoiceTestItemStatus::Succeeded)
                ->whereHas('invoice', fn ($query) => $query->where('fiscal_status', InvoiceFiscalStatus::Validated))
                ->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cancellation' => 'El lote no tiene facturas validadas pendientes de anulación.']);
            }

            $locked->update(['cancellation_status' => InvoiceTestBatchStatus::Pending,
                'cancellation_reason_code' => $reasonCode, 'cancellation_requested_count' => $items->count(),
                'cancellation_processed_count' => 0, 'cancellation_successful_count' => 0,
                'cancellation_failed_count' => 0, 'cancellation_started_at' => null, 'cancellation_finished_at' => null]);
            $items->each->update(['cancellation_status' => InvoiceTestItemStatus::Pending,
                'cancellation_message' => null, 'cancellation_started_at' => null, 'cancellation_finished_at' => null]);

            return $items;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): InvoiceTestBatch
    {
        $companyId = (int) $user->company_id;
        $this->assertPilotEnvironment($companyId);
        $this->assertOperationalState($companyId);

        $product = Product::query()->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->findOrFail((int) $data['product_id']);

        if (! $product->economic_activity_code) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto necesita una actividad económica homologada para la prueba.',
            ]);
        }

        if ((int) $product->economic_activity_code !== (int) $data['economic_activity_code']) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto no pertenece a la actividad económica seleccionada.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $companyId, $product): InvoiceTestBatch {
            $active = InvoiceTestBatch::query()->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where(function ($query): void {
                    $query->whereIn('batch_status', [InvoiceTestBatchStatus::Pending, InvoiceTestBatchStatus::Running])
                        ->orWhereIn('cancellation_status', [InvoiceTestBatchStatus::Pending, InvoiceTestBatchStatus::Running]);
                })
                ->lockForUpdate()
                ->exists();

            if ($active) {
                throw ValidationException::withMessages([
                    'invoice_count' => 'Ya existe una prueba en ejecución. Espera a que termine antes de iniciar otra.',
                ]);
            }

            $batch = InvoiceTestBatch::query()->create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'sin_point_of_sale_id' => (int) $data['sin_point_of_sale_id'],
                'customer_id' => (int) $data['customer_id'],
                'product_id' => $product->id,
                'batch_key' => (string) Str::uuid(),
                'batch_status' => InvoiceTestBatchStatus::Pending,
                'requested_count' => (int) $data['invoice_count'],
                'economic_activity_code' => (int) $data['economic_activity_code'],
                'payment_method_code' => (int) $data['payment_method_code'],
                'currency_code' => (int) $data['currency_code'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
            ]);

            $batch->items()->createMany(collect(range(1, $batch->requested_count))
                ->map(fn (int $position): array => [
                    'company_id' => $companyId,
                    'position' => $position,
                    'issuance_key' => (string) Str::uuid(),
                ])->all());

            return $batch->load(['items', 'product', 'customer', 'pointOfSale.branch']);
        }, 3);
    }

    public function assertPilotEnvironment(int $companyId): void
    {
        $environment = SinAuthorization::query()->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->value('environment_code');

        $environment = $environment instanceof SiatEnvironment
            ? $environment
            : SiatEnvironment::tryFrom((int) $environment);

        if ($environment !== SiatEnvironment::TestingAndPilot) {
            throw ValidationException::withMessages([
                'environment' => 'Las pruebas masivas están bloqueadas fuera del ambiente Piloto del SIN.',
            ]);
        }
    }

    private function assertOperationalState(int $companyId): void
    {
        $openEvent = SinSignificantEvent::query()->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNull('closed_at')
            ->whereNotIn('event_status', [SignificantEventStatus::Completed, SignificantEventStatus::Expired])
            ->exists();
        $offlinePending = SinInvoiceIssue::query()->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('emission_mode', InvoiceEmissionMode::OfflineDigital)
            ->whereIn('fiscal_status', [InvoiceFiscalStatus::OfflineIssued, InvoiceFiscalStatus::PendingPackage])
            ->exists();

        if ($openEvent || $offlinePending) {
            throw ValidationException::withMessages([
                'environment' => 'Regulariza la contingencia y las facturas fuera de línea antes de iniciar una prueba masiva.',
            ]);
        }
    }
}
