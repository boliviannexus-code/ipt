@extends('layouts.admin')

@section('title', 'Detalle categoria | Inventario POS')
@section('page-title', 'Detalle categoria')
@section('page-subtitle', 'Consulta de informacion de categoria')

@section('content')
    <x-ui.card title="Informacion de categoria">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $category->name }}</dd>
                <dt class="col-sm-3">Descripcion</dt>
                <dd class="col-sm-9">{{ $category->description ?: 'Sin descripcion' }}</dd>
                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9">{{ $category->is_active ? 'Activo' : 'Inactivo' }}</dd>
            </dl>
        </div>

        <x-slot:footer>
            <a class="btn btn-outline-secondary" href="{{ route('categories.index') }}">Volver</a>
        </x-slot:footer>
    </x-ui.card>
@endsection
