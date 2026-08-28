@extends('layouts.admin')
@section('title', 'Nuevo programa')
@section('page-title', 'Nuevo programa')
@section('page-subtitle', 'Datos y planes del programa')
@section('content')<x-ui.form-panel :action="route('parameters.programs.store')">@include('parameters.programs.partials.fields')<div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('parameters.programs.index') }}">Cancelar</a><button class="btn btn-primary" type="submit">Guardar programa</button></div></x-ui.form-panel>@endsection
