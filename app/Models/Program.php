<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Program extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = ['company_id', 'title', 'enrollment_code', 'duration_months'];

    protected function casts(): array
    {
        return ['duration_months' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class)->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RectorateApplication::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EnrollmentContract::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(ProgramLevel::class)->orderBy('position');
    }

    public function academicModules(): HasMany
    {
        return $this->hasMany(AcademicModule::class);
    }

    public function gradingScheme(): HasOne
    {
        return $this->hasOne(ProgramGradingScheme::class)->latestOfMany('version');
    }

    public function gradingSchemes(): HasMany
    {
        return $this->hasMany(ProgramGradingScheme::class)->orderByDesc('version');
    }

    protected static function booted(): void
    {
        static::created(function (Program $program): void {
            $program->gradingSchemes()->create([
                'company_id' => $program->company_id,
                'version' => 1,
                'passing_score' => 51,
                'status' => 'draft',
                'is_active' => false,
            ]);
        });
    }
}
