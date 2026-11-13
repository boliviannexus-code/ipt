@extends('layouts.admin')
@section('title', '2T')
@section('page-title', '2T · Segunda oportunidad')
@section('page-subtitle', 'Una evaluación adicional para estudiantes reprobados de cursos cerrados.')
@section('content')
<form method="GET" class="card card-body mb-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label" for="search">Estudiante o CI</label><input class="form-control" id="search" name="search" value="{{ request('search') }}"></div>
        <div class="col-md-3"><label class="form-label" for="program">Programa</label><select class="form-select" id="program" name="program_id"><option value="">Todos</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->title }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="module">Módulo</label><select class="form-select" id="module" name="module_id"><option value="">Todos</option>@foreach($modules as $module)<option value="{{ $module->id }}" @selected(request('module_id') == $module->id)>{{ $module->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Buscar</button> <a class="btn btn-link" href="{{ route('academic.second-opportunities.index') }}">Limpiar</a></div>
    </div>
</form>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<x-ui.table-card title="Estudiantes habilitados">
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Estudiante</th><th>Programa / módulo</th><th>Nota original</th><th>Mínimo</th><th>Acción</th></tr></thead>
        <tbody>@forelse($candidates as $result)<tr>
            <td>{{ $result->student->first_name }} {{ $result->student->paternal_surname }} {{ $result->student->maternal_surname }}<small class="d-block text-secondary">{{ $result->student->identity_document }}</small></td>
            <td>{{ $result->module->program->title }}<small class="d-block text-secondary">{{ $result->module->name }} · {{ $result->module->level->name }}</small></td>
            <td>{{ number_format((float) $result->final_score, 2, ',', '.') }}</td><td>{{ number_format((float) $result->passing_score, 2, ',', '.') }}</td>
            <td><a class="btn btn-outline-primary" href="{{ route('academic.second-opportunities.create', $result) }}">Registrar 2T</a></td>
        </tr>@empty<x-ui.empty-row colspan="5" message="No hay estudiantes habilitados para 2T con estos filtros." />@endforelse</tbody>
    </table></div>
    <x-slot:footer>{{ $candidates->links() }}<small class="text-secondary">Solo reprobados con matrícula vigente, sin intento 2T ni asignaciones activas o historial posterior.</small></x-slot:footer>
</x-ui.table-card>
<div class="mt-4">
<x-ui.table-card title="Historial 2T">
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Estudiante / módulo</th><th>Original</th><th>2T</th><th>Resultado</th><th>Motivo</th><th>Registrado</th></tr></thead><tbody>
    @forelse($history as $attempt)<tr>
        <td>{{ $attempt->result->student?->first_name }} {{ $attempt->result->student?->paternal_surname }}<small class="d-block text-secondary">{{ $attempt->result->module->program->title }} · {{ $attempt->result->module->name }}</small></td>
        <td>{{ number_format((float) $attempt->original_score, 2, ',', '.') }}</td><td>{{ number_format((float) $attempt->score, 2, ',', '.') }}</td>
        <td>{{ $attempt->status === 'approved' ? 'Aprobado' : 'Reprobado' }}</td><td class="text-break">{{ $attempt->reason }}</td><td>{{ $attempt->recorded_at->format('d/m/Y H:i') }}<small class="d-block text-secondary">{{ $attempt->recordedBy->name }}</small></td>
    </tr>@empty<x-ui.empty-row colspan="6" message="Todavía no se registraron notas 2T." />@endforelse
    </tbody></table></div><x-slot:footer>{{ $history->links() }}</x-slot:footer>
</x-ui.table-card>
</div>
@endsection
