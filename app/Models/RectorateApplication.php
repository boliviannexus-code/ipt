<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class RectorateApplication extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::updating(function (self $application): void {
            $protected = ['student_id', 'program_id', 'account_number', 'campus_id', 'company_id', 'status'];
            foreach (array_keys($application->getAttributes()) as $field) {
                if (str_starts_with($field, 'student_')) {
                    $protected[] = $field;
                }
            }
            if ($application->isDirty($protected)
                && self::withoutGlobalScopes()->whereKey($application->id)->where('status', 'completed')->exists()) {
                throw ValidationException::withMessages([
                    'application' => 'No se permite cambiar estudiante, programa o matrícula de una inscripción confirmada.',
                ]);
            }
        });
    }

    protected $fillable = [
        'company_id', 'campus_id', 'account_number', 'customer_id', 'program_id', 'plan_id', 'commercial_origin_id', 'sales_executive_id', 'student_id', 'identity_document', 'first_name', 'paternal_surname',
        'maternal_surname', 'birth_date', 'email', 'phone', 'student_identity_document',
        'student_first_name', 'student_paternal_surname', 'student_maternal_surname',
        'student_birth_date', 'student_email', 'student_phone', 'student_relationship',
        'student_gender', 'primary_contact_type', 'reference_first_name', 'reference_last_name',
        'reference_relationship', 'reference_phone', 'current_step', 'status',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'student_birth_date' => 'date', 'current_step' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function commercialOrigin(): BelongsTo
    {
        return $this->belongsTo(CommercialOrigin::class);
    }

    public function salesExecutive(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'sales_executive_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function contract(): HasOne
    {
        return $this->hasOne(EnrollmentContract::class);
    }
}
