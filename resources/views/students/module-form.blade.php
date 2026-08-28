@extends('layouts.admin')
@section('title', 'Asignar módulo')
@section('page-title', 'Asignar estudiante a módulo')
@section('page-subtitle', 'Asignación académica del estudiante')
@section('content')
<x-ui.form-panel :action="route('students.modules.store', $student)">
    @include('students.partials.module-fields')
    <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" @disabled($modules->isEmpty())>Asignar módulo</button><a class="btn btn-outline-secondary" href="{{ route('students.index') }}">Cancelar</a></div>
</x-ui.form-panel>
@endsection
