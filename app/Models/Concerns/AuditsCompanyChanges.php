<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\PointOfSale;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Support\CompanyContext;

trait AuditsCompanyChanges
{
    use \OwenIt\Auditing\Auditable;

    public function transformAudit(array $data): array
    {
        $data['company_id'] = $this->auditCompanyId();

        return $data;
    }

    public function generateTags(): array
    {
        return array_filter([
            'company:'.($this->auditCompanyId() ?? 'global'),
            class_basename($this),
        ]);
    }

    private function auditCompanyId(): ?int
    {
        $companyId = CompanyContext::id();

        if ($companyId !== null) {
            return $companyId;
        }

        if ($this instanceof \App\Models\Company && $this->getKey() !== null) {
            return (int) $this->getKey();
        }

        if ($this->getAttribute('company_id') !== null) {
            return (int) $this->getAttribute('company_id');
        }

        if ($this->getAttribute('warehouse_id') !== null) {
            return Warehouse::query()->whereKey($this->getAttribute('warehouse_id'))->value('company_id');
        }

        if ($this->getAttribute('branch_id') !== null) {
            return Branch::query()->whereKey($this->getAttribute('branch_id'))->value('company_id');
        }

        if ($this->getAttribute('point_of_sale_id') !== null) {
            return PointOfSale::query()->whereKey($this->getAttribute('point_of_sale_id'))->value('company_id');
        }

        if ($this->getAttribute('cash_register_id') !== null) {
            return CashRegister::query()
                ->join('point_of_sales', 'point_of_sales.id', '=', 'cash_registers.point_of_sale_id')
                ->where('cash_registers.id', $this->getAttribute('cash_register_id'))
                ->value('point_of_sales.company_id');
        }

        if ($this->getAttribute('purchase_id') !== null) {
            return Purchase::query()
                ->join('warehouses', 'warehouses.id', '=', 'purchases.warehouse_id')
                ->where('purchases.id', $this->getAttribute('purchase_id'))
                ->value('warehouses.company_id');
        }

        if ($this->getAttribute('sale_id') !== null) {
            return Sale::query()
                ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
                ->where('sales.id', $this->getAttribute('sale_id'))
                ->value('warehouses.company_id');
        }

        return auth()->user()?->company_id ? (int) auth()->user()->company_id : null;
    }
}
