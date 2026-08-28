@extends('layouts.admin')
@section('title', 'Registrar asistencia')
@section('page-title', 'Registrar asistencia')
@section('page-subtitle', $module->name.' · '.$session->class_date->format('d/m/Y'))
@section('content')
<form method="POST" action="{{ route('teacher.modules.attendance.update', [$module, $session]) }}">
    @csrf @method('PUT')
    <x-ui.table-card title="Lista de estudiantes">
        <x-slot:actions><span class="badge text-bg-green"><i class="ti ti-player-play me-1"></i>Iniciada {{ $session->started_at->format('H:i') }}</span></x-slot:actions>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Estudiante</th><th>CI</th><th style="width: 15rem">Asistencia</th></tr></thead>
            <tbody>
            @forelse($module->studentAssignments as $assignment)
                @php($currentStatus = old('attendance.'.$assignment->student_id, $attendanceByStudent->get($assignment->student_id)?->status ?? 'present'))
                <tr><td class="fw-semibold">{{ trim("{$assignment->student->first_name} {$assignment->student->paternal_surname} {$assignment->student->maternal_surname}") }}</td><td>{{ $assignment->student->identity_document }}</td><td><select class="form-select" name="attendance[{{ $assignment->student_id }}]" aria-label="Asistencia de {{ $assignment->student->first_name }}" required>@foreach(['present'=>'Presente','absent'=>'Ausente','late'=>'Tardanza','excused'=>'Justificado'] as $value=>$label)<option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>@endforeach</select></td></tr>
            @empty
                <x-ui.empty-row colspan="3" message="Este módulo no tiene estudiantes asignados." />
            @endforelse
            </tbody>
        </table>
        <x-slot:footer><div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('teacher.modules.index') }}">Volver</a><button class="btn btn-primary" type="submit" @disabled($module->studentAssignments->isEmpty())><i class="ti ti-device-floppy me-1"></i>Guardar asistencia</button></div></x-slot:footer>
    </x-ui.table-card>
</form>
@error('attendance')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror
@endsection
