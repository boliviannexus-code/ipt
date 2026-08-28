@php
    $studentName = trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}");
    $resultLabels = ['approved' => 'APROBADO', 'failed' => 'REPROBADO'];
@endphp
<table cellpadding="3" cellspacing="0" width="100%">
    <tr><td style="font-size:16px;font-weight:bold;color:#183153;">{{ $student->company?->name ?? config('app.name') }}</td><td align="right" style="font-size:13px;font-weight:bold;">KARDEX ACADÉMICO</td></tr>
    <tr><td>{{ $student->campus?->name ?? 'Sin sede' }}</td><td align="right">Emitido: {{ now()->format('d/m/Y H:i') }}</td></tr>
</table>
<hr>
<table cellpadding="4" cellspacing="0" width="100%" style="font-size:9px;">
    <tr><td width="18%"><b>Estudiante:</b></td><td width="42%">{{ $studentName }}</td><td width="18%"><b>N.º de cuenta:</b></td><td width="22%">{{ $student->account_number ?: '—' }}</td></tr>
    <tr><td><b>CI:</b></td><td>{{ $student->identity_document }}</td><td><b>Nacimiento:</b></td><td>{{ $student->birth_date?->format('d/m/Y') ?? '—' }}</td></tr>
    <tr><td><b>Correo:</b></td><td>{{ $student->email ?: '—' }}</td><td><b>Teléfono:</b></td><td>{{ $student->phone ?: '—' }}</td></tr>
</table>
<h3 style="color:#183153;">Trayectoria académica</h3>
<table border="1" cellpadding="4" cellspacing="0" width="100%" style="font-size:7.5px;">
    <thead><tr style="background-color:#183153;color:#ffffff;font-weight:bold;"><th width="19%">Programa / Nivel</th><th width="17%">Módulo</th><th width="15%">Periodo</th><th width="16%">Docente</th><th width="23%">Asistencia</th><th width="10%">Resultado</th></tr></thead>
    <tbody>
    @forelse($academicRows as $row)
        @php($module = $row['module'])
        <tr><td>{{ $module->program->title }}<br>{{ $module->level->name }}</td><td>{{ $module->name }}<br>{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</td><td>{{ $module->start_date->format('d/m/Y') }}<br>{{ $module->end_date->format('d/m/Y') }}</td><td>{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</td><td>P: {{ $row['attendance']['present'] }} · T: {{ $row['attendance']['late'] }} · A: {{ $row['attendance']['absent'] }} · J: {{ $row['attendance']['excused'] }}<br>Total: {{ $row['attendance']['total'] }}</td><td>{{ $row['result'] ? ($resultLabels[$row['result']->status] ?? strtoupper($row['result']->status)) : 'EN CURSO' }}</td></tr>
    @empty
        <tr><td colspan="6" align="center">El estudiante aún no tiene módulos asignados.</td></tr>
    @endforelse
    </tbody>
</table>
<h3 style="color:#183153;">Inscripciones y planes</h3>
<table border="1" cellpadding="4" cellspacing="0" width="100%" style="font-size:8px;">
    <thead><tr style="background-color:#e9eef5;font-weight:bold;"><th width="32%">Programa</th><th width="25%">Plan</th><th width="18%">Contrato</th><th width="25%">Estado / Fecha</th></tr></thead>
    <tbody>@forelse($student->contracts->sortByDesc('confirmed_at') as $contract)<tr><td>{{ $contract->program->title }}</td><td>{{ $contract->plan->name }}</td><td>#{{ $contract->contract_number }}</td><td>{{ strtoupper(str_replace('_', ' ', $contract->status)) }}<br>{{ $contract->confirmed_at->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="4" align="center">No existen contratos registrados.</td></tr>@endforelse</tbody>
</table>
<p style="font-size:7px;color:#666666;">P: presente · T: tardanza · A: ausencia · J: justificada. Documento generado por {{ config('app.name') }}.</p>
