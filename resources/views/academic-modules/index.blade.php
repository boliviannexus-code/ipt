@extends('layouts.admin')
@section('title', 'Módulos académicos')
@section('page-title', 'Módulos')
@section('page-subtitle', 'Programa, nivel, modalidad y horarios')
@section('content')
<div data-refresh-container>
    <x-ui.table-card title="Listado de módulos">
        <x-slot:actions>@can('academic-modules.manage')<a class="btn btn-primary btn-sm" href="{{ route('academic.modules.create') }}" data-modal-url="{{ route('academic.modules.create') }}" data-modal-title="Nuevo módulo" data-modal-size="lg"><i class="ti ti-plus me-1"></i>Nuevo módulo</a>@endcan</x-slot:actions>
        <table class="table table-hover align-middle mb-0" data-datatable data-url="{{ route('datatables.academic-modules') }}" data-columns-id="academic-modules-columns" data-order='[[0,"asc"]]'>
            <thead><tr><th>Módulo</th><th>Programa</th><th>Nivel</th><th>Docente</th><th>Modalidad</th><th>Horario</th><th>Fechas</th><th class="text-end">Acciones</th></tr></thead>
        </table>
        <script type="application/json" id="academic-modules-columns">[{"data":"name","name":"name"},{"data":"program_name","name":"program_name"},{"data":"level_name","name":"level_name"},{"data":"teacher_name","name":"teacher_name","orderable":false},{"data":"modality","name":"modality"},{"data":"schedule","orderable":false,"searchable":false},{"data":"dates","orderable":false,"searchable":false},{"data":"actions","orderable":false,"searchable":false}]</script>
    </x-ui.table-card>
</div>
@endsection
