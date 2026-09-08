@extends('layouts.admin')
@section('title', 'Seleccionar estudiantes')
@section('page-title', 'Promover módulo')
@section('page-subtitle', 'Paso 2 de 2 · Selección de estudiantes')
@section('content')
<form method="POST" action="{{ route('academic.promotions.store', $module) }}" data-confirm-action data-confirm-title="¿Crear y asignar el nuevo módulo?" data-confirm-text="Se asignarán los estudiantes seleccionados y se generará un nuevo cargo para cada uno." data-confirm-button="Sí, crear módulo">
    @csrf
    <input type="hidden" name="start_date" value="{{ $dates['start_date'] }}">
    <input type="hidden" name="end_date" value="{{ $dates['end_date'] }}">
    <x-ui.table-card title="Estudiantes de {{ $module->name }}">
        <x-slot:actions><span class="badge text-bg-azure">Destino: {{ $context['targetLevel']->name }}</span></x-slot:actions>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th style="width:3rem"><span class="visually-hidden">Seleccionar</span></th><th>Estudiante</th><th>CI</th><th>Disponibilidad</th></tr></thead>
            <tbody>
                @foreach($context['students'] as $row)
                    <tr>
                        <td><input class="form-check-input" type="checkbox" name="student_ids[]" value="{{ $row['student']->id }}" aria-label="Asignar a {{ $row['student']->first_name }}" @checked($row['eligible']) @disabled(!$row['eligible'])></td>
                        <td class="fw-semibold">{{ trim("{$row['student']->first_name} {$row['student']->paternal_surname} {$row['student']->maternal_surname}") }}</td>
                        <td>{{ $row['student']->identity_document }}</td>
                        <td>@if($row['eligible'])<span class="badge text-bg-success">Disponible</span>@else<span class="badge text-bg-secondary">{{ $row['reason'] }}</span>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <x-slot:footer><div class="d-flex justify-content-between"><a class="btn btn-outline-secondary" href="{{ route('academic.promotions.create', $module) }}"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Volver</a><button class="btn btn-primary" type="submit" @disabled(!$context['students']->contains('eligible', true))>Crear módulo y cargos</button></div></x-slot:footer>
    </x-ui.table-card>
    @error('student_ids')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror
</form>
@endsection
