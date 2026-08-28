<?php

namespace App\Http\Controllers\Web\Parameters;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramLevelController extends Controller
{
    public function index(Program $program): View
    {
        return view('parameters.programs.levels', [
            'program' => $program,
            'levels' => $program->levels()->get(),
        ]);
    }

    public function store(Request $request, Program $program): RedirectResponse
    {
        $request->merge(['name' => $this->normalizeName((string) $request->input('name'))]);
        $data = $this->validated($request, $program);
        $program->levels()->create([
            ...$data,
            'company_id' => $program->company_id,
            'name' => $this->normalizeName($data['name']),
            'position' => $data['position'] ?? ((int) $program->levels()->max('position') + 1),
            'is_active' => true,
        ]);

        return back()->with('success', 'Nivel agregado correctamente.');
    }

    public function update(Request $request, Program $program, ProgramLevel $level): RedirectResponse
    {
        $this->ensureBelongsToProgram($program, $level);
        $request->merge(['name' => $this->normalizeName((string) $request->input('name'))]);
        $data = $this->validated($request, $program, $level);
        $level->update([
            ...$data,
            'name' => $this->normalizeName($data['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Nivel actualizado correctamente.');
    }

    public function destroy(Program $program, ProgramLevel $level): RedirectResponse
    {
        $this->ensureBelongsToProgram($program, $level);
        $level->delete();

        return back()->with('success', 'Nivel eliminado correctamente.');
    }

    private function validated(Request $request, Program $program, ?ProgramLevel $level = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('program_levels')->where('program_id', $program->id)->ignore($level),
            ],
            'position' => [
                $level ? 'required' : 'nullable', 'integer', 'min:1', 'max:999',
                Rule::unique('program_levels')->where('program_id', $program->id)->ignore($level),
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.unique' => 'Este programa ya tiene un nivel con ese nombre.',
            'position.unique' => 'Este programa ya tiene un nivel en esa posición.',
        ]);
    }

    private function ensureBelongsToProgram(Program $program, ProgramLevel $level): void
    {
        abort_unless((int) $level->program_id === (int) $program->id, 404);
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)->squish()->lower()->title()->toString();
    }
}
