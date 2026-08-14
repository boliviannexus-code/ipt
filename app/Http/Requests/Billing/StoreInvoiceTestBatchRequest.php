<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Product;
use App\Models\SinCatalogItem;
use App\Models\SinPointOfSale;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreInvoiceTestBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invoice-tests.run') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        return [
            'sin_branch_id' => ['required', 'integer', Rule::exists('sin_branches', 'id')
                ->where('company_id', $companyId)->where('is_active', true)],
            'sin_point_of_sale_id' => ['required', 'integer', Rule::exists('sin_points_of_sale', 'id')
                ->where('company_id', $companyId)->where('is_active', true)],
            'economic_activity_code' => ['required', 'integer'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')
                ->where('company_id', $companyId)->where('is_active', true)],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')
                ->where('company_id', $companyId)->where('is_active', true)],
            'payment_method_code' => ['required', 'integer', 'not_in:2', Rule::exists('sin_catalog_items', 'classifier_code')
                ->where('company_id', $companyId)->where('catalog_key', 'tipos_metodo_pago')->where('is_active', true)],
            'currency_code' => ['required', 'integer', Rule::exists('sin_catalog_items', 'classifier_code')
                ->where('company_id', $companyId)->where('catalog_key', 'tipos_moneda')->where('is_active', true)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'invoice_count' => ['required', 'integer', 'between:1,25'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $companyId = CompanyContext::id($this->user());
            $pointMatchesBranch = SinPointOfSale::query()->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->whereKey((int) $this->input('sin_point_of_sale_id'))
                ->where('sin_branch_id', (int) $this->input('sin_branch_id'))
                ->exists();

            if (! $pointMatchesBranch) {
                $validator->errors()->add('sin_point_of_sale_id', 'El punto de venta no pertenece a la sucursal seleccionada.');
            }

            $activityCode = (string) $this->input('economic_activity_code');
            $activityExists = SinCatalogItem::query()->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('catalog_key', 'actividades')
                ->where('is_active', true)
                ->where(function ($query) use ($activityCode): void {
                    $query->where('classifier_code', $activityCode)
                        ->orWhere('raw_data->codigoCaeb', $activityCode);
                })->exists();

            if (! $activityExists) {
                $validator->errors()->add('economic_activity_code', 'Selecciona una actividad económica vigente.');
            }

            $productMatchesActivity = Product::query()->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->whereKey((int) $this->input('product_id'))
                ->where('economic_activity_code', (int) $this->input('economic_activity_code'))
                ->exists();

            if (! $productMatchesActivity) {
                $validator->errors()->add('product_id', 'El producto no pertenece a la actividad económica seleccionada.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sin_branch_id.required' => 'Selecciona una sucursal.',
            'economic_activity_code.required' => 'Selecciona una actividad económica.',
            'invoice_count.between' => 'La prueba debe contener entre 1 y 25 facturas.',
            'payment_method_code.not_in' => 'Las pruebas masivas no admiten tarjeta porque requieren un número individual.',
        ];
    }
}
