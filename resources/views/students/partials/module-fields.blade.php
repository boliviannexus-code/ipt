<div class="border rounded-3 p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-5"><span class="text-secondary small d-block">Estudiante</span><strong>{{ trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}") }}</strong></div>
        <div class="col-md-3"><span class="text-secondary small d-block">CI</span><strong>{{ $student->identity_document }}</strong></div>
        <div class="col-md-4"><span class="text-secondary small d-block">Programa</span><strong>{{ $student->contracts()->where('status', 'enrolled')->with('program')->get()->pluck('program.title')->unique()->join(', ') }}</strong></div>
    </div>
</div>
<div class="mb-3">
    <label class="form-label required" for="student-module">Módulo</label>
    <select class="form-select @error('academic_module_id') is-invalid @enderror" id="student-module" name="academic_module_id" required>
        <option value="">Seleccionar módulo</option>
        @foreach($modules as $module)
            <option value="{{ $module->id }}" @selected((int) old('academic_module_id') === $module->id)>{{ $module->name }} · {{ $module->program->title }} · {{ $module->level->name }} · {{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</option>
        @endforeach
    </select>
    @error('academic_module_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($modules->isEmpty())<div class="form-text text-danger">No hay módulos vigentes disponibles para los programas del estudiante.</div>@endif
</div>
@if($student->moduleAssignments->isNotEmpty())
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Módulos asignados</th><th>Programa</th><th>Nivel</th><th>Asignado</th></tr></thead><tbody>@foreach($student->moduleAssignments as $assignment)<tr><td>{{ $assignment->module->name }}</td><td>{{ $assignment->module->program->title }}</td><td>{{ $assignment->module->level->name }}</td><td>{{ $assignment->assigned_at->format('d/m/Y') }}</td></tr>@endforeach</tbody></table></div>
@endif
