<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('products.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');
        $companyId = CompanyContext::id($this->user());

        return [
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($productId)],
            'category_id' => ['required', Rule::exists('categories', 'id')->when($companyId, fn ($rule) => $rule->where('company_id', $companyId))],
            'measurement_unit_id' => ['required', Rule::exists('measurement_units', 'id')->when($companyId, fn ($rule) => $rule->where('company_id', $companyId))],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0', 'gte:purchase_price'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
