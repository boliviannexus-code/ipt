@extends('layouts.admin')

@section('title', 'Editar usuario | '.config('app.name', 'Base Admin'))
@section('page-title', 'Editar usuario')
@section('page-subtitle', 'Actualizacion de datos y roles')

@section('content')
    <x-ui.form-panel :action="route('users.update', $user)" method="PUT">
        @include('users.partials.form', ['mode' => 'edit'])

        <div class="d-flex gap-2 mt-4">
            @can('users.change-password')
                <button class="btn btn-outline-warning" type="button" data-user-password-reset data-reset-url="{{ route('users.reset-password', $user) }}"><i class="ti ti-key me-1"></i>Restablecer contraseña</button>
            @endcan
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Cancelar</a>
        </div>
    </x-ui.form-panel>
@endsection
