<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramLevel extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'program_id', 'name', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_active' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicModules(): HasMany
    {
        return $this->hasMany(AcademicModule::class);
    }
}
