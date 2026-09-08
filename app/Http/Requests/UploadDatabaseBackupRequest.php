<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadDatabaseBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('backups.restore') ?? false;
    }

    public function rules(): array
    {
        return [
            'backup_file' => ['required', 'file', 'extensions:gz', 'max:'.config('backups.upload_max_kilobytes', 102400)],
            'confirmation' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'backup_file.required' => 'Selecciona un archivo de respaldo.',
            'backup_file.extensions' => 'El respaldo debe ser un archivo .sql.gz.',
            'backup_file.max' => 'El archivo supera el tamaño máximo permitido.',
            'confirmation.required' => 'Marca la casilla para confirmar la operación.',
            'confirmation.accepted' => 'Marca la casilla para confirmar la operación.',
        ];
    }
}
