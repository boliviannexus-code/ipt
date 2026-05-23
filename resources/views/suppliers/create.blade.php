@extends('layouts.admin')

@section('title', 'Nuevo proveedor | Inventario POS')
@section('page-title', 'Nuevo proveedor')
@section('page-subtitle', 'Registro administrativo de proveedores')

@section('content')
    <x-ui.form-panel :action="route('suppliers.store')">
        @include('suppliers._form', ['supplier' => null])
    </x-ui.form-panel>
@endsection
