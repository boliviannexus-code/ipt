@extends('layouts.admin')

@section('title', 'Kardex académico | '.config('app.name'))
@section('page-title', 'Kardex académico')
@section('page-subtitle', ($student->account_number ? 'Matrícula '.$student->account_number.' · ' : '').trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}"))

@section('content')
    @php
        $studentName = trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}");
        $resultLabels = ['approved' => 'Aprobado', 'failed' => 'Reprobado'];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('students.index') }}"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Volver a estudiantes</a>
        <a class="btn btn-primary" href="{{ route('students.kardex.pdf', $student) }}" target="_blank" rel="noopener"><i class="ti ti-printer me-1" aria-hidden="true"></i>Imprimir kardex</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 col-xl-3"><div class="text-secondary small">Estudiante</div><div class="fw-semibold">{{ $studentName }}</div><div class="text-secondary">CI {{ $student->identity_document }}</div></div>
                <div class="col-md-6 col-xl-3"><div class="text-secondary small">Matrícula / Sede</div><div class="fw-semibold">{{ $student->account_number ?: 'Sin número' }}</div><div class="text-secondary">{{ $student->campus?->name ?? 'Sin sede' }}</div></div>
                <div class="col-md-6 col-xl-3"><div class="text-secondary small">Nacimiento / Género</div><div class="fw-semibold">{{ $student->birth_date?->format('d/m/Y') ?? 'No registrado' }}</div><div class="text-secondary">{{ $student->gender ?: 'No registrado' }}</div></div>
                <div class="col-md-6 col-xl-3"><div class="text-secondary small">Contacto</div><div class="fw-semibold text-break">{{ $student->email ?: 'Sin correo' }}</div><div class="text-secondary">{{ $student->phone ?: 'Sin teléfono' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title"><i class="ti ti-user-shield me-2 text-primary" aria-hidden="true"></i>Datos del titular</h3></div>
        <div class="card-body">
            @if($holder)
                @php($holderName = collect([$holder->first_name, $holder->paternal_surname, $holder->maternal_surname])->filter()->join(' '))
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3"><div class="text-secondary small">Nombre completo</div><div class="fw-semibold">{{ $holderName }}</div></div>
                    <div class="col-md-6 col-xl-3"><div class="text-secondary small">CI / Parentesco</div><div class="fw-semibold">{{ $holder->identity_document ?: 'No registrado' }}</div><div class="text-secondary">{{ $holder->student_relationship ?: 'No registrado' }}</div></div>
                    <div class="col-md-6 col-xl-3"><div class="text-secondary small">Teléfono</div><div class="fw-semibold">{{ $holder->phone ?: 'No registrado' }}</div></div>
                    <div class="col-md-6 col-xl-3"><div class="text-secondary small">Correo electrónico</div><div class="fw-semibold text-break">{{ $holder->email ?: 'No registrado' }}</div></div>
                </div>
            @else
                <div class="text-secondary"><i class="ti ti-info-circle me-1" aria-hidden="true"></i>No existen datos de titular vinculados a este estudiante.</div>
            @endif
        </div>
    </div>

    <x-ui.table-card title="Trayectoria académica">
        <table class="table table-hover align-middle mb-0 kardex-table">
            <thead><tr><th>Programa / Nivel</th><th>Módulo</th><th>Modalidad y periodo</th><th>Docente</th><th class="text-center">Nota final</th><th>Resultado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse($academicRows as $row)
                    @php($module = $row['module'])
                    <tr>
                        <td><span class="d-block fw-semibold">{{ $module->program->title }}</span><small class="text-secondary">{{ $module->level->name }}</small></td>
                        <td>{{ $module->name }}</td>
                        <td><span class="d-block">{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</span><small class="text-secondary">{{ $module->start_date->format('d/m/Y') }} – {{ $module->end_date->format('d/m/Y') }}</small></td>
                        <td>{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</td>
                        <td class="text-center">@if($row['grading']['overall_score'] !== null)@php($isPassing = $row['grading']['overall_score'] >= $row['grading']['passing_score'])<span class="badge fs-5 {{ $isPassing ? 'text-bg-success' : 'text-bg-danger' }}">{{ number_format($row['grading']['overall_score'], 2, ',', '.') }}</span><small class="d-block text-secondary mt-1">Mín. {{ number_format($row['grading']['passing_score'], 2, ',', '.') }}</small>@else<span class="text-secondary">—</span>@endif</td>
                        <td>@if($row['result'])<span class="badge {{ $row['result']->status === 'approved' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $resultLabels[$row['result']->status] ?? $row['result']->status }}</span>@else<span class="badge text-bg-secondary">En curso</span>@endif</td>
                        <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('students.kardex.details', [$student, $module]) }}"><i class="ti ti-calendar-stats me-1" aria-hidden="true"></i>Ver detalles</a></td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="7" message="El estudiante aún no tiene módulos asignados." />
                @endforelse
            </tbody>
        </table>
    </x-ui.table-card>

    <div class="mt-3">
        <x-ui.table-card title="Inscripciones y planes">
            <table class="table table-hover align-middle mb-0"><thead><tr><th>Programa</th><th>Plan</th><th>Matrícula</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
                @forelse($student->contracts->sortByDesc('confirmed_at') as $contract)<tr><td>{{ $contract->program->title }}</td><td>{{ $contract->plan->name }}</td><td>{{ $contract->account_number }}</td><td><span class="badge {{ $contract->status === 'enrolled' ? 'text-bg-success' : ($contract->status === 'cancelled' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ['enrolled'=>'Inscrito','pre_enrolled'=>'Preinscrito','cancelled'=>'Cancelado'][$contract->status] ?? $contract->status }}</span></td><td>{{ $contract->confirmed_at->format('d/m/Y') }}</td></tr>@empty<x-ui.empty-row colspan="5" message="No existen matrículas para este estudiante." />@endforelse
            </tbody></table>
        </x-ui.table-card>
    </div>
@endsection
