@extends('layouts.admin')

@section('title', 'Nueva categoria | Inventario POS')
@section('page-title', 'Nueva categoria')
@section('page-subtitle', 'Registro de clasificaciones para productos')

@section('content')
    <x-ui.form-panel :action="route('categories.store')">
        @include('categories._form')
    </x-ui.form-panel>
@endsection
