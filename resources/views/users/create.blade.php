@extends('layouts.admin')

@section('title', 'Nuevo usuario | '.config('app.name', 'Base Admin'))
@section('page-title', 'Nuevo usuario')
@section('page-subtitle', 'Alta de usuario administrativo')

@section('content')
    <x-ui.form-panel :action="route('users.store')">
        @include('users.partials.form', ['mode' => 'create'])

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Cancelar</a>
        </div>
    </x-ui.form-panel>
@endsection
