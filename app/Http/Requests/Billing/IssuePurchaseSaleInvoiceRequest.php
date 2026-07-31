<?php

namespace App\Http\Requests\Billing;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssuePurchaseSaleInvoiceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('items'))) {
            $items = json_decode($this->input('items'), true);

            if (is_array($items)) {
                $this->merge(['items' => $items]);
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('invoices.issue') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        return [
            'sin_point_of_sale_id' => [
                'required',
                'integer',
                Rule::exists('sin_points_of_sale', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'economic_activity_code' => ['required', 'integer', 'min:1'],
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'issued_at' => ['required', 'date'],
            'payment_method_code' => ['required', 'integer', 'min:1'],
            'currency_code' => ['required', 'integer', 'min:1'],
            'total_discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.additional_description' => ['nullable', 'string', 'max:485'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Agrega al menos un producto o servicio.',
            'items.min' => 'Agrega al menos un producto o servicio.',
            'sin_point_of_sale_id.required' => 'Selecciona la sucursal y punto de venta.',
            'customer_id.required' => 'Selecciona el cliente.',
        ];
    }
}
