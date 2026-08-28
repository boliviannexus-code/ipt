@extends('layouts.admin')
@section('title', 'Resultados del módulo')
@section('page-title', 'Resultados del módulo')
@section('page-subtitle', $module->name.' · Finalizó '.$module->end_date->format('d/m/Y'))
@section('content')
<form method="POST" action="{{ route('teacher.modules.results.update', $module) }}">
    @csrf @method('PUT')
    <x-ui.table-card title="Cierre académico">
        <x-slot:actions><span class="badge text-bg-azure">{{ $module->program->title }} · {{ $module->level->name }}</span></x-slot:actions>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Estudiante</th><th>CI</th><th style="width: 13rem">Resultado</th><th style="min-width: 15rem">Detalle del cargo</th><th>Cargo</th></tr></thead>
            <tbody>
            @forelse($module->studentAssignments as $assignment)
                @php($savedResult = $resultsByStudent->get($assignment->student_id))
                @php($currentResult = old('results.'.$assignment->student_id, $savedResult?->status ?? 'approved'))
                @php($currentConcept = old('concepts.'.$assignment->student_id, $savedResult?->charge?->concept ?? 'Mensualidad'))
                <tr>
                    <td class="fw-semibold">{{ trim("{$assignment->student->first_name} {$assignment->student->paternal_surname} {$assignment->student->maternal_surname}") }}</td>
                    <td>{{ $assignment->student->identity_document }}</td>
                    <td><select class="form-select" name="results[{{ $assignment->student_id }}]" required><option value="approved" @selected($currentResult === 'approved')>Aprobado</option><option value="failed" @selected($currentResult === 'failed')>Reprobado</option></select></td>
                    <td><input class="form-control" name="concepts[{{ $assignment->student_id }}]" value="{{ $currentConcept }}" maxlength="160" aria-label="Detalle del cargo de {{ $assignment->student->first_name }}" required></td>
                    <td>@if($savedResult?->charge)<span class="badge text-bg-success">Bs {{ number_format((float) $savedResult->charge->amount, 2, ',', '.') }} · {{ $savedResult->charge->period->format('m/Y') }}</span>@else<span class="text-secondary">Se generará al guardar</span>@endif</td>
                </tr>
            @empty
                <x-ui.empty-row colspan="5" message="Este módulo no tiene estudiantes asignados." />
            @endforelse
            </tbody>
        </table>
        <x-slot:footer><div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('teacher.modules.index') }}">Volver</a><button class="btn btn-primary" type="submit" @disabled($module->studentAssignments->isEmpty())><i class="ti ti-checks me-1"></i>Guardar cierre y generar cargos</button></div></x-slot:footer>
    </x-ui.table-card>
</form>
@error('results')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror
@endsection
