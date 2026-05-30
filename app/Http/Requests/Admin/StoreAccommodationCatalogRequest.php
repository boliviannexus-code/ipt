<?php

namespace App\Http\Requests\Admin;

use App\Support\AccommodationCatalogRegistry;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccommodationCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CompanyContext::isGlobalAdmin($this->user())
            && ($this->user()?->can(AccommodationCatalogRegistry::PERMISSION) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => str($this->input('name'))->slug()->toString()]);
        }
    }

    public function rules(): array
    {
        $catalog = AccommodationCatalogRegistry::get($this->route('catalog'));
        $modelClass = $catalog['model'];
        $table = (new $modelClass)->getTable();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique($table, 'slug')],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        if ($catalog['has_capacity']) {
            $rules['capacity'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
