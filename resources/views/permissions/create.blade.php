@extends('layouts.admin')
@section('title', 'Nuevo permiso | '.config('app.name', 'Base Admin'))
@section('page-title', 'Nuevo permiso')
@section('content')
    <x-ui.form-panel :action="route('permissions.store')">
        @include('permissions.partials.form')
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Guardar</button><a class="btn btn-outline-secondary" href="{{ route('permissions.index') }}">Cancelar</a></div>
    </x-ui.form-panel>
@endsection
