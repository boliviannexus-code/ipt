<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\RectorateApplication;
use App\Models\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class AcademicPersonalDataService
{
    public function update(RectorateApplication $application, array $data): void
    {
        DB::transaction(function () use ($application, $data): void {
            $application = RectorateApplication::query()->lockForUpdate()->findOrFail($application->id);
            $student = Student::query()->lockForUpdate()->findOrFail($application->student_id);
            $studentData = [];
            $snapshot = [];
            foreach (['identity_document', 'first_name', 'paternal_surname', 'maternal_surname', 'birth_date', 'email', 'phone', 'gender'] as $field) {
                $studentData[$field] = $data['student_'.$field] ?? null;
                $snapshot['student_'.$field] = $studentData[$field];
            }
            $student->update($studentData);
            RectorateApplication::query()->where('student_id', $student->id)->update($snapshot);
            $application->update(Arr::only($data, [
                'identity_document', 'first_name', 'paternal_surname', 'maternal_surname', 'birth_date', 'email', 'phone',
                'student_relationship', 'primary_contact_type', 'reference_first_name', 'reference_last_name', 'reference_relationship', 'reference_phone',
            ]));
        });
    }
}
