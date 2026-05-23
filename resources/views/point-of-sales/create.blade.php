@extends('layouts.admin')

@section('title', 'Nuevo punto de venta | Inventario POS')
@section('page-title', 'Nuevo punto de venta')

@section('content')
    <x-ui.form-panel :action="route('point-of-sales.store')">
        @include('point-of-sales._form', ['pointOfSale' => null])
    </x-ui.form-panel>
@endsection
