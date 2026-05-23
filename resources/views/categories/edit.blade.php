@extends('layouts.admin')

@section('title', 'Editar categoria | Inventario POS')
@section('page-title', 'Editar categoria')
@section('page-subtitle', 'Actualizacion de datos del catalogo')

@section('content')
    <x-ui.form-panel :action="route('categories.update', $category)" method="PUT">
        @include('categories._form')
    </x-ui.form-panel>
@endsection
