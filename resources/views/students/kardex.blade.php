@extends('layouts.admin')

@section('title', 'Kardex académico | '.config('app.name'))
@section('page-title', 'Kardex académico')
@section('page-subtitle', ($student->account_number ? 'Cuenta '.$student->account_number.' · ' : '').trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}"))

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
                <div class="col-md-6 col-xl-3"><div class="text-secondary small">Cuenta / Sede</div><div class="fw-semibold">{{ $student->account_number ?: 'Sin número' }}</div><div class="text-secondary">{{ $student->campus?->name ?? 'Sin sede' }}</div></div>
                <div class="col-md-6 col-xl-3"><div class="text-secondary small">Nacimiento / Género</div><div class="fw-semibold">{{ $student->birth_date?->format('d/m/Y') ?? 'No registrado' }}</div><div class="text-secondary">{{ $student->gender ?: 'No registrado' }}</div></div>
                <div class="col-md-6 col-xl-3"><div class="text-secondary small">Contacto</div><div class="fw-semibold text-break">{{ $student->email ?: 'Sin correo' }}</div><div class="text-secondary">{{ $student->phone ?: 'Sin teléfono' }}</div></div>
            </div>
        </div>
    </div>

    <x-ui.table-card title="Trayectoria académica">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Programa / Nivel</th><th>Módulo</th><th>Modalidad y periodo</th><th>Docente</th><th>Asistencia</th><th>Resultado</th></tr></thead>
            <tbody>
                @forelse($academicRows as $row)
                    @php($module = $row['module'])
                    <tr>
                        <td><span class="d-block fw-semibold">{{ $module->program->title }}</span><small class="text-secondary">{{ $module->level->name }}</small></td>
                        <td>{{ $module->name }}</td>
                        <td><span class="d-block">{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</span><small class="text-secondary">{{ $module->start_date->format('d/m/Y') }} – {{ $module->end_date->format('d/m/Y') }}</small></td>
                        <td>{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</td>
                        <td><span class="d-block">{{ $row['attendance']['present'] }} presentes · {{ $row['attendance']['late'] }} tardanzas</span><small class="text-secondary">{{ $row['attendance']['absent'] }} ausencias · {{ $row['attendance']['excused'] }} justificadas · {{ $row['attendance']['total'] }} clases</small></td>
                        <td>@if($row['result'])<span class="badge {{ $row['result']->status === 'approved' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $resultLabels[$row['result']->status] ?? $row['result']->status }}</span>@else<span class="badge text-bg-secondary">En curso</span>@endif</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="6" message="El estudiante aún no tiene módulos asignados." />
                @endforelse
            </tbody>
        </table>
    </x-ui.table-card>

    <div class="mt-3">
        <x-ui.table-card title="Inscripciones y planes">
            <table class="table table-hover align-middle mb-0"><thead><tr><th>Programa</th><th>Plan</th><th>Contrato</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
                @forelse($student->contracts->sortByDesc('confirmed_at') as $contract)<tr><td>{{ $contract->program->title }}</td><td>{{ $contract->plan->name }}</td><td>#{{ $contract->contract_number }}</td><td><span class="badge {{ $contract->status === 'enrolled' ? 'text-bg-success' : ($contract->status === 'cancelled' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ['enrolled'=>'Inscrito','pre_enrolled'=>'Preinscrito','cancelled'=>'Cancelado'][$contract->status] ?? $contract->status }}</span></td><td>{{ $contract->confirmed_at->format('d/m/Y') }}</td></tr>@empty<x-ui.empty-row colspan="5" message="No existen contratos para este estudiante." />@endforelse
            </tbody></table>
        </x-ui.table-card>
    </div>
@endsection
