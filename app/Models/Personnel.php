<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Personnel extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'personnel';

    protected $fillable = ['company_id', 'position_id', 'campus_id', 'first_name', 'paternal_surname', 'maternal_surname', 'identity_document', 'birth_date', 'phone', 'email', 'is_active'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_active' => 'boolean'];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim(implode(' ', array_filter([$this->first_name, $this->paternal_surname, $this->maternal_surname]))));
    }

    protected function firstName(): Attribute
    {
        return Attribute::set(fn (string $value): string => $this->normalizeName($value));
    }

    protected function paternalSurname(): Attribute
    {
        return Attribute::set(fn (string $value): string => $this->normalizeName($value));
    }

    protected function maternalSurname(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => filled($value) ? $this->normalizeName($value) : null);
    }

    private function normalizeName(string $value): string
    {
        return Str::of($value)->squish()->lower()->title()->toString();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function moduleTeacherAssignments(): HasMany
    {
        return $this->hasMany(AcademicModuleTeacherAssignment::class);
    }
}
