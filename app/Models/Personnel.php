<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
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

    protected $fillable = ['company_id', 'position_id', 'campus_id', 'first_name', 'paternal_surname', 'maternal_surname', 'identity_document', 'birth_date', 'phone', 'email', 'is_active', 'is_sales_enabled'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_active' => 'boolean', 'is_sales_enabled' => 'boolean'];
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

    public function scopeAvailableForTeaching(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId)->where('is_active', true)
            ->whereHas('position', fn ($position) => $position->where('company_id', $companyId)->where('is_active', true)
                ->where(fn ($eligibility) => $eligibility->where('is_academic', true)
                    ->orWhereHas('area', fn ($area) => $area->where('company_id', $companyId)->where('is_active', true)
                        ->whereRaw("LOWER(TRANSLATE(TRIM(name), 'ÁÉÍÓÚáéíóú', 'AEIOUaeiou')) IN (?, ?, ?, ?)",
                            ['academico', 'academica', 'area academica', 'area academico']))));
    }

    public function scopeAssignmentFilters(Builder $query, array $filters): Builder
    {
        return $query->when($filters['area_id'] ?? null, fn ($query, $areaId) => $query->whereHas('position', fn ($position) => $position->where('area_id', $areaId)))
            ->when($filters['position_id'] ?? null, fn ($query, $positionId) => $query->where('position_id', $positionId));
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
