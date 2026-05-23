@extends('layouts.admin')

@section('title', 'Editar usuario | '.config('app.name', 'Base Admin'))
@section('page-title', 'Editar usuario')
@section('page-subtitle', 'Actualizacion de datos y roles')

@section('content')
    <x-ui.form-panel :action="route('users.update', $user)" method="PUT">
        @include('users.partials.form', ['mode' => 'edit'])

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Cancelar</a>
        </div>
    </x-ui.form-panel>
@endsection
