@extends('layouts.admin')

@section('title', 'Nuevo almacen | Inventario POS')
@section('page-title', 'Nuevo almacen')
@section('page-subtitle', 'Registro de almacen por sucursal')

@section('content')
    <x-ui.form-panel :action="route('warehouses.store')">
        @include('warehouses._form')
    </x-ui.form-panel>
@endsection
