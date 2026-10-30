<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Services\Academic\ModulePromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ModulePromotionController extends Controller
{
    public function __construct(private readonly ModulePromotionService $promotions) {}

    public function index(): View
    {
        return view('academic-promotions.index', [
            'modules' => AcademicModule::query()
                ->whereNotNull('closed_at')
                ->with(['program.levels', 'level', 'currentTeacherAssignment.personnel', 'closedBy'])
                ->withCount(['studentAssignments', 'studentResults'])
                ->orderByDesc('closed_at')
                ->paginate(20),
        ]);
    }

    public function create(AcademicModule $module): View
    {
        $context = $this->promotions->context($module);

        return view('academic-promotions.create', compact('module', 'context'));
    }

    public function students(Request $request, AcademicModule $module): View
    {
        $dates = $this->validatedDates($request, $module);
        $context = $this->promotions->context($module);

        return view('academic-promotions.students', compact('module', 'context', 'dates'));
    }

    public function store(Request $request, AcademicModule $module): RedirectResponse
    {
        $dates = $this->validatedDates($request, $module);
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $newModule = $this->promotions->promote($request->user(), $module, $dates, $data['student_ids']);

        return redirect()->route('academic.modules.index')->with('success', "Módulo {$newModule->name} creado con estudiantes y cargos registrados correctamente.");
    }

    private function validatedDates(Request $request, AcademicModule $module): array
    {
        abort_if($module->closed_at === null, 409, 'Solo se pueden promover módulos cerrados.');

        return $request->validate([
            'start_date' => ['required', 'date', 'after:'.$module->end_date->toDateString()],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'start_date.after' => 'La nueva fecha de inicio debe ser posterior al módulo finalizado.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);
    }
}
