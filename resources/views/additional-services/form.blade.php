@extends('layouts.admin')
@section('title', $service->exists ? 'Editar servicio adicional' : 'Nuevo servicio adicional')
@section('page-title', $service->exists ? 'Editar servicio adicional' : 'Nuevo servicio adicional')
@section('content')
<x-ui.card title="Datos del servicio">
    <div class="card-body">
        <form class="row g-3" method="POST" action="{{ $service->exists ? route('additional-services.update', $service) : route('additional-services.store') }}">
            @csrf
            @if($service->exists) @method('PUT') @endif
            <div class="col-md-8"><label class="form-label required" for="service-title">Título</label><input class="form-control @error('title') is-invalid @enderror" id="service-title" name="title" value="{{ old('title', $service->title) }}" maxlength="150" required><div class="invalid-feedback">@error('title'){{ $message }}@enderror</div></div>
            <div class="col-md-4"><label class="form-label required" for="service-cost">Costo (Bs)</label><input class="form-control @error('cost') is-invalid @enderror" type="number" id="service-cost" name="cost" min="0" max="9999999999.99" step="0.01" value="{{ old('cost', $service->cost) }}" required><div class="invalid-feedback">@error('cost'){{ $message }}@enderror</div></div>
            <div class="col-12"><label class="form-label required" for="service-detail">Detalle</label><textarea class="form-control @error('detail') is-invalid @enderror" name="detail" id="service-detail" rows="3" maxlength="2000" required>{{ old('detail', $service->detail) }}</textarea><div class="invalid-feedback">@error('detail'){{ $message }}@enderror</div></div>
            <div class="col-12"><input type="hidden" name="is_active" value="0"><label class="form-check form-switch"><input class="form-check-input" name="is_active" type="checkbox" value="1" @checked(old('is_active', $service->is_active))><span class="form-check-label">Servicio activo</span></label><p class="form-hint">Desactive un servicio para impedir nuevos cargos. Los cargos existentes conservan su título, detalle y costo.</p></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Guardar servicio</button><a class="btn btn-outline-secondary" href="{{ route('additional-services.index') }}">Volver</a></div>
        </form>
    </div>
</x-ui.card>
@endsection
