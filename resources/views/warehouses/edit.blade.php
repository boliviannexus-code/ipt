@extends('layouts.admin')

@section('title', 'Editar almacen | Inventario POS')
@section('page-title', 'Editar almacen')
@section('page-subtitle', 'Actualizacion de datos de almacen')

@section('content')
    <x-ui.form-panel :action="route('warehouses.update', $warehouse)" method="PUT">
        @include('warehouses._form')
    </x-ui.form-panel>
@endsection
