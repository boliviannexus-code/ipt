@extends('layouts.admin')

@section('title', 'Editar metodo de pago | Inventario POS')
@section('page-title', 'Editar metodo de pago')

@section('content')
    <x-ui.form-panel :action="route('payment-methods.update', $paymentMethod)" method="PUT">
        @include('payment-methods.partials.fields')
        <x-slot:footer>
            <button class="btn btn-primary" type="submit">Guardar cambios</button>
            <a class="btn btn-outline-secondary" href="{{ route('payment-methods.index') }}">Cancelar</a>
        </x-slot:footer>
    </x-ui.form-panel>
@endsection
