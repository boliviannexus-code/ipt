@extends('layouts.admin')
@section('title', 'Confirmar 2T')
@section('page-title', 'Confirmar evaluación 2T')
@section('page-subtitle', $result->student->first_name.' '.$result->student->paternal_surname.' · '.$result->module->name)
@section('content')
@php($approved = (float) $data['score'] >= (float) $result->passing_score)
<div class="card"><div class="card-body">
    <dl class="row"><dt class="col-sm-4">Nota original</dt><dd class="col-sm-8">{{ number_format((float) $result->final_score, 2, ',', '.') }}</dd><dt class="col-sm-4">Nota 2T</dt><dd class="col-sm-8">{{ number_format((float) $data['score'], 2, ',', '.') }}</dd><dt class="col-sm-4">Resultado</dt><dd class="col-sm-8"><strong>{{ $approved ? 'Aprobado' : 'Reprobado' }}</strong></dd><dt class="col-sm-4">Motivo</dt><dd class="col-sm-8 text-break">{{ $data['reason'] }}</dd></dl>
    <div class="alert alert-info">@if(!$approved)Continuará en {{ $result->module->level->name }} y quedará disponible para asignación a otro módulo del mismo nivel.@elseif($nextLevel)Avanzará a {{ $nextLevel->name }} y quedará disponible para asignación manual.@elseSe evaluará la finalización del programa según los niveles aprobados y pagos pendientes.@endif No se generarán cargos ni se asignará un módulo automáticamente.</div>
    <p>Este intento no podrá modificarse ni repetirse.</p>
    <form method="POST" action="{{ route('academic.second-opportunities.store', $result) }}">
        @csrf<input type="hidden" name="score" value="{{ $data['score'] }}"><input type="hidden" name="reason" value="{{ $data['reason'] }}">
        <div class="d-flex gap-2"><button class="btn btn-outline-secondary" type="submit" formaction="{{ route('academic.second-opportunities.preview', $result) }}" name="back" value="1">Volver a editar</button><button class="btn btn-primary" type="submit">Confirmar y registrar 2T</button></div>
    </form>
</div></div>
@endsection
