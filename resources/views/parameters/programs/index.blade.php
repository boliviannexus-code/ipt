@extends('layouts.admin')
@section('title', 'Programas')
@section('page-title', 'Programas')
@section('page-subtitle', 'Programas académicos y planes vinculados')
@section('content')
<x-ui.table-card title="Listado de programas">
    <x-slot:actions>@can('programs.create')<a class="btn btn-primary btn-sm" href="{{ route('parameters.programs.create') }}"><i class="ti ti-plus me-1"></i>Nuevo programa</a>@endcan</x-slot:actions>
    <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.programs') }}" data-columns-id="programs-columns" data-order='[[0,"asc"]]'>
        <thead><tr><th>Título</th><th>Código matrícula</th><th>Duración</th><th>Planes</th><th>Niveles</th><th class="text-end">Acciones</th></tr></thead>
    </table>
    <script type="application/json" id="programs-columns">[{"data":"title","name":"title"},{"data":"enrollment_code","name":"enrollment_code"},{"data":"duration_months","name":"duration_months"},{"data":"plans_count","searchable":false},{"data":"levels_count","searchable":false},{"data":"actions","orderable":false,"searchable":false}]</script>
</x-ui.table-card>
@endsection
