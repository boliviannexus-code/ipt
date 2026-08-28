@extends('layouts.admin')
@section('title', 'Nuevo origen comercial | '.config('app.name'))
@section('page-title', 'Nuevo origen comercial')
@section('page-subtitle', 'Registro de origen comercial')
@section('content')
    <x-ui.form-panel :action="route('parameters.commercial-origins.store')">
        @include('parameters.commercial-origins.partials.fields')
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Guardar origen</button><a class="btn btn-outline-secondary" href="{{ route('parameters.commercial-origins.index') }}">Cancelar</a></div>
    </x-ui.form-panel>
@endsection
