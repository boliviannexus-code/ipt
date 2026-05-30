@extends('layouts.admin')

@section('title', 'Ubicacion | '.config('app.name', 'Base Admin'))
@section('page-title', $space->name ?: 'Alojamiento compartido')
@section('page-subtitle', 'Ubicacion')

@section('content')
    <div data-refresh-container>
    @include('spaces.shared.partials.stepper')

    <x-ui.card title="Ubicacion">
        <div class="card-body">
            <form method="POST" action="{{ route('spaces.shared.location.store', $space) }}" data-ajax-form novalidate>
                @csrf
                @method('PUT')
                @php($location = $space->location)
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="country">Pais</label>
                        <input class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country', $location->country ?? 'Bolivia') }}" required>
                        <div class="invalid-feedback">{{ $errors->first('country') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="state-or-region">Region/departamento</label>
                        <input class="form-control @error('state_or_region') is-invalid @enderror" id="state-or-region" name="state_or_region" value="{{ old('state_or_region', $location->state_or_region ?? '') }}">
                        <div class="invalid-feedback">{{ $errors->first('state_or_region') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="city">Ciudad</label>
                        <input class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $location->city ?? '') }}" required>
                        <div class="invalid-feedback">{{ $errors->first('city') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="zone">Zona/barrio</label>
                        <input class="form-control @error('zone_or_neighborhood') is-invalid @enderror" id="zone" name="zone_or_neighborhood" value="{{ old('zone_or_neighborhood', $location->zone_or_neighborhood ?? '') }}">
                        <div class="invalid-feedback">{{ $errors->first('zone_or_neighborhood') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="address">Direccion</label>
                        <input class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $location->address ?? '') }}" required>
                        <div class="invalid-feedback">{{ $errors->first('address') }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="reference">Referencia</label>
                        <textarea class="form-control @error('reference') is-invalid @enderror" id="reference" name="reference" rows="2">{{ old('reference', $location->reference ?? '') }}</textarea>
                        <div class="invalid-feedback">{{ $errors->first('reference') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="latitude">Latitud</label>
                        <input class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude', $location->latitude ?? '') }}" placeholder="-16.5000000">
                        <div class="invalid-feedback">{{ $errors->first('latitude') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="longitude">Longitud</label>
                        <input class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude', $location->longitude ?? '') }}" placeholder="-68.1500000">
                        <div class="invalid-feedback">{{ $errors->first('longitude') }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('spaces.shared.services.edit', $space) }}">Volver</a>
                    <button class="btn btn-primary" type="submit">Guardar y continuar</button>
                </div>
            </form>
        </div>
    </x-ui.card>
    </div>
@endsection
