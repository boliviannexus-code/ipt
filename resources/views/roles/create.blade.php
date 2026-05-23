@extends('layouts.admin')
@section('title', 'Nuevo rol | Inventario POS')
@section('page-title', 'Nuevo rol')
@section('content')
    <x-ui.form-panel :action="route('roles.store')">
        @include('roles.partials.form')
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Guardar</button><a class="btn btn-outline-secondary" href="{{ route('roles.index') }}">Cancelar</a></div>
    </x-ui.form-panel>
@endsection
