<?php

namespace App\Http\Requests\Rectorate;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlanStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rectorate.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'program_id' => [
                'required',
                'integer',
                Rule::exists('programs', 'id')->where('company_id', CompanyContext::id($this->user())),
            ],
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where('company_id', CompanyContext::id($this->user())),
            ],
            'commercial_origin_id' => [
                'required',
                'integer',
                Rule::exists('commercial_origins', 'id')->where('company_id', CompanyContext::id($this->user())),
            ],
            'sales_executive_id' => [
                'required',
                'integer',
                Rule::exists('personnel', 'id')
                    ->where('company_id', CompanyContext::id($this->user()))
                    ->where('is_active', true),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['program_id', 'plan_id'])) {
                $linked = DB::table('plan_program')
                    ->where('program_id', $this->integer('program_id'))
                    ->where('plan_id', $this->integer('plan_id'))
                    ->exists();

                if (! $linked) {
                    $validator->errors()->add('plan_id', 'El plan seleccionado no pertenece al programa.');
                }
            }

            if (! $validator->errors()->has('sales_executive_id')) {
                $isSalesExecutive = DB::table('personnel')
                    ->join('positions', 'positions.id', '=', 'personnel.position_id')
                    ->where('personnel.id', $this->integer('sales_executive_id'))
                    ->where('personnel.company_id', CompanyContext::id($this->user()))
                    ->where('personnel.is_active', true)
                    ->where('positions.is_active', true)
                    ->whereRaw('LOWER(positions.name) = ?', ['ejecutivo de ventas'])
                    ->exists();

                if (! $isSalesExecutive) {
                    $validator->errors()->add('sales_executive_id', 'Selecciona un ejecutivo de ventas activo.');
                }
            }
        });
    }
}
