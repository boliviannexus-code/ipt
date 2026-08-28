@extends('layouts.admin')
@section('title', 'Editar sede | '.config('app.name'))
@section('page-title', 'Editar sede')
@section('page-subtitle', 'Actualización de sede')
@section('content')<x-ui.form-panel :action="route('campuses.update', $campus)" method="PUT">@include('campuses.partials.fields')<div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Guardar cambios</button><a class="btn btn-outline-secondary" href="{{ route('campuses.index') }}">Cancelar</a></div></x-ui.form-panel>@endsection
