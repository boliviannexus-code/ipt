@extends('layouts.admin')

@section('title', 'Nueva sucursal | Inventario POS')
@section('page-title', 'Nueva sucursal')
@section('page-subtitle', 'Registro de punto de operacion')

@section('content')
    <x-ui.form-panel :action="route('branches.store')">
        @include('branches._form')
    </x-ui.form-panel>
@endsection
