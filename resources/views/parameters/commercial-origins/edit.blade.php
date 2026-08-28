@extends('layouts.admin')
@section('title', 'Editar origen comercial | '.config('app.name'))
@section('page-title', 'Editar origen comercial')
@section('page-subtitle', 'Actualización del origen comercial')
@section('content')
    <x-ui.form-panel :action="route('parameters.commercial-origins.update', $commercialOrigin)" method="PUT">
        @include('parameters.commercial-origins.partials.fields')
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Guardar cambios</button><a class="btn btn-outline-secondary" href="{{ route('parameters.commercial-origins.index') }}">Cancelar</a></div>
    </x-ui.form-panel>
@endsection
