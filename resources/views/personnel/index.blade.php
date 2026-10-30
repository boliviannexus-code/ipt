@extends('layouts.admin')
@section('title', 'Personal')
@section('page-title', 'Personal')
@section('page-subtitle', 'El registro de personal es previo e independiente al acceso de usuario')
@section('content')
<x-ui.table-card title="Listado de personal" data-refresh-container>
    <x-slot:actions>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-success btn-sm" href="{{ route('personnel.export', ['format' => 'csv', ...$filters]) }}" data-personnel-export title="Descargar el listado filtrado en CSV compatible con Excel"><i class="ti ti-file-spreadsheet me-1" aria-hidden="true"></i>Excel (CSV)</a>
            <a class="btn btn-outline-danger btn-sm" href="{{ route('personnel.export', ['format' => 'pdf', ...$filters]) }}" data-personnel-export title="Descargar el listado filtrado en PDF"><i class="ti ti-file-type-pdf me-1" aria-hidden="true"></i>PDF</a>
            @can('personnel.manage')
                <a class="btn btn-primary btn-sm" href="{{ route('personnel.create') }}" data-modal-url="{{ route('personnel.create') }}" data-modal-title="Registrar personal"><i class="ti ti-plus me-1" aria-hidden="true"></i>Registrar personal</a>
            @endcan
        </div>
    </x-slot:actions>
    <form id="personnel-filters" data-personnel-filters class="row g-3 align-items-end p-3 border-bottom" method="GET" action="{{ route('personnel.index') }}">
        <div class="col-sm-5"><label class="form-label" for="personnel-area-filter">Área</label><select class="form-select" id="personnel-area-filter" name="area_id"><option value="">Todas las áreas</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected((int) ($filters['area_id'] ?? 0) === $area->id)>{{ $area->name }}</option>@endforeach</select></div>
        <div class="col-sm-5"><label class="form-label" for="personnel-position-filter">Cargo</label><select class="form-select" id="personnel-position-filter" name="position_id"><option value="">Todos los cargos</option>@foreach($positions as $position)<option value="{{ $position->id }}" data-area-id="{{ $position->area_id }}" @selected((int) ($filters['position_id'] ?? 0) === $position->id)>{{ $position->name }}</option>@endforeach</select></div>
        <div class="col-sm-2"><button class="btn btn-outline-secondary w-100" type="button" data-clear-personnel-filters>Limpiar</button></div>
    </form>
    <table class="table table-hover align-middle" data-datatable data-filters-form="#personnel-filters" data-auto-width="false" data-url="{{ route('datatables.personnel') }}" data-columns-id="personnel-columns" data-order='[[0,"asc"]]'>
        <thead><tr><th>Nombre</th><th>CI</th><th>Área / Cargo</th><th>Sede</th><th>Teléfono</th><th class="table-email-column">Usuario</th><th>Ventas</th><th class="text-end">Acciones</th></tr></thead>
    </table>
    <script type="application/json" id="personnel-columns">[
        {"data":"full_name","name":"full_name"},
        {"data":"identity_document","name":"personnel.identity_document"},
        {"data":"position_label","name":"position_label","orderable":false},
        {"data":"campus_label","name":"campus_label","orderable":false},
        {"data":"contact","name":"contact","orderable":false},
        {"data":"user_label","className":"table-email-column","width":"120px","name":"user_label","orderable":false},
        {"data":"sales","name":"personnel.is_sales_enabled","searchable":false},
        {"data":"actions","orderable":false,"searchable":false}
    ]</script>
</x-ui.table-card>
@endsection
