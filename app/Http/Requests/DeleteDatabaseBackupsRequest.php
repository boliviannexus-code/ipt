<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteDatabaseBackupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('backups.delete') ?? false;
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'accepted'],
            'backups' => ['required', 'array', 'min:1', 'max:1000'],
            'backups.*' => ['required', 'string', 'distinct', 'regex:/\Afacturacion-[0-9]{8}-[0-9]{6}-[a-f0-9]{6}\.sql\.gz\z/'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.required' => 'Marca la casilla para confirmar la operación.',
            'confirmation.accepted' => 'Marca la casilla para confirmar la operación.',
            'backups.required' => 'Selecciona al menos un respaldo.',
            'backups.*.regex' => 'El nombre del respaldo no es válido.',
            'backups.*.distinct' => 'No selecciones el mismo respaldo más de una vez.',
        ];
    }
}
