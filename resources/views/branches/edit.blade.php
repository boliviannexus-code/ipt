@extends('layouts.admin')

@section('title', 'Editar sucursal | Inventario POS')
@section('page-title', 'Editar sucursal')
@section('page-subtitle', 'Actualizacion de datos de sucursal')

@section('content')
    <x-ui.form-panel :action="route('branches.update', $branch)" method="PUT">
        @include('branches._form')
    </x-ui.form-panel>
@endsection
