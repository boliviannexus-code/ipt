@extends('layouts.admin')

@section('title', 'Editar unidad | Inventario POS')
@section('page-title', 'Editar unidad de medida')
@section('page-subtitle', 'Actualizacion de unidad para productos')

@section('content')
    <x-ui.form-panel :action="route('measurement-units.update', $measurementUnit)" method="PUT">
        @include('measurement-units._form')
    </x-ui.form-panel>
@endsection
