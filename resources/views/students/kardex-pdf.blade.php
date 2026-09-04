@php
    $studentName = trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}");
    $resultLabels = ['approved' => 'APROBADO', 'failed' => 'REPROBADO'];
@endphp
<table cellpadding="3" cellspacing="0" width="100%">
    <tr><td width="65%" style="font-size:16px;font-weight:bold;color:#183153;">{{ $student->company?->name ?? config('app.name') }}</td><td width="35%" align="right" style="font-size:13px;font-weight:bold;">KARDEX ACADÉMICO</td></tr>
    <tr><td width="65%">{{ $student->campus?->name ?? 'Sin sede' }}</td><td width="35%" align="right">Emitido: {{ now()->format('d/m/Y H:i') }}</td></tr>
</table>
<hr>
<table cellpadding="4" cellspacing="0" width="100%" style="font-size:9px;">
    <tr><td width="18%"><b>Estudiante:</b></td><td width="42%">{{ $studentName }}</td><td width="18%"><b>N.º de matrícula:</b></td><td width="22%">{{ $student->account_number ?: '—' }}</td></tr>
    <tr><td width="18%"><b>CI:</b></td><td width="42%">{{ $student->identity_document }}</td><td width="18%"><b>Nacimiento:</b></td><td width="22%">{{ $student->birth_date?->format('d/m/Y') ?? '—' }}</td></tr>
    <tr><td width="18%"><b>Correo:</b></td><td width="42%">{{ $student->email ?: '—' }}</td><td width="18%"><b>Teléfono:</b></td><td width="22%">{{ $student->phone ?: '—' }}</td></tr>
</table>
<h3 style="color:#183153;">Datos del titular</h3>
@if(isset($holder) && $holder)
    @php($holderName = collect([$holder->first_name, $holder->paternal_surname, $holder->maternal_surname])->filter()->join(' '))
    <table cellpadding="4" cellspacing="0" width="100%" style="font-size:9px;">
        <tr><td width="18%"><b>Nombre:</b></td><td width="42%">{{ $holderName }}</td><td width="18%"><b>CI:</b></td><td width="22%">{{ $holder->identity_document ?: '—' }}</td></tr>
        <tr><td width="18%"><b>Parentesco:</b></td><td width="42%">{{ $holder->student_relationship ?: '—' }}</td><td width="18%"><b>Teléfono:</b></td><td width="22%">{{ $holder->phone ?: '—' }}</td></tr>
        <tr><td width="18%"><b>Correo:</b></td><td width="82%" colspan="3">{{ $holder->email ?: '—' }}</td></tr>
    </table>
@else
    <p style="font-size:9px;color:#666666;">No existen datos de titular vinculados a este estudiante.</p>
@endif
<h3 style="color:#183153;">Trayectoria académica</h3>
<table border="1" cellpadding="4" cellspacing="0" width="100%" style="font-size:7.5px;">
    <thead><tr style="background-color:#183153;color:#ffffff;font-weight:bold;"><th width="24%">Programa / Nivel</th><th width="19%">Módulo</th><th width="17%">Periodo</th><th width="20%">Docente</th><th width="10%">Nota final</th><th width="10%">Resultado</th></tr></thead>
    <tbody>
    @forelse($academicRows as $row)
        @php($module = $row['module'])
        <tr><td width="24%">{{ $module->program->title }}<br>{{ $module->level->name }}</td><td width="19%">{{ $module->name }}<br>{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</td><td width="17%">{{ $module->start_date->format('d/m/Y') }}<br>{{ $module->end_date->format('d/m/Y') }}</td><td width="20%">{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</td><td width="10%" align="center"><b>{{ $row['grading']['overall_score'] === null ? '—' : number_format($row['grading']['overall_score'], 2, ',', '.') }}</b>@if($row['grading']['passing_score'] !== null)<br>Mín. {{ number_format($row['grading']['passing_score'], 2, ',', '.') }}@endif</td><td width="10%" align="center">{{ $row['result'] ? ($resultLabels[$row['result']->status] ?? strtoupper($row['result']->status)) : 'EN CURSO' }}</td></tr>
    @empty
        <tr><td colspan="6" align="center">El estudiante aún no tiene módulos asignados.</td></tr>
    @endforelse
    </tbody>
</table>
<h3 style="color:#183153;">Inscripciones y planes</h3>
<table border="1" cellpadding="4" cellspacing="0" width="100%" style="font-size:8px;">
    <thead><tr style="background-color:#e9eef5;font-weight:bold;"><th width="32%">Programa</th><th width="25%">Plan</th><th width="18%">Matrícula</th><th width="25%">Estado / Fecha</th></tr></thead>
    <tbody>@forelse($student->contracts->sortByDesc('confirmed_at') as $contract)<tr><td width="32%">{{ $contract->program->title }}</td><td width="25%">{{ $contract->plan->name }}</td><td width="18%">{{ $contract->account_number }}</td><td width="25%">{{ strtoupper(str_replace('_', ' ', $contract->status)) }}<br>{{ $contract->confirmed_at->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="4" align="center">No existen matrículas registradas.</td></tr>@endforelse</tbody>
</table>
<p style="font-size:7px;color:#666666;">Documento generado por {{ config('app.name') }}.</p>
