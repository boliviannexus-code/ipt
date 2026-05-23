@extends('layouts.admin')

@section('title', 'Nueva empresa | Inventario POS')
@section('page-title', 'Nueva empresa')
@section('content')
    <x-ui.form-panel title="Datos de empresa">
        @include('companies.partials.create-form')
    </x-ui.form-panel>
@endsection
