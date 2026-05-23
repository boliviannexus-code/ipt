@extends('layouts.admin')

@section('title', 'Detalle almacen | Inventario POS')
@section('page-title', 'Detalle almacen')
@section('page-subtitle', 'Consulta de informacion de almacen')

@section('content')
    <x-ui.card title="Informacion de almacen">
        <div class="card-body">
            @include('warehouses.partials.show')
        </div>
        <x-slot:footer>
            <a class="btn btn-outline-secondary" href="{{ route('warehouses.index') }}">Volver</a>
        </x-slot:footer>
    </x-ui.card>
@endsection
