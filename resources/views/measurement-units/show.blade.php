@extends('layouts.admin')

@section('title', 'Detalle unidad | Inventario POS')
@section('page-title', 'Detalle unidad de medida')
@section('page-subtitle', 'Consulta de unidad para productos')

@section('content')
    <x-ui.card title="Informacion de unidad">
        <div class="card-body">
            @include('measurement-units.partials.show')
        </div>

        <x-slot:footer>
            <a class="btn btn-outline-secondary" href="{{ route('measurement-units.index') }}">Volver</a>
        </x-slot:footer>
    </x-ui.card>
@endsection
