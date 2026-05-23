@extends('layouts.admin')
@section('title', 'Detalle permiso | Inventario POS')
@section('page-title', 'Detalle permiso')
@section('content')
    <x-ui.card title="Informacion del permiso"><div class="card-body">@include('permissions.partials.show')</div><x-slot:footer><a class="btn btn-outline-secondary" href="{{ route('permissions.index') }}">Volver</a></x-slot:footer></x-ui.card>
@endsection
