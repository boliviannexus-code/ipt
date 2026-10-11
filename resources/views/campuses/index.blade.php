@extends('layouts.admin')

@section('title', 'Sedes | '.config('app.name'))
@section('page-title', 'Sedes')
@section('page-subtitle', 'Sedes de la empresa activa')

@section('content')
    <x-ui.table-card title="Listado de sedes" data-refresh-container>
        <x-slot:actions>
            @can('campuses.manage')
                <a class="btn btn-primary btn-sm" href="{{ route('campuses.create') }}" data-modal-url="{{ route('campuses.create') }}" data-modal-title="Nueva sede">
                    <i class="ti ti-plus me-1" aria-hidden="true"></i>Nueva sede
                </a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.campuses') }}" data-columns-id="campuses-columns" data-order='[[0,"asc"]]'>
            <thead><tr><th>Nombre</th><th>Código</th><th>Dirección</th><th class="text-end">Acciones</th></tr></thead>
        </table>
        <script type="application/json" id="campuses-columns">[{"data":"name","name":"name"},{"data":"code","name":"code"},{"data":"address","name":"address"},{"data":"actions","orderable":false,"searchable":false}]</script>
    </x-ui.table-card>
@endsection
