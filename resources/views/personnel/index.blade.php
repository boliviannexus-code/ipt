@extends('layouts.admin')
@section('title', 'Personal')
@section('page-title', 'Personal')
@section('page-subtitle', 'El registro de personal es previo e independiente al acceso de usuario')
@section('content')
<x-ui.table-card title="Listado de personal" data-refresh-container>
    @can('personnel.manage')
        <x-slot:actions><a class="btn btn-primary btn-sm" href="{{ route('personnel.create') }}" data-modal-url="{{ route('personnel.create') }}" data-modal-title="Registrar personal"><i class="ti ti-plus me-1" aria-hidden="true"></i>Registrar personal</a></x-slot:actions>
    @endcan
    <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.personnel') }}" data-columns-id="personnel-columns" data-order='[[0,"asc"]]'>
        <thead><tr><th>Nombre</th><th>CI</th><th>Área / Cargo</th><th>Sede</th><th>Contacto</th><th>Usuario</th><th>Ventas</th><th class="text-end">Acciones</th></tr></thead>
    </table>
    <script type="application/json" id="personnel-columns">[
        {"data":"full_name","name":"full_name"},
        {"data":"identity_document","name":"personnel.identity_document"},
        {"data":"position_label","name":"position_label","orderable":false},
        {"data":"campus_label","name":"campus_label","orderable":false},
        {"data":"contact","name":"contact","orderable":false},
        {"data":"user_label","name":"user_label","orderable":false},
        {"data":"sales","name":"personnel.is_sales_enabled","searchable":false},
        {"data":"actions","orderable":false,"searchable":false}
    ]</script>
</x-ui.table-card>
@endsection
