@extends('layouts.admin')
@section('title', 'Nueva sede | '.config('app.name'))
@section('page-title', 'Nueva sede')
@section('page-subtitle', 'Registro de sede')
@section('content')<x-ui.form-panel :action="route('campuses.store')">@include('campuses.partials.fields')<div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Guardar sede</button><a class="btn btn-outline-secondary" href="{{ route('campuses.index') }}">Cancelar</a></div></x-ui.form-panel>@endsection
