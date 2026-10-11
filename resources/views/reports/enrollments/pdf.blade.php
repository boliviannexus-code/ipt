@php
    $selectedCampus = $campuses->firstWhere('id', (int)($filters['campus_id'] ?? 0))?->name ?? 'Todas';
    $selectedProgram = $programs->firstWhere('id', (int)($filters['program_id'] ?? 0))?->title ?? 'Todos';
    $selectedPlan = $plans->firstWhere('id', (int)($filters['plan_id'] ?? 0))?->name ?? 'Todos';
    $selectedExecutive = $salesExecutives->firstWhere('id', (int)($filters['sales_executive_id'] ?? 0))?->full_name ?? 'Todos';
    $selectedOrigin = $commercialOrigins->firstWhere('id', (int)($filters['commercial_origin_id'] ?? 0))?->name ?? 'Todos';
    $selectedStatus = ['completed'=>'Completada','draft'=>'En proceso'][$filters['status'] ?? ''] ?? 'Todos';
@endphp
<table cellpadding="3" cellspacing="0" width="100%"><tr><td style="font-size:16px;font-weight:bold;color:#183153;">{{ $company?->name ?? config('app.name') }}</td><td align="right" style="font-size:14px;font-weight:bold;">REPORTE DE MATRÍCULAS</td></tr><tr><td>Periodo: {{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</td><td align="right">Emitido: {{ now()->format('d/m/Y H:i') }}</td></tr></table>
<hr>
<table cellpadding="3" width="100%" style="font-size:8px;background-color:#eef2f7;"><tr><td><b>Sede:</b> {{ $selectedCampus }}</td><td><b>Programa:</b> {{ $selectedProgram }}</td><td><b>Plan:</b> {{ $selectedPlan }}</td></tr><tr><td><b>Ejecutivo:</b> {{ $selectedExecutive }}</td><td><b>Origen:</b> {{ $selectedOrigin }}</td><td><b>Estado:</b> {{ $selectedStatus }}</td></tr></table>
<br>
<table cellpadding="4" width="100%" style="font-size:8px;"><tr><td><b>Total:</b> {{ $summary['total'] }}</td><td><b>Completadas:</b> {{ $summary['completed'] }}</td><td><b>En proceso:</b> {{ $summary['draft'] }}</td><td><b>Cargos:</b> Bs {{ number_format($summary['charged'],2,',','.') }}</td><td><b>Recaudado:</b> Bs {{ number_format($summary['collected'],2,',','.') }}</td><td><b>Saldo:</b> Bs {{ number_format($summary['balance'],2,',','.') }}</td></tr></table>
<table border="1" cellpadding="3" cellspacing="0" width="100%" style="font-size:6.5px;"><thead><tr style="background-color:#183153;color:#ffffff;font-weight:bold;"><th width="7%">Fecha</th><th width="10%">Matrícula / Sede</th><th width="15%">Estudiante / CI</th><th width="15%">Programa / Plan</th><th width="13%">Ejecutivo</th><th width="10%">Origen</th><th width="9%">Cargos</th><th width="9%">Recaudado</th><th width="7%">Saldo</th><th width="5%">Estado</th></tr></thead><tbody>
@forelse($applications as $application)
    @php($charged = (float)($application->contract?->charges->sum('amount') ?? 0)) @php($collected = (float)($application->contract?->charges->sum('paid_amount') ?? 0))
    <tr><td>{{ $application->created_at->format('d/m/Y') }}</td><td>{{ $application->account_number ?: 'Pendiente' }}<br>{{ $application->campus?->name ?? 'Sin sede' }}</td><td>{{ trim("{$application->student_first_name} {$application->student_paternal_surname} {$application->student_maternal_surname}") ?: trim("{$application->first_name} {$application->paternal_surname}") }}<br>CI {{ $application->student_identity_document ?: $application->identity_document }}</td><td>{{ $application->program?->title ?? 'Pendiente' }}<br>{{ $application->plan?->name }}</td><td>{{ $application->salesExecutive?->full_name ?? 'Pendiente' }}</td><td>{{ $application->commercialOrigin?->name ?? 'Pendiente' }}</td><td align="right">{{ number_format($charged,2,',','.') }}</td><td align="right">{{ number_format($collected,2,',','.') }}</td><td align="right">{{ number_format($charged-$collected,2,',','.') }}</td><td>{{ $application->status==='completed' ? 'Completa' : 'Proceso' }}</td></tr>
@empty
    <tr><td colspan="10" align="center">No existen matrículas para los filtros seleccionados.</td></tr>
@endforelse
</tbody></table>
