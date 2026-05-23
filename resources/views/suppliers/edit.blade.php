@extends('layouts.admin')

@section('title', 'Editar proveedor | Inventario POS')
@section('page-title', 'Editar proveedor')
@section('page-subtitle', 'Actualizacion de datos de proveedor')

@section('content')
    <x-ui.form-panel :action="route('suppliers.update', $supplier)" method="PUT">
        @include('suppliers._form')
    </x-ui.form-panel>
@endsection
