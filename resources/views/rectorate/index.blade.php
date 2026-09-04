@extends('layouts.admin')
@section('title', 'Inscripciones')
@section('page-title', 'Inscripciones')
@section('page-subtitle', 'Titulares y avance de sus inscripciones en la empresa activa')
@section('content')
<x-ui.table-card title="Listado de inscripciones">
    <x-slot:actions><a class="btn btn-primary btn-sm" href="{{ route('rectorate.new') }}"><i class="ti ti-plus me-1"></i>Nueva inscripción</a></x-slot:actions>
    <table class="table table-hover align-middle mb-0" data-datatable data-url="{{ route('datatables.enrollments') }}" data-columns-id="enrollments-columns" data-order='[[6,"desc"]]'>
        <thead><tr><th>Matrícula / Sede</th><th>Titular</th><th>CI</th><th>Contacto</th><th>Programa / Plan</th><th>Estado</th><th>Registrado</th><th class="text-end">Acción</th></tr></thead>
    </table>
    <script type="application/json" id="enrollments-columns">[{"data":"enrollment","name":"enrollment"},{"data":"holder","name":"holder"},{"data":"identity_document","name":"identity_document"},{"data":"contact","orderable":false,"searchable":false},{"data":"program_plan","name":"program_plan"},{"data":"progress","name":"status"},{"data":"created_at","name":"created_at"},{"data":"actions","orderable":false,"searchable":false}]</script>
</x-ui.table-card>
@endsection
