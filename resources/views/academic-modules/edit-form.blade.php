@extends('layouts.admin')
@section('title', 'Editar módulo') @section('page-title', 'Editar módulo') @section('page-subtitle', 'Configuración académica')
@section('content')<x-ui.form-panel :action="route('academic.modules.update', $module)" method="PUT">@include('academic-modules.partials.fields')<div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Guardar cambios</button><a class="btn btn-outline-secondary" href="{{ route('academic.modules.index') }}">Cancelar</a></div></x-ui.form-panel>@endsection
