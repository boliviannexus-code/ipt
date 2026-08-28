<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\AcademicModuleTeacherAssignment;
use App\Models\Personnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicModuleTeacherController extends Controller
{
    public function edit(Request $request, AcademicModule $module): View
    {
        $module->load(['program', 'level', 'teacherAssignments.personnel.position']);
        $personnel = Personnel::query()
            ->with('position')
            ->where('company_id', $module->company_id)
            ->where('is_active', true)
            ->whereHas('position', fn ($query) => $query->where('is_active', true)->where('is_academic', true))
            ->orderBy('first_name')
            ->orderBy('paternal_surname')
            ->get();

        return view($request->ajax() ? 'academic-modules.partials.teacher-form' : 'academic-modules.teacher-form', compact('module', 'personnel'));
    }

    public function update(Request $request, AcademicModule $module): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'personnel_id' => ['required', 'integer', Rule::exists('personnel', 'id')->where('company_id', $module->company_id)->where('is_active', true)],
        ]);

        $teacher = Personnel::query()
            ->whereKey($data['personnel_id'])
            ->where('company_id', $module->company_id)
            ->where('is_active', true)
            ->whereHas('position', fn ($query) => $query->where('is_active', true)->where('is_academic', true))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages(['personnel_id' => 'Seleccione personal activo con un cargo académico.']);
        }

        DB::transaction(function () use ($module, $teacher): void {
            $current = AcademicModuleTeacherAssignment::query()
                ->where('academic_module_id', $module->id)
                ->whereNull('unassigned_at')
                ->lockForUpdate()
                ->first();

            if ($current?->personnel_id === $teacher->id) {
                return;
            }

            $current?->update(['unassigned_at' => now()]);
            AcademicModuleTeacherAssignment::create([
                'company_id' => $module->company_id,
                'academic_module_id' => $module->id,
                'personnel_id' => $teacher->id,
                'assigned_at' => now(),
            ]);
        });

        $message = 'Docente asignado correctamente.';
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'refresh_url' => route('academic.modules.index')]);
        }

        return redirect()->route('academic.modules.index')->with('success', $message);
    }
}
