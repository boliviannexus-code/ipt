@extends('layouts.admin')

@section('title', 'Detalle proveedor | Inventario POS')
@section('page-title', 'Detalle proveedor')
@section('page-subtitle', 'Consulta de informacion del proveedor')

@section('content')
    <x-ui.card title="Informacion del proveedor">
        <div class="card-body">
            @include('suppliers.partials.show')
        </div>

        <x-slot:footer>
            <a class="btn btn-outline-secondary" href="{{ route('suppliers.index') }}">Volver</a>
        </x-slot:footer>
    </x-ui.card>
@endsection
