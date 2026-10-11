<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicModuleController extends Controller
{
    public function index(): View
    {
        return view('academic-modules.index');
    }

    public function create(Request $request): View
    {
        return $this->formView($request, null);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $companyId = (int) CompanyContext::id($request->user());
        $data = $this->validated($request, $companyId);
        $module = AcademicModule::create([...$data, 'company_id' => $companyId]);

        return $this->success($request, 'Módulo creado correctamente.', $module);
    }

    public function edit(Request $request, AcademicModule $module): View
    {
        return $this->formView($request, $module);
    }

    public function update(Request $request, AcademicModule $module): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request, (int) $module->company_id, $module);
        $module->update($data);

        return $this->success($request, 'Módulo actualizado correctamente.', $module);
    }

    public function destroy(Request $request, AcademicModule $module): JsonResponse|RedirectResponse
    {
        $module->delete();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Módulo eliminado correctamente.', 'refresh_url' => route('academic.modules.index')]);
        }

        return redirect()->route('academic.modules.index')->with('success', 'Módulo eliminado correctamente.');
    }

    private function formView(Request $request, ?AcademicModule $module): View
    {
        $data = [
            'module' => $module,
            'programs' => Program::query()->with(['levels' => fn ($query) => $query->where('is_active', true)])->orderBy('title')->get(),
        ];
        $prefix = $request->ajax() ? 'academic-modules.partials.' : 'academic-modules.';

        return view($prefix.($module ? 'edit-form' : 'create-form'), $data);
    }

    private function validated(Request $request, int $companyId, ?AcademicModule $module = null): array
    {
        $name = Str::of((string) $request->input('name'))->squish()->toString();

        if ($name === '' && $request->integer('program_level_id') > 0) {
            $levelName = ProgramLevel::query()
                ->where('company_id', $companyId)
                ->whereKey($request->integer('program_level_id'))
                ->where('is_active', true)
                ->value('name');

            if ($levelName) {
                $name = "Módulo {$levelName}";
            }
        }

        $request->merge(['name' => $name]);
        $data = $request->validate([
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where('company_id', $companyId)],
            'program_level_id' => ['required', 'integer', Rule::exists('program_levels', 'id')->where('company_id', $companyId)->where('is_active', true)],
            'name' => ['required', 'string', 'max:160', Rule::unique('academic_modules')->where(fn ($query) => $query->where('program_id', $request->integer('program_id'))->where('program_level_id', $request->integer('program_level_id')))->ignore($module)],
            'modality' => ['required', Rule::in(['virtual', 'presential'])],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'name.unique' => 'Ya existe un módulo con este nombre para el programa y nivel seleccionados.',
            'ends_at.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'end_date.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
        ]);

        $levelBelongs = ProgramLevel::query()->whereKey($data['program_level_id'])->where('program_id', $data['program_id'])->exists();
        if (! $levelBelongs) {
            throw ValidationException::withMessages(['program_level_id' => 'El nivel seleccionado no pertenece al programa.']);
        }

        return $data;
    }

    private function success(Request $request, string $message, AcademicModule $module): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => ['id' => $module->id], 'refresh_url' => route('academic.modules.index')]);
        }

        return redirect()->route('academic.modules.index')->with('success', $message);
    }
}
