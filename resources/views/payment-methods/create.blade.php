@extends('layouts.admin')

@section('title', 'Nuevo metodo de pago | Inventario POS')
@section('page-title', 'Nuevo metodo de pago')

@section('content')
    <x-ui.form-panel :action="route('payment-methods.store')">
        @include('payment-methods.partials.fields', ['paymentMethod' => null])
        <x-slot:footer>
            <button class="btn btn-primary" type="submit">Crear metodo</button>
            <a class="btn btn-outline-secondary" href="{{ route('payment-methods.index') }}">Cancelar</a>
        </x-slot:footer>
    </x-ui.form-panel>
@endsection
