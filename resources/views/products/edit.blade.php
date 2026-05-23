@extends('layouts.admin')

@section('title', 'Editar producto | Inventario POS')
@section('page-title', 'Editar producto')
@section('page-subtitle', 'Actualizacion del catalogo comercial')

@section('content')
    <x-ui.form-panel :action="route('products.update', $product)" method="PUT" enctype="multipart/form-data">
        @include('products._form')
    </x-ui.form-panel>
@endsection
