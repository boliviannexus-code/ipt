@extends('layouts.admin')
@section('title', 'Editar plan | '.config('app.name'))
@section('page-title', 'Editar plan')
@section('page-subtitle', 'Actualización del plan y mensualidad')
@section('content')<x-ui.form-panel :action="route('parameters.plans.update', $plan)" method="PUT">@include('parameters.plans.partials.fields')<div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Guardar cambios</button><a class="btn btn-outline-secondary" href="{{ route('parameters.plans.index') }}">Cancelar</a></div></x-ui.form-panel>@endsection
