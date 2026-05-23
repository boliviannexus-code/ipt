@extends('layouts.admin')
@section('title', 'Nueva presentacion | Inventario POS')
@section('page-title', 'Nueva presentacion')
@section('content')
    <x-ui.form-panel :action="route('product-presentations.store')">
        @include('product-presentations._form', ['productPresentation' => null])
    </x-ui.form-panel>
@endsection
