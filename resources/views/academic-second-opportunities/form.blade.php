@extends('layouts.admin')
@section('title', 'Registrar 2T')
@section('page-title', 'Registrar nota 2T')
@section('page-subtitle', $result->student->first_name.' '.$result->student->paternal_surname.' · '.$result->module->name)
@section('content')
<div class="card"><div class="card-body">
    <p>{{ $result->module->program->title }} · {{ $result->module->level->name }}</p>
    <p>Nota original: <strong>{{ number_format((float) $result->final_score, 2, ',', '.') }}</strong> · Mínimo de aprobación: <strong>{{ number_format((float) $result->passing_score, 2, ',', '.') }}</strong></p>
    <div class="alert alert-info">Solo se permite un intento 2T. Revisa la nota antes de confirmar.</div>
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('academic.second-opportunities.preview', $result) }}">
        @csrf
        <div class="mb-3"><label class="form-label required" for="score">Nota 2T</label><input class="form-control @error('score') is-invalid @enderror" style="max-width: 16rem" type="number" id="score" name="score" min="0" max="100" step="0.01" value="{{ old('score', $data['score'] ?? '') }}" required>@error('score')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label required" for="reason">Motivo</label><textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="3" maxlength="2000" required>{{ old('reason', $data['reason'] ?? '') }}</textarea>@error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="{{ route('academic.second-opportunities.index') }}">Cancelar</a><button class="btn btn-primary" type="submit">Revisar resultado</button></div>
    </form>
</div></div>
@endsection
