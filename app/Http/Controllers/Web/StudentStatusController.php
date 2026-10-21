<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Enrollment\EnrollmentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentStatusController extends Controller
{
    public function edit(Request $request, Student $student): View
    {
        $contracts = $student->contracts()->with(['program', 'statusChanges.recorder'])->orderBy('id')->get();
        $changeable = $contracts->whereIn('status', EnrollmentStatusService::CHANGEABLE);
        $states = EnrollmentStatusService::TARGETS;
        $submissionToken = old('submission_token', (string) Str::uuid());

        return view($request->ajax() ? 'students.partials.status-form' : 'students.status-form', compact('student', 'contracts', 'changeable', 'states', 'submissionToken'));
    }

    public function update(Request $request, Student $student, EnrollmentStatusService $service): JsonResponse|RedirectResponse
    {
        $request->merge(['observation' => trim((string) $request->input('observation'))]);
        $data = $request->validate([
            'enrollment_contract_id' => ['required', 'integer'],
            'status' => ['required', Rule::in(array_keys(EnrollmentStatusService::TARGETS))],
            'observation' => ['required', 'string', 'max:2000'],
            'registered_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reactivation_date' => ['exclude_unless:status,suspended', 'required', 'date_format:Y-m-d', 'after:registered_on'],
            'submission_token' => ['required', 'uuid'],
        ], [
            'observation.required' => 'La observación es obligatoria.',
            'reactivation_date.required' => 'Indique la fecha prevista de reactivación para SUSPENDIDO.',
            'reactivation_date.after' => 'La reactivación debe ser posterior a la fecha de registro.',
        ]);
        $service->record($request->user(), $student, $data);
        $message = 'Cambio de estado registrado correctamente.';
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'refresh_url' => route('students.index')]);
        }

        return redirect()->route('students.index')->with('success', $message);
    }
}
