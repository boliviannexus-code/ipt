@extends('layouts.admin')

@section('title', 'Nuevo producto | Inventario POS')
@section('page-title', 'Nuevo producto')
@section('page-subtitle', 'Alta de productos para inventario y POS')

@section('content')
    <x-ui.form-panel :action="route('products.store')" enctype="multipart/form-data">
        @include('products._form')
    </x-ui.form-panel>
@endsection
