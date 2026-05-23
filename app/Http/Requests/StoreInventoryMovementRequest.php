<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('inventory.movements') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        $warehouseRule = Rule::exists('warehouses', 'id');
        $productRule = Rule::exists('products', 'id');
        $presentationRule = Rule::exists('presentations', 'id');

        if ($companyId !== null) {
            $warehouseRule->where('company_id', $companyId);
            $productRule->where('company_id', $companyId);
            $presentationRule->where('company_id', $companyId);
        }

        return [
            'operation' => ['required', Rule::in(['in', 'out', 'transfer'])],
            'warehouse_id' => ['required_if:operation,in,out', 'nullable', $warehouseRule],
            'source_warehouse_id' => ['required_if:operation,transfer', 'nullable', $warehouseRule],
            'target_warehouse_id' => ['required_if:operation,transfer', 'nullable', $warehouseRule, 'different:source_warehouse_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', $productRule],
            'items.*.presentation_id' => ['nullable', $presentationRule],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'required_without:items.*.package_quantity'],
            'items.*.package_quantity' => ['nullable', 'integer', 'min:1', 'required_with:items.*.presentation_id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
