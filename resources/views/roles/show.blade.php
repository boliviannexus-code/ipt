@extends('layouts.admin')
@section('title', 'Detalle rol | '.config('app.name', 'Base Admin'))
@section('page-title', 'Detalle rol')
@section('content')
    <x-ui.card title="Informacion del rol"><div class="card-body">@include('roles.partials.show')</div><x-slot:footer><a class="btn btn-outline-secondary" href="{{ route('roles.index') }}">Volver</a></x-slot:footer></x-ui.card>
@endsection
