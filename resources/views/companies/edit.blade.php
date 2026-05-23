@extends('layouts.admin')

@section('title', 'Editar empresa | Inventario POS')
@section('page-title', 'Editar empresa')
@section('content')
    <x-ui.form-panel title="Datos de empresa">
        @include('companies.partials.edit-form')
    </x-ui.form-panel>
@endsection
