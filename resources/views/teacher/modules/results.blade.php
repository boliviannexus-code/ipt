@extends('layouts.admin')
@section('title', 'Resultados del módulo')
@section('page-title', 'Resultados del módulo')
@section('page-subtitle', $module->name.' · Finalizó '.$module->end_date->format('d/m/Y'))
@section('content')
@php($isClosed = $module->closed_at !== null)
@php($scheme = $grading['scheme'] ?? null)
<form method="POST" action="{{ module_workspace_route('teacher.modules.results.update', $module) }}" data-confirm-action data-confirm-title="¿Cerrar este curso?" data-confirm-text="Los resultados se calcularán con las ponderaciones registradas y el cierre no podrá modificarse." data-confirm-button="Sí, cerrar curso">
    @csrf @method('PUT')
    <x-ui.table-card title="{{ $isClosed ? 'Cierre académico registrado' : 'Vista previa del cierre académico' }}">
        <x-slot:actions><div class="d-flex gap-2 align-items-center"><span class="badge text-bg-azure">{{ $module->program->title }} · {{ $module->level->name }}</span>@if($scheme)<span class="badge text-bg-secondary">Nota mínima: {{ number_format((float) $scheme->passing_score, 2, ',', '.') }}</span>@endif</div></x-slot:actions>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Estudiante</th><th>CI</th><th>Ponderaciones</th><th class="text-end">Nota final</th><th>Resultado</th></tr></thead>
            <tbody>
            @forelse($module->studentAssignments as $assignment)
                @php($savedResult = $resultsByStudent->get($assignment->student_id))
                @php($preview = $grading ? $grading['students']->first(fn($row) => $row['student']->id === $assignment->student_id) : null)
                @php($score = $savedResult?->final_score ?? ($preview['overall_score'] ?? null))
                @php($passingScore = (float) ($savedResult?->passing_score ?? $scheme?->passing_score ?? 51))
                @php($status = $savedResult?->status ?? ($score !== null ? ((float) $score >= $passingScore ? 'approved' : 'failed') : null))
                @php($details = $savedResult?->grading_details ?? ($grading ? $grading['displayComponents']->map(fn($component) => ['name' => $component->name, 'weight' => (float) $component->weight, 'average' => $preview['component_averages']->get($component->id)])->all() : []))
                <tr>
                    <td class="fw-semibold">{{ trim("{$assignment->student->first_name} {$assignment->student->paternal_surname} {$assignment->student->maternal_surname}") }}</td>
                    <td>{{ $assignment->student->identity_document }}</td>
                    <td>@foreach($details as $detail)<span class="d-block small"><strong>{{ $detail['name'] }}</strong> ({{ number_format((float) $detail['weight'], 2, ',', '.') }}%): {{ $detail['average'] === null ? 'Sin nota' : number_format((float) $detail['average'], 2, ',', '.') }}</span>@endforeach</td>
                    <td class="text-end fw-bold">{{ $score === null ? '—' : number_format((float) $score, 2, ',', '.') }}</td>
                    <td>@if($status === 'approved')<span class="badge text-bg-success">Aprobado</span>@elseif($status === 'failed')<span class="badge text-bg-danger">Reprobado</span>@else<span class="badge text-bg-warning">Sin calificaciones</span>@endif</td>
                </tr>
            @empty
                <x-ui.empty-row colspan="5" message="Este módulo no tiene estudiantes asignados." />
            @endforelse
            </tbody>
        </table>
        <x-slot:footer><div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ module_workspace_route($module->closed_at || $resultsByStudent->isNotEmpty() ? 'teacher.modules.history' : 'teacher.modules.index') }}">Volver</a>@unless($isClosed)<button class="btn btn-primary" type="submit" @disabled($module->studentAssignments->isEmpty() || !$scheme)><i class="ti ti-lock-check me-1"></i>Cerrar curso</button>@endunless</div></x-slot:footer>
    </x-ui.table-card>
</form>
@error('results')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror
@unless($isClosed)
    @if(!$scheme)<div class="alert alert-warning mt-3">El programa no tiene ponderaciones activas. No es posible cerrar el curso.</div>@endif
@endunless
@endsection
