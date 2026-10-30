@extends('layouts.admin')
@section('title', 'Historial de exámenes de nivel')
@section('page-title', 'Historial de exámenes de nivel')
@section('page-subtitle', 'Exámenes registrados en la empresa activa')
@section('content')
<x-ui.table-card title="Exámenes de nivel">
    <x-slot:actions><a class="btn btn-outline-primary btn-sm" href="{{ route('placement-exams.index') }}">Estudiantes disponibles</a></x-slot:actions>
    <table class="table table-hover align-middle">
        <thead><tr><th>Fecha / Estudiante</th><th>Programa / Contrato</th><th>Nivel destino</th><th>Nota</th><th>Docente</th><th>Reconocimientos / Observación</th><th>Registrado por</th></tr></thead>
        <tbody>
        @forelse($exams as $exam)
            <tr>
                <td>{{ $exam->examined_at->format('d/m/Y H:i') }}<a class="d-block" href="{{ route('students.kardex.show', $exam->student_id) }}">{{ trim($exam->student->first_name.' '.$exam->student->paternal_surname.' '.$exam->student->maternal_surname) }}</a><small class="text-secondary">CI {{ $exam->student->identity_document }}</small></td>
                <td>{{ $exam->program->title }}<small class="d-block text-secondary">Contrato #{{ $exam->contract->contract_number }}</small></td>
                <td>{{ $exam->previous_level_name }} → {{ $exam->target_level_name }}</td>
                <td>{{ number_format($exam->score, 2, ',', '.') }}</td>
                <td>{{ $exam->teacher?->full_name ?? '—' }}</td>
                <td><div>{{ $exam->levels->pluck('level_name')->join(', ') ?: 'Niveles anteriores ya aprobados' }}</div><div class="text-secondary text-break">{{ $exam->observation }}</div></td>
                <td>{{ $exam->recorder?->name ?? '—' }}</td>
            </tr>
        @empty
            <x-ui.empty-row colspan="7" message="No hay exámenes de nivel registrados." />
        @endforelse
        </tbody>
    </table>
    <x-slot:footer>{{ $exams->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
