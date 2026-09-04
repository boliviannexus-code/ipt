@extends('layouts.admin')
@section('title', 'Centralizador de notas')
@section('page-title', 'Centralizador de notas')
@section('page-subtitle', $module->name.' · '.$module->program->title)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="{{ route('teacher.tracking.index') }}"><i class="ti ti-arrow-left me-1"></i>Volver a seguimiento</a>
    <div class="d-flex gap-2"><span class="badge bg-primary-lt text-primary">{{ $module->classSessions->count() }} clases</span>@if($scheme)<span class="badge bg-azure-lt text-azure">Configuración v{{ $scheme->version }}</span>@endif</div>
</div>
@if(!$scheme || $skills->isEmpty())
    <div class="alert alert-info"><i class="ti ti-info-circle me-2"></i>Este módulo todavía no tiene ponderaciones configuradas.</div>
@endif
@php($componentAverageColumns = $displayComponents->filter(fn ($gradingComponent) => $gradingComponent->skills->count() > 1)->count())
<x-ui.table-card title="Notas acumuladas">
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0 tracking-grade-table">
        <thead>
            <tr>
                <th class="tracking-student-cell" rowspan="2" scope="col">Estudiante</th>
                @foreach($displayComponents as $gradingComponent)
                    <th class="text-center tracking-group tracking-group-tone-{{ $loop->index % 4 }}" colspan="{{ $gradingComponent->skills->count() + ($gradingComponent->skills->count() > 1 ? 1 : 0) }}" scope="colgroup">
                        <span class="d-block">{{ $gradingComponent->name }}</span>
                        <small>{{ $gradingComponent->frequency === \App\Enums\GradingFrequency::Daily ? 'Diaria' : 'Única' }} · {{ number_format((float) $gradingComponent->weight, 2, ',', '.') }}%</small>
                    </th>
                @endforeach
                <th class="text-center tracking-final-cell" rowspan="2" scope="col">Nota<br>acumulada</th>
            </tr>
            <tr>
                @foreach($displayComponents as $gradingComponent)
                    @php($hasMultipleSkills = $gradingComponent->skills->count() > 1)
                    @foreach($gradingComponent->skills as $skill)
                        <th class="text-center tracking-skill tracking-group-tone-{{ $loop->parent->index % 4 }} {{ $loop->first ? 'is-group-start' : '' }} {{ $loop->last && !$hasMultipleSkills ? 'is-group-end' : '' }} {{ !$hasMultipleSkills ? 'tracking-component-terminal' : '' }}" scope="col">{{ $skill->name }}</th>
                    @endforeach
                    @if($hasMultipleSkills)<th class="text-center tracking-component-terminal tracking-group-tone-{{ $loop->index % 4 }}" scope="col">Promedio</th>@endif
                @endforeach
            </tr>
        </thead>
        <tbody>
        @forelse($students as $row)
            <tr>
                <td class="tracking-student-cell"><strong>{{ trim("{$row['student']->first_name} {$row['student']->paternal_surname} {$row['student']->maternal_surname}") }}</strong><small class="d-block text-secondary">{{ $row['student']->identity_document }}</small></td>
                @foreach($displayComponents as $gradingComponent)
                    @php($tone = $loop->index % 4)
                    @php($hasMultipleSkills = $gradingComponent->skills->count() > 1)
                    @foreach($gradingComponent->skills as $skill)
                        @php($score = $row['scores']->get($skill->id, ['average' => null, 'count' => 0]))
                        <td class="text-center tracking-score tracking-group-tone-{{ $tone }} {{ $loop->first ? 'is-group-start' : '' }} {{ $loop->last && !$hasMultipleSkills ? 'is-group-end' : '' }} {{ !$hasMultipleSkills ? 'tracking-component-terminal' : '' }}">@if($score['average'] !== null)<strong>{{ number_format($score['average'], 2, ',', '.') }}</strong>@if($gradingComponent->frequency === \App\Enums\GradingFrequency::Daily)<small class="d-block text-secondary">×{{ $score['count'] }}</small>@endif @else<span class="text-secondary">—</span>@endif</td>
                    @endforeach
                    @if($hasMultipleSkills)
                        @php($componentAverage = $row['component_averages']->get($gradingComponent->id))
                        <td class="text-center tracking-component-terminal tracking-group-tone-{{ $tone }}">@if($componentAverage !== null)<strong>{{ number_format($componentAverage, 2, ',', '.') }}</strong>@else<span>—</span>@endif</td>
                    @endif
                @endforeach
                <td class="text-center tracking-final-cell">@if($row['overall_score'] !== null)@php($isApproved = $row['overall_score'] >= (float) ($scheme?->passing_score ?? 51))<span class="badge tracking-final-score {{ $isApproved ? 'text-bg-success' : 'text-bg-danger' }}"><strong>{{ number_format($row['overall_score'], 2, ',', '.') }}</strong><small>{{ $isApproved ? 'Aprobado' : 'Reprobado' }}</small></span>@else<span class="text-secondary">—</span>@endif</td>
            </tr>
        @empty
            <x-ui.empty-row :colspan="2 + $skills->count() + $componentAverageColumns" message="Este módulo no tiene estudiantes asignados." />
        @endforelse
        </tbody>
    </table>
    </div>
    <x-slot:footer><small class="text-secondary">La nota acumulada combina ponderaciones diarias y únicas según su peso configurado. Las habilidades pendientes aportan cero hasta ser evaluadas.</small></x-slot:footer>
</x-ui.table-card>
@endsection
