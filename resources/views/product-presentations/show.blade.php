@extends('layouts.admin')
@section('title', 'Detalle presentacion | Inventario POS')
@section('page-title', 'Detalle presentacion')
@section('content')
    <x-ui.card title="Informacion de presentacion">
        <div class="card-body">@include('product-presentations.partials.show')</div>
        <x-slot:footer><a class="btn btn-outline-secondary" href="{{ route('product-presentations.index') }}">Volver</a></x-slot:footer>
    </x-ui.card>
@endsection
