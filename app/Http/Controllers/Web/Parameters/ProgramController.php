<?php

namespace App\Http\Controllers\Web\Parameters;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('parameters.programs.index', [
            'programs' => Program::query()->withCount(['plans', 'levels'])->orderBy('title')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('parameters.programs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) CompanyContext::id($request->user());
        $data = $this->validated($request, $companyId);
        Program::create([...$data, 'company_id' => $companyId]);

        return redirect()->route('parameters.programs.index')->with('success', 'Programa creado correctamente.');
    }

    public function edit(Program $program): View
    {
        return view('parameters.programs.edit', [
            'program' => $program,
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $data = $this->validated($request, (int) $program->company_id, $program);
        if (filled($program->enrollment_code) && $data['enrollment_code'] !== $program->enrollment_code && $program->applications()->whereNotNull('account_number')->exists()) {
            throw ValidationException::withMessages([
                'enrollment_code' => 'El código no puede cambiar porque el programa ya tiene matrículas generadas.',
            ]);
        }
        DB::transaction(function () use ($program, $data): void {
            $initializingCode = blank($program->enrollment_code);
            $program->update($data);
            if ($initializingCode) {
                $this->prefixExistingEnrollments($program, $data['enrollment_code']);
            }
        });

        return redirect()->route('parameters.programs.index')->with('success', 'Programa actualizado correctamente.');
    }

    private function validated(Request $request, int $companyId, ?Program $program = null): array
    {
        $request->merge(['enrollment_code' => strtoupper((string) $request->input('enrollment_code'))]);

        return $request->validate([
            'title' => ['required', 'string', 'max:180', Rule::unique('programs')->where('company_id', $companyId)->ignore($program)],
            'enrollment_code' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/', Rule::unique('programs')->where('company_id', $companyId)->ignore($program)],
            'duration_months' => ['required', 'integer', 'min:1', 'max:600'],
        ], [
            'enrollment_code.regex' => 'El código debe contener exactamente tres letras.',
        ]);
    }

    private function prefixExistingEnrollments(Program $program, string $code): void
    {
        $applications = $program->applications()->with(['contract', 'student'])->whereNotNull('account_number')->orderBy('account_number')->get();
        foreach ($applications as $application) {
            if (! ctype_digit($application->account_number)) {
                continue;
            }

            $matricula = $code.$application->account_number;
            $application->update(['account_number' => $matricula]);
            $application->contract?->update(['account_number' => $matricula]);
            $application->student?->update(['account_number' => $matricula]);

            DB::table('program_campus_enrollment_sequences')->upsert([[
                'program_id' => $program->id,
                'campus_id' => $application->campus_id,
                'last_number' => (int) substr($matricula, -4),
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['program_id', 'campus_id'], ['last_number', 'updated_at']);
        }
    }
}
