@extends('layouts.admin')

@section('title', 'Detalle punto de venta | Inventario POS')
@section('page-title', 'Detalle punto de venta')

@section('content')
    <x-ui.card title="Informacion de punto de venta">
        <div class="card-body">@include('point-of-sales.partials.show')</div>
        <x-slot:footer><a class="btn btn-outline-secondary" href="{{ route('point-of-sales.index') }}">Volver</a></x-slot:footer>
    </x-ui.card>
@endsection
