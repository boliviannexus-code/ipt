@extends('layouts.admin')
@section('title', 'Promover '.$module->name)
@section('page-title', 'Promover módulo')
@section('page-subtitle', 'Paso 1 de 2 · Fechas del nuevo módulo')
@section('content')
<x-ui.card title="Datos del nuevo módulo">
    <div class="card-body">
        <div class="alert alert-info"><strong>{{ $module->name }}</strong> se promoverá como <strong>Módulo {{ $context['targetLevel']->name }}</strong>. Se conservarán modalidad, horario y docente.</div>
        <form method="POST" action="{{ route('academic.promotions.students', $module) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label required" for="promotion-start-date">Fecha de inicio</label><input class="form-control @error('start_date') is-invalid @enderror" id="promotion-start-date" name="start_date" type="date" min="{{ $module->end_date->copy()->addDay()->toDateString() }}" value="{{ old('start_date', $module->end_date->copy()->addDay()->toDateString()) }}" required>@error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label required" for="promotion-end-date">Fecha de fin</label><input class="form-control @error('end_date') is-invalid @enderror" id="promotion-end-date" name="end_date" type="date" value="{{ old('end_date', $module->end_date->copy()->addMonths($module->start_date->diffInMonths($module->end_date) ?: 1)->toDateString()) }}" required>@error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="d-flex justify-content-between mt-4"><a class="btn btn-outline-secondary" href="{{ route('academic.promotions.index') }}">Cancelar</a><button class="btn btn-primary" type="submit">Continuar <i class="ti ti-arrow-right ms-1" aria-hidden="true"></i></button></div>
        </form>
    </div>
</x-ui.card>
@endsection
