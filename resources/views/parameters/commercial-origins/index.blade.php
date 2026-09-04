@extends('layouts.admin')

@section('title', 'Origen comercial | '.config('app.name'))
@section('page-title', 'Origen comercial')
@section('page-subtitle', 'Orígenes comerciales de la empresa activa')

@section('content')
    <x-ui.table-card title="Listado de orígenes comerciales" data-refresh-container>
        <x-slot:actions>
            @can('commercial-origins.create')
                <a class="btn btn-primary btn-sm" href="{{ route('parameters.commercial-origins.create') }}" data-modal-url="{{ route('parameters.commercial-origins.create') }}" data-modal-title="Nuevo origen comercial">
                    <i class="ti ti-plus me-1" aria-hidden="true"></i>Nuevo origen comercial
                </a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.commercial-origins') }}" data-columns-id="commercial-origins-columns" data-order='[[0,"asc"]]'>
            <thead><tr><th>Nombre</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
        </table>
        <script type="application/json" id="commercial-origins-columns">[{"data":"name","name":"name"},{"data":"created_at","name":"created_at"},{"data":"actions","orderable":false,"searchable":false}]</script>
    </x-ui.table-card>
@endsection
