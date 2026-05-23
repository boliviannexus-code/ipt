@extends('layouts.admin')

@section('title', 'Detalle sucursal | Inventario POS')
@section('page-title', 'Detalle sucursal')
@section('page-subtitle', 'Consulta de informacion de sucursal')

@section('content')
    <x-ui.card title="Informacion de sucursal">
        <div class="card-body">
            @include('branches.partials.show')
        </div>
        <x-slot:footer>
            <a class="btn btn-outline-secondary" href="{{ route('branches.index') }}">Volver</a>
        </x-slot:footer>
    </x-ui.card>
@endsection
