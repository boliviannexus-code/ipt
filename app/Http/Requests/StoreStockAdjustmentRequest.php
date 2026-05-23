<?php

namespace App\Http\Requests;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
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
            'warehouse_id' => ['required', $warehouseRule],
            'product_id' => ['required', $productRule],
            'presentation_id' => ['nullable', $presentationRule],
            'counted_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', Rule::in(['conteo_fisico', 'perdida', 'robo', 'otros'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
