<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('purchases.create') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        $supplierRule = Rule::exists('suppliers', 'id');
        $warehouseRule = Rule::exists('warehouses', 'id');
        $productRule = Rule::exists('products', 'id');
        $presentationRule = Rule::exists('presentations', 'id');

        if ($companyId !== null) {
            $supplierRule->where('company_id', $companyId);
            $warehouseRule->where('company_id', $companyId);
            $productRule->where('company_id', $companyId);
            $presentationRule->where('company_id', $companyId);
        }

        return [
            'supplier_id' => ['nullable', $supplierRule],
            'warehouse_id' => ['required', $warehouseRule],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', $productRule],
            'items.*.presentation_id' => ['required', $presentationRule],
            'items.*.package_quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'warehouse_id' => 'almacen destino',
            'purchase_date' => 'fecha de compra',
            'items' => 'detalle de productos',
            'items.*.product_id' => 'producto',
            'items.*.presentation_id' => 'presentacion',
            'items.*.package_quantity' => 'cantidad',
            'items.*.unit_price' => 'precio de la presentacion',
        ];
    }
}
