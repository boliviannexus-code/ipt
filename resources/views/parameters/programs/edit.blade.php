@extends('layouts.admin')
@section('title', 'Editar programa')
@section('page-title', 'Editar programa')
@section('page-subtitle', 'Datos y planes vinculados')
@section('content')<x-ui.form-panel :action="route('parameters.programs.update', $program)" method="PUT">@include('parameters.programs.partials.fields')<div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('parameters.programs.index') }}">Cancelar</a><button class="btn btn-primary" type="submit">Guardar cambios</button></div></x-ui.form-panel>@endsection
