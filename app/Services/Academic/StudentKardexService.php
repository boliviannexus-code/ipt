<?php

namespace App\Services\Academic;

use App\Models\Student;

class StudentKardexService
{
    public function build(Student $student): array
    {
        $student->loadMissing([
            'company',
            'campus',
            'contracts.program',
            'contracts.plan',
            'applications.customer',
            'moduleAssignments.module.program',
            'moduleAssignments.module.level',
            'moduleAssignments.module.currentTeacherAssignment.personnel',
            'moduleResults',
            'attendances.session',
        ]);

        $results = $student->moduleResults->keyBy('academic_module_id');
        $attendances = $student->attendances->groupBy(fn ($attendance) => $attendance->session?->academic_module_id);
        $academicRows = $student->moduleAssignments
            ->sortBy(fn ($assignment) => $assignment->module?->start_date?->timestamp ?? PHP_INT_MAX)
            ->map(function ($assignment) use ($results, $attendances): array {
                $module = $assignment->module;
                $moduleAttendances = $attendances->get($module->id, collect());

                return [
                    'assignment' => $assignment,
                    'module' => $module,
                    'result' => $results->get($module->id),
                    'attendance' => [
                        'total' => $moduleAttendances->count(),
                        'present' => $moduleAttendances->where('status', 'present')->count(),
                        'late' => $moduleAttendances->where('status', 'late')->count(),
                        'absent' => $moduleAttendances->where('status', 'absent')->count(),
                        'excused' => $moduleAttendances->where('status', 'excused')->count(),
                    ],
                ];
            })
            ->values();

        return compact('student', 'academicRows');
    }
}
