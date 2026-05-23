@extends('layouts.admin')

@section('title', 'Nueva unidad | Inventario POS')
@section('page-title', 'Nueva unidad de medida')
@section('page-subtitle', 'Registro de unidad para productos')

@section('content')
    <x-ui.form-panel :action="route('measurement-units.store')">
        @include('measurement-units._form', ['measurementUnit' => null])
    </x-ui.form-panel>
@endsection
