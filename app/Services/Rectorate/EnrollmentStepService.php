<?php

namespace App\Services\Rectorate;

use App\Models\RectorateApplication;
use App\Models\Student;
use App\Services\Enrollment\ContractService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentStepService
{
    public function __construct(private readonly ContractService $contracts) {}

    public function assignPlan(RectorateApplication $application, int $programId, int $planId, int $commercialOriginId, int $salesExecutiveId): RectorateApplication
    {
        $application->update([
            'program_id' => $programId,
            'plan_id' => $planId,
            'commercial_origin_id' => $commercialOriginId,
            'sales_executive_id' => $salesExecutiveId,
            'current_step' => 3,
        ]);

        return $application->refresh();
    }

    public function saveStudent(RectorateApplication $application, array $data): RectorateApplication
    {
        if (($data['student_relationship'] ?? null) === 'Titular') {
            $data = [
                ...$data,
                'student_identity_document' => $application->identity_document,
                'student_first_name' => $application->first_name,
                'student_paternal_surname' => $application->paternal_surname,
                'student_maternal_surname' => $application->maternal_surname,
                'student_birth_date' => $application->birth_date?->toDateString(),
                'student_email' => $application->email,
                'student_phone' => $application->phone,
            ];
        }

        foreach (['student_first_name', 'student_paternal_surname', 'student_maternal_surname'] as $field) {
            if (filled($data[$field] ?? null)) {
                $data[$field] = Str::of($data[$field])->squish()->lower()->title()->toString();
            }
        }

        if (filled($data['student_email'] ?? null)) {
            $data['student_email'] = mb_strtolower($data['student_email']);
        }

        if (($data['primary_contact_type'] ?? null) === 'Otro') {
            foreach (['reference_first_name', 'reference_last_name', 'reference_relationship'] as $field) {
                $data[$field] = Str::of($data[$field])->squish()->lower()->title()->toString();
            }
        } else {
            $data = [
                ...$data,
                'reference_first_name' => null,
                'reference_last_name' => null,
                'reference_relationship' => null,
                'reference_phone' => null,
            ];
        }

        $application->update([...$data, 'current_step' => 4]);

        return $application->refresh();
    }

    public function confirm(RectorateApplication $application): Student
    {
        return DB::transaction(function () use ($application): Student {
            $application = RectorateApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($application->student_id !== null && $application->status === 'completed') {
                return $application->student()->withTrashed()->firstOrFail();
            }

            if ($application->program_id === null || $application->plan_id === null || blank($application->student_identity_document)) {
                throw new \LogicException('La inscripción todavía no tiene programa, plan o datos del estudiante.');
            }

            $studentData = [
                'campus_id' => $application->campus_id,
                'account_number' => $application->account_number,
                'first_name' => $application->student_first_name,
                'paternal_surname' => $application->student_paternal_surname,
                'maternal_surname' => $application->student_maternal_surname,
                'birth_date' => $application->student_birth_date,
                'email' => $application->student_email,
                'phone' => $application->student_phone,
                'gender' => $application->student_gender,
                'is_active' => true,
            ];
            $student = Student::withTrashed()->withoutGlobalScope('company')
                ->where('company_id', $application->company_id)
                ->where('identity_document', $application->student_identity_document)
                ->first();

            if ($student) {
                if ($student->trashed()) {
                    $student->restore();
                }
                $student->update($studentData);
            } else {
                $student = Student::withoutGlobalScope('company')->create([
                    ...$studentData,
                    'company_id' => $application->company_id,
                    'identity_document' => $application->student_identity_document,
                ]);
            }

            $application->update(['student_id' => $student->id, 'status' => 'completed', 'current_step' => 4]);
            $this->contracts->createForApplication($application->refresh());

            return $student->refresh();
        });
    }
}
