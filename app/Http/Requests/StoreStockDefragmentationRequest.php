<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class StoreStockDefragmentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('inventory.movements') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        $productRule = Rule::exists('products', 'id');
        $warehouseRule = Rule::exists('warehouses', 'id');
        $presentationRule = Rule::exists('presentations', 'id');

        if ($companyId !== null) {
            $productRule->where('company_id', $companyId);
            $warehouseRule->where('company_id', $companyId);
            $presentationRule->where('company_id', $companyId);
        }

        return [
            'product_id' => ['required', $productRule],
            'warehouse_id' => ['required', $warehouseRule],
            'presentation_id' => ['required', $presentationRule],
            'package_quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
