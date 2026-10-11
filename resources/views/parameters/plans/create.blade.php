@extends('layouts.admin')

@section('title', 'Nuevo plan | '.config('app.name'))
@section('page-title', 'Nuevo plan')
@section('page-subtitle', 'Registro de plan y mensualidad')

@section('content')
    <x-ui.form-panel :action="route('parameters.plans.store', $program)">
        @include('parameters.plans.partials.fields')

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Guardar plan</button>
            <a class="btn btn-outline-secondary" href="{{ route('parameters.plans.show', $program) }}">Cancelar</a>
        </div>
    </x-ui.form-panel>
@endsection
