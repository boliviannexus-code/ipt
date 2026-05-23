@extends('layouts.admin')

@section('title', 'Editar punto de venta | Inventario POS')
@section('page-title', 'Editar punto de venta')

@section('content')
    <x-ui.form-panel :action="route('point-of-sales.update', $pointOfSale)" method="PUT">
        @include('point-of-sales._form')
    </x-ui.form-panel>
@endsection
