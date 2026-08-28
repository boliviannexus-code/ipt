<div class="border rounded-3 p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-4"><span class="text-secondary small d-block">Módulo</span><strong>{{ $module->name }}</strong></div>
        <div class="col-md-4"><span class="text-secondary small d-block">Programa</span><strong>{{ $module->program->title }}</strong></div>
        <div class="col-md-4"><span class="text-secondary small d-block">Nivel</span><strong>{{ $module->level->name }}</strong></div>
    </div>
</div>
<div class="mb-3">
    <label class="form-label required" for="module-teacher">Docente</label>
    <select class="form-select @error('personnel_id') is-invalid @enderror" id="module-teacher" name="personnel_id" required>
        <option value="">Seleccionar personal</option>
        @foreach($personnel as $person)
            <option value="{{ $person->id }}" @selected((int) old('personnel_id', $module->currentTeacherAssignment?->personnel_id) === $person->id)>{{ $person->full_name }} · {{ $person->position->name }}</option>
        @endforeach
    </select>
    @error('personnel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($personnel->isEmpty())<div class="form-text text-danger">No existe personal activo con un cargo marcado como académico.</div>@endif
</div>
@if($module->teacherAssignments->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Historial docente</th><th>Desde</th><th>Hasta</th></tr></thead>
            <tbody>@foreach($module->teacherAssignments as $assignment)<tr><td>{{ $assignment->personnel->full_name }}</td><td>{{ $assignment->assigned_at->format('d/m/Y H:i') }}</td><td>{{ $assignment->unassigned_at?->format('d/m/Y H:i') ?? 'Actual' }}</td></tr>@endforeach</tbody>
        </table>
    </div>
@endif
