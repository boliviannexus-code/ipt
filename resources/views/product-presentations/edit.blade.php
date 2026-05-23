@extends('layouts.admin')
@section('title', 'Editar presentacion | Inventario POS')
@section('page-title', 'Editar presentacion')
@section('content')
    <x-ui.form-panel :action="route('product-presentations.update', $productPresentation)" method="PUT">
        @include('product-presentations._form')
    </x-ui.form-panel>
@endsection
