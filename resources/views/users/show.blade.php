@extends('layouts.admin')

@section('title', 'Detalle usuario | '.config('app.name', 'Base Admin'))
@section('page-title', 'Detalle usuario')
@section('page-subtitle', 'Consulta de usuario administrativo')

@section('content')
    <x-ui.card title="Informacion del usuario">
        <div class="card-body">
            @include('users.partials.show')
        </div>

        <x-slot:footer>
            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Volver</a>
        </x-slot:footer>
    </x-ui.card>
@endsection
