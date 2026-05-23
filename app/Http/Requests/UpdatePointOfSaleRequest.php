<?php

namespace App\Http\Requests;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePointOfSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('point-of-sales.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $pointOfSaleId = $this->route('point_of_sale')?->id ?? $this->route('point_of_sale');
        $branchRule = Rule::exists('branches', 'id')->whereNull('deleted_at');
        $warehouseRule = Rule::exists('warehouses', 'id')
            ->where('branch_id', $this->integer('branch_id'))
            ->whereNull('deleted_at');
        $userRule = Rule::exists('users', 'id')
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($companyId = CompanyContext::id($this->user())) {
            $branchRule->where('company_id', $companyId);
            $warehouseRule->where('company_id', $companyId);
            $userRule->where('company_id', $companyId);
        }

        return [
            'branch_id' => [
                'required',
                $branchRule,
            ],
            'warehouse_id' => [
                'required',
                $warehouseRule,
                Rule::unique('point_of_sales', 'warehouse_id')->ignore($pointOfSaleId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'receipt_prefix' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/'],
            'receipt_next_number' => ['nullable', 'integer', 'min:1'],
            'receipt_digits' => ['nullable', 'integer', 'min:1', 'max:12'],
            'users' => ['nullable', 'array'],
            'users.*' => [
                'integer',
                $userRule,
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'sucursal',
            'warehouse_id' => 'almacen vinculado',
            'name' => 'nombre',
            'receipt_prefix' => 'prefijo de comprobante',
            'receipt_next_number' => 'siguiente numero de comprobante',
            'receipt_digits' => 'digitos de comprobante',
            'users' => 'usuarios asignados',
            'users.*' => 'usuario asignado',
            'is_active' => 'estado',
        ];
    }
}
