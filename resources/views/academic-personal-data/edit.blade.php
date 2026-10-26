@extends('layouts.admin')
@section('title', 'Editar titular y estudiante')
@section('page-title', 'Editar titular y estudiante')
@section('page-subtitle', 'Matrícula: '.($application->account_number ?? $application->student->account_number ?? '—'))
@section('content')
<a class="btn btn-outline-secondary mb-3" href="{{ route('academic.personal-data.index') }}">Volver al listado</a>
<p class="text-secondary">Los datos del estudiante se actualizarán en sus inscripciones. Los datos del titular y contacto corresponden a esta inscripción.</p>
<form method="POST" action="{{ route('academic.personal-data.update', $application) }}">
    @csrf
    @method('PUT')
    @foreach(['' => 'Datos del titular', 'student_' => 'Datos del estudiante'] as $prefix => $title)
    <x-ui.card :title="$title" class="mb-3">
        <div class="card-body row g-3">
            @foreach(['identity_document' => ['CI', 'text'], 'first_name' => ['Nombres', 'text'], 'paternal_surname' => ['Apellido paterno', 'text'], 'maternal_surname' => ['Apellido materno', 'text'], 'birth_date' => ['Fecha de nacimiento', 'date'], 'email' => ['Correo electrónico', 'email'], 'phone' => ['Teléfono', 'tel']] as $field => [$label, $type])
                @php
                    $name = $prefix.$field;
                    $record = $prefix === '' ? $application : $application->student;
                    $value = $field === 'birth_date' ? $record->birth_date?->format('Y-m-d') : $record->{$field};
                    $required = !in_array($field, ['maternal_surname', 'email', 'phone']) || ($prefix === '' && in_array($field, ['email', 'phone']));
                @endphp
                <div class="col-md-6">
                    <label class="form-label {{ $required ? 'required' : '' }}" for="{{ $name }}">{{ $label }}</label>
                    <input class="form-control @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)>
                    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </x-ui.card>
    @endforeach
    <x-ui.card title="Relación y contacto" class="mb-3">
        <div class="card-body row g-3">
            @foreach(['student_gender' => ['Género del estudiante', ['Femenino', 'Masculino', 'Otro']], 'student_relationship' => ['Relación con el titular', ['Titular', 'Hijo/a', 'Hermano/a', 'Nieto/a', 'Sobrino/a', 'Otro']], 'primary_contact_type' => ['Contacto principal', ['Titular', 'Estudiante', 'Otro']]] as $name => [$label, $options])
                <div class="col-md-4"><label class="form-label required" for="{{ $name }}">{{ $label }}</label><select class="form-select @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" required><option value="">Seleccionar</option>@foreach($options as $option)<option value="{{ $option }}" @selected(old($name, $name === 'student_gender' ? $application->student->gender : $application->{$name}) === $option)>{{ $option }}</option>@endforeach</select>@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endforeach
            <p class="text-secondary mb-0">Si el contacto principal es «Otro», completa los datos de referencia.</p>
            @foreach(['reference_first_name' => 'Nombres de referencia', 'reference_last_name' => 'Apellidos de referencia', 'reference_relationship' => 'Relación de referencia', 'reference_phone' => 'Teléfono de referencia'] as $name => $label)
                <div class="col-md-6"><label class="form-label" for="{{ $name }}">{{ $label }}</label><input class="form-control @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" type="{{ $name === 'reference_phone' ? 'tel' : 'text' }}" value="{{ old($name, $application->{$name}) }}">@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endforeach
        </div>
    </x-ui.card>
    <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">Guardar cambios</button><a class="btn btn-outline-secondary" href="{{ route('academic.personal-data.index') }}">Cancelar</a></div>
</form>
@endsection
