@extends('layouts.admin')

@section('title', 'Detalle diario del Kardex | '.config('app.name'))
@section('page-title', 'Detalle diario del Kardex')
@section('page-subtitle', trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}").' · '.$module->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('students.kardex.show', $student) }}"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Volver al Kardex</a>
        <span class="badge bg-primary-lt text-primary"><i class="ti ti-calendar me-1" aria-hidden="true"></i>{{ $dailyRows->count() }} {{ $dailyRows->count() === 1 ? 'día registrado' : 'días registrados' }}</span>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-secondary small">Programa / Nivel</div><div class="fw-semibold">{{ $module->program->title }}</div><div class="text-secondary">{{ $module->level->name }}</div></div>
                <div class="col-md-4"><div class="text-secondary small">Módulo</div><div class="fw-semibold">{{ $module->name }}</div><div class="text-secondary">{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</div></div>
                <div class="col-md-4"><div class="text-secondary small">Periodo</div><div class="fw-semibold">{{ $module->start_date->format('d/m/Y') }} – {{ $module->end_date->format('d/m/Y') }}</div><div class="text-secondary">{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</div></div>
            </div>
        </div>
    </div>

    <x-ui.table-card title="Registro por día">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Evaluaciones del día</th><th>Observación</th></tr></thead>
                <tbody>
                    @forelse($dailyRows as $row)
                        <tr>
                            <td class="text-nowrap"><strong>{{ $row['session']->class_date->translatedFormat('l') }}</strong><small class="d-block text-secondary">{{ $row['session']->class_date->format('d/m/Y') }}</small></td>
                            <td>
                                @forelse($row['evaluations'] as $evaluation)
                                    <div class="d-flex justify-content-between gap-3 py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <span><strong>{{ $evaluation['skill'] }}</strong><small class="d-block text-secondary">{{ $evaluation['component'] }}</small></span>
                                        <span class="fw-semibold text-nowrap">{{ number_format($evaluation['score'], 2, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <span class="text-secondary">Sin evaluaciones</span>
                                @endforelse
                            </td>
                            <td>{{ $row['observation']?->observation ?? 'Sin observación' }}</td>
                        </tr>
                    @empty
                        <x-ui.empty-row colspan="3" message="Todavía no existen registros diarios para este módulo." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.table-card>
@endsection
