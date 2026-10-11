<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RestoreDatabaseBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('backups.restore') ?? false;
    }

    public function rules(): array
    {
        return ['confirmation' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return [
            'confirmation.required' => 'Marca la casilla para confirmar la operación.',
            'confirmation.accepted' => 'Marca la casilla para confirmar la operación.',
        ];
    }
}
