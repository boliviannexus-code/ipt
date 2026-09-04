@extends('layouts.admin')
@section('title', 'Áreas | '.config('app.name'))
@section('page-title', 'Áreas')
@section('page-subtitle', 'Estructura organizacional por empresa')
@section('content')
<x-ui.table-card title="Listado de áreas" data-refresh-container>
 <x-slot:actions><a class="btn btn-primary btn-sm" href="{{ route('areas.create') }}" data-modal-url="{{ route('areas.create') }}" data-modal-title="Nueva área">Nueva área</a></x-slot:actions>
 <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.areas') }}" data-columns-id="areas-columns" data-order='[[0,"asc"]]'><thead><tr><th>Área</th><th>Cargos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead></table>
 <script type="application/json" id="areas-columns">[{"data":"name","name":"name"},{"data":"positions_count","name":"positions_count","searchable":false},{"data":"is_active","name":"is_active"},{"data":"actions","orderable":false,"searchable":false}]</script>
</x-ui.table-card>
@endsection
