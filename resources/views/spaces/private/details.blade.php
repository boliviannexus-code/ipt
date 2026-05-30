@extends('layouts.admin')

@section('title', 'Datos principales | '.config('app.name', 'Base Admin'))
@section('page-title', 'Alojamiento privado')
@section('page-subtitle', 'Datos principales')

@section('content')
    <div data-refresh-container>
    @include('spaces.private.partials.stepper')

    <x-ui.card title="Datos principales">
        <div class="card-body">
            <form method="POST" action="{{ route('spaces.private.details.store', $space) }}" data-ajax-form novalidate>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="private-space-type">Tipo de espacio privado</label>
                        <select class="form-select @error('private_space_type_id') is-invalid @enderror" id="private-space-type" name="private_space_type_id" data-tom-select required>
                            <option value="">Seleccionar</option>
                            @foreach ($privateSpaceTypes as $type)
                                <option value="{{ $type->id }}" @selected((int) old('private_space_type_id', $space->private_space_type_id) === $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">{{ $errors->first('private_space_type_id') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="space-title">Titulo del espacio</label>
                        <input class="form-control @error('title') is-invalid @enderror" id="space-title" name="title" value="{{ old('title', $space->title) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('title') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="max-capacity">Capacidad maxima</label>
                        <input class="form-control @error('max_capacity') is-invalid @enderror" id="max-capacity" name="max_capacity" type="number" min="1" value="{{ old('max_capacity', $space->max_capacity) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('max_capacity') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bedrooms-count">Cantidad de habitaciones</label>
                        <input class="form-control @error('bedrooms_count') is-invalid @enderror" id="bedrooms-count" name="bedrooms_count" type="number" min="0" value="{{ old('bedrooms_count', $space->bedrooms_count) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('bedrooms_count') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="beds-count">Numero total de camas</label>
                        <input class="form-control @error('beds_count') is-invalid @enderror" id="beds-count" name="beds_count" type="number" min="1" value="{{ old('beds_count', $space->beds_count) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('beds_count') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="private-bathrooms-count">Baños privados</label>
                        <input class="form-control @error('private_bathrooms_count') is-invalid @enderror" id="private-bathrooms-count" name="private_bathrooms_count" type="number" min="0" value="{{ old('private_bathrooms_count', $space->private_bathrooms_count) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('private_bathrooms_count') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="shared-bathrooms-count">Baños compartidos</label>
                        <input class="form-control @error('shared_bathrooms_count') is-invalid @enderror" id="shared-bathrooms-count" name="shared_bathrooms_count" type="number" min="0" value="{{ old('shared_bathrooms_count', $space->shared_bathrooms_count) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('shared_bathrooms_count') }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" type="submit">Guardar y continuar</button>
                </div>
            </form>
        </div>
    </x-ui.card>
    </div>
@endsection
