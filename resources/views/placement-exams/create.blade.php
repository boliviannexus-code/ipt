@extends('layouts.admin')
@section('title', 'Registrar examen de nivel')
@section('page-title', 'Registrar examen de nivel')
@section('page-subtitle', trim($student->first_name.' '.$student->paternal_surname.' '.$student->maternal_surname).' · CI '.$student->identity_document)
@section('content')
<a class="btn btn-outline-secondary mb-3" href="{{ route('placement-exams.index') }}">Volver a estudiantes disponibles</a>
@if($hasModule)
    <div class="alert alert-warning">El estudiante ya tiene un módulo abierto. No se puede registrar el examen.</div>
@else
    <x-ui.card title="1. Seleccionar programa y nivel destino">
        <div class="card-body">
            <form method="GET" action="{{ route('placement-exams.create', $student) }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required" for="exam-contract">Contrato / Programa</label>
                    <select class="form-select" name="enrollment_contract_id" id="exam-contract" required>
                        <option value="">Seleccione un contrato</option>
                        @foreach($contracts as $option)<option value="{{ $option->id }}" @selected($contract?->id === $option->id)>{{ $option->program->title }} · Contrato #{{ $option->contract_number }} · {{ $option->account_number }}</option>@endforeach
                    </select>
                </div>
                @if($contract)
                    <div class="col-md-6">
                        <label class="form-label" for="exam-target">Nivel destino</label>
                        <select class="form-select" name="target_program_level_id" id="exam-target">
                            <option value="">Seleccione un nivel superior</option>
                            @foreach($levels as $level)<option value="{{ $level->id }}" @selected($target?->id === $level->id)>{{ $level->name }}</option>@endforeach
                        </select>
                        <div class="form-hint">Nivel actual: {{ $current?->name ?? 'Sin nivel configurado' }}. Si cambia de programa, vuelva a consultar sus niveles.</div>
                    </div>
                @endif
                <div class="col-12"><button class="btn btn-outline-primary" type="submit">{{ $contract ? 'Consultar niveles y vista previa' : 'Consultar niveles' }}</button></div>
            </form>
            @if($contract && $levels->isEmpty())<div class="alert alert-info mt-3 mb-0">Este programa no tiene niveles activos superiores disponibles.</div>@endif
        </div>
    </x-ui.card>
    @if($target)
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">2. Niveles anteriores a {{ $target->name }}</h3></div>
            <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nivel</th><th>Tratamiento</th></tr></thead><tbody>
                @foreach($preview as $row)<tr><td>{{ $row['level']->name }}</td><td>{{ $row['preserved'] ? 'Conserva aprobación o reconocimiento anterior' : 'Se reconocerá con la nota del examen' }}</td></tr>@endforeach
            </tbody></table></div>
        </div>
        <x-ui.card title="3. Datos del examen" class="mt-3">
            <div class="card-body">
                <p class="text-secondary">Al guardar, el nivel actual será {{ $target->name }} y el estudiante quedará pendiente de asignación a un grupo. En esta versión no se genera un cargo por el examen.</p>
                <form method="POST" action="{{ route('placement-exams.store', $student) }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="enrollment_contract_id" value="{{ $contract->id }}">
                    <input type="hidden" name="target_program_level_id" value="{{ $target->id }}">
                    <input type="hidden" name="expected_current_level_id" value="{{ $contract->current_program_level_id }}">
                    <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
                    <div class="col-md-4"><label class="form-label required" for="exam-score">Nota (0 a 100)</label><input class="form-control @error('score') is-invalid @enderror" type="number" name="score" id="exam-score" min="0" max="100" step="0.01" value="{{ old('score') }}" required><div class="invalid-feedback">@error('score'){{ $message }}@enderror</div></div>
                    <div class="col-md-8"><label class="form-label required" for="exam-teacher">Docente evaluador</label><select class="form-select @error('teacher_id') is-invalid @enderror" id="exam-teacher" name="teacher_id" required><option value="">Seleccione un docente</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((int) old('teacher_id') === $teacher->id)>{{ $teacher->full_name }}</option>@endforeach</select><div class="invalid-feedback">@error('teacher_id'){{ $message }}@enderror</div></div>
                    <div class="col-12"><label class="form-label required" for="exam-observation">Observación</label><textarea class="form-control @error('observation') is-invalid @enderror" name="observation" id="exam-observation" rows="3" maxlength="2000" required>{{ old('observation') }}</textarea><div class="form-hint">Hasta 2.000 caracteres.</div><div class="invalid-feedback">@error('observation'){{ $message }}@enderror</div></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Registrar examen y actualizar nivel</button></div>
                </form>
            </div>
        </x-ui.card>
    @endif
@endif
@endsection
