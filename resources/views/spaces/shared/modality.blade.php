@extends('layouts.admin')

@section('title', 'Nuevo alojamiento compartido | '.config('app.name', 'Base Admin'))
@section('page-title', 'Nuevo alojamiento compartido')
@section('page-subtitle', 'Selecciona la modalidad')

@section('content')
    <div data-refresh-container>
    <form method="POST" action="{{ route('spaces.shared.modality.store') }}" data-ajax-form novalidate>
        @csrf
        <div class="row g-3">
            <div class="col-lg-6">
                <label class="form-selectgroup-item flex-fill">
                    <input class="form-selectgroup-input" name="space_mode" type="radio" value="compartido" checked>
                    <div class="form-selectgroup-label d-flex align-items-start p-3">
                        <span class="me-3"><i class="ti ti-building-community fs-1 text-primary"></i></span>
                        <span>
                            <span class="d-block fw-semibold">Compartido</span>
                            <span class="d-block text-body-secondary">Compartido significa que el huesped reserva una habitacion o una cama dentro de un alojamiento como hotel, hostal, residencia u hospedaje.</span>
                        </span>
                    </div>
                </label>
            </div>
            <div class="col-lg-6">
                <a class="form-selectgroup-item flex-fill text-decoration-none" href="{{ route('spaces.private.create') }}">
                    <div class="form-selectgroup-label d-flex align-items-start p-3">
                        <span class="me-3"><i class="ti ti-home fs-1 text-secondary"></i></span>
                        <span>
                            <span class="d-block fw-semibold">Privado</span>
                            <span class="d-block text-body-secondary">Para casas, departamentos, cabañas o suites completas.</span>
                        </span>
                    </div>
                </a>
            </div>
        </div>
        @error('space_mode')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-primary" type="submit">Continuar</button>
        </div>
    </form>
    </div>
@endsection
