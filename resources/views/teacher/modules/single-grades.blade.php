@extends('layouts.admin')
@section('title', 'Ponderaciones únicas')
@section('page-title', 'Ponderaciones únicas')
@section('page-subtitle', $module->name.' · '.$module->program->title)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="{{ route('teacher.modules.index') }}"><i class="ti ti-arrow-left me-1"></i>Volver a mis módulos</a>
    @if($scheme)<span class="badge bg-azure-lt text-azure">Configuración v{{ $scheme->version }}</span>@endif
</div>

@if(!$scheme || $components->isEmpty())
    <div class="alert alert-info" role="alert"><i class="ti ti-info-circle me-2"></i>Este programa no tiene ponderaciones únicas configuradas.</div>
@elseif($module->studentAssignments->isEmpty())
    <div class="alert alert-warning" role="alert"><i class="ti ti-users-minus me-2"></i>El módulo no tiene estudiantes asignados.</div>
@else
    <form method="POST" action="{{ route('teacher.modules.single-grades.update', $module) }}">
        @csrf @method('PUT')
        <div class="vstack gap-3">
            @foreach($components as $gradingComponent)
                <x-ui.card>
                    <div class="card-header">
                        <div>
                            <h2 class="card-title mb-1">{{ $gradingComponent->name }}</h2>
                            <div class="text-secondary small">{{ $gradingComponent->skills->count() === 1 ? 'Calificación única' : 'Calificación por habilidades' }} · {{ $gradingComponent->scoring_method === \App\Enums\GradingScoringMethod::Simple ? 'Simple (0 o 1)' : 'Porcentaje (0 a 100)' }}</div>
                        </div>
                        <span class="badge bg-primary-lt text-primary fs-6">{{ number_format((float) $gradingComponent->weight, 2, ',', '.') }}%</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover mb-0">
                            <thead><tr><th style="min-width: 14rem">Estudiante</th>@foreach($gradingComponent->skills as $skill)<th class="text-center" style="min-width: 10rem">{{ $skill->name }}@if($gradingComponent->skills->count() > 1)<small class="d-block text-secondary fw-normal">{{ number_format((float) $skill->weight, 2, ',', '.') }}%</small>@endif</th>@endforeach</tr></thead>
                            <tbody>
                            @foreach($module->studentAssignments as $assignment)
                                @php($studentName = trim("{$assignment->student->first_name} {$assignment->student->paternal_surname} {$assignment->student->maternal_surname}"))
                                <tr>
                                    <td><strong>{{ $studentName }}</strong><small class="d-block text-secondary">{{ $assignment->student->identity_document }}</small></td>
                                    @foreach($gradingComponent->skills as $skill)
                                        @php($savedScore = $gradesBySkill->get($skill->id)?->get($assignment->student_id))
                                        @php($field = "grades.{$skill->id}.{$assignment->student_id}")
                                        <td class="text-center">
                                            @if($gradingComponent->scoring_method === \App\Enums\GradingScoringMethod::Simple)
                                                @php($checked = (string) old($field, $savedScore !== null && (float) $savedScore === 100.0 ? '1' : '0') === '1')
                                                <div class="form-check form-switch justify-content-center"><input type="hidden" name="grades[{{ $skill->id }}][{{ $assignment->student_id }}]" value="0"><input class="form-check-input" id="single-grade-{{ $skill->id }}-{{ $assignment->student_id }}" name="grades[{{ $skill->id }}][{{ $assignment->student_id }}]" type="checkbox" role="switch" value="1" @checked($checked)><label class="form-check-label" for="single-grade-{{ $skill->id }}-{{ $assignment->student_id }}"><span class="visually-hidden">{{ $skill->name }} de {{ $studentName }}</span></label></div>
                                            @else
                                                <label class="visually-hidden" for="single-grade-{{ $skill->id }}-{{ $assignment->student_id }}">{{ $skill->name }} de {{ $studentName }}</label>
                                                <div class="input-group input-group-sm mx-auto" style="max-width: 8rem"><input class="form-control text-end @error($field) is-invalid @enderror" id="single-grade-{{ $skill->id }}-{{ $assignment->student_id }}" name="grades[{{ $skill->id }}][{{ $assignment->student_id }}]" type="number" min="0" max="100" step="0.01" inputmode="decimal" value="{{ old($field, $savedScore ?? 0) }}" required><span class="input-group-text">%</span></div>
                                                @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
        @error('grades')<div class="alert alert-danger mt-3" role="alert">{{ $message }}</div>@enderror
        <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Guardar ponderaciones únicas</button></div>
    </form>
@endif
@endsection
