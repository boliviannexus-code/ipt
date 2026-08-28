@extends('layouts.admin')
@section('title', 'Asignar docente')
@section('page-title', 'Asignar docente')
@section('page-subtitle', 'Asignación independiente del módulo académico')
@section('content')
<x-ui.form-panel :action="route('academic.modules.teacher.update', $module)" method="PUT">
    @include('academic-modules.partials.teacher-fields')
    <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" @disabled($personnel->isEmpty())>Asignar docente</button><a class="btn btn-outline-secondary" href="{{ route('academic.modules.index') }}">Cancelar</a></div>
</x-ui.form-panel>
@endsection
