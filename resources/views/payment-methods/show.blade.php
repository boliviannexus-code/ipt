@extends('layouts.admin')

@section('title', 'Detalle de metodo de pago | Inventario POS')
@section('page-title', 'Detalle de metodo de pago')

@section('content')
    <div class="card">
        <div class="card-body">@include('payment-methods.partials.show')</div>
        <div class="card-footer">
            <a class="btn btn-outline-secondary" href="{{ route('payment-methods.index') }}">Volver</a>
        </div>
    </div>
@endsection
