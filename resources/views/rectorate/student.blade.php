@extends('layouts.admin')

@section('title', 'Datos del estudiante')
@section('page-title', 'Nueva inscripción')
@section('page-subtitle', 'Inscripciones · Paso 3 de 4')

@section('content')
    <div class="rectorate-wizard">
        <div class="rectorate-wizard__intro">
            <div><span class="rectorate-wizard__eyebrow">Matrícula {{ $application->account_number }} · {{ $application->program->title }} / {{ $application->plan->name }}</span><h2>Datos del estudiante</h2></div>
            <span class="rectorate-wizard__step-count">03 <small>/ 04</small></span>
        </div>

        <ol class="rectorate-steps" aria-label="Progreso del registro">
            <li class="is-complete"><span><i class="ti ti-check"></i></span><div><strong>Titular</strong><small>{{ $application->first_name }} {{ $application->paternal_surname }}</small></div></li>
            <li class="is-complete"><span><i class="ti ti-check"></i></span><div><strong>Programa</strong><small>{{ $application->program->title }}</small></div></li>
            <li class="is-active" aria-current="step"><span>3</span><div><strong>Estudiante</strong><small>Datos básicos</small></div></li>
            <li><span>4</span><div><strong>Confirmación</strong><small>Próximamente</small></div></li>
        </ol>

        <form method="POST" action="{{ route('rectorate.applications.student.update', $application) }}" class="card rectorate-form" data-student-form data-holder-identity-document="{{ $application->identity_document }}" data-holder-first-name="{{ $application->first_name }}" data-holder-paternal-surname="{{ $application->paternal_surname }}" data-holder-maternal-surname="{{ $application->maternal_surname }}" data-holder-birth-date="{{ $application->birth_date?->toDateString() }}" data-holder-email="{{ $application->email }}" data-holder-phone="{{ $application->phone }}">
            @csrf
            @method('PUT')
            <div class="card-body p-3 p-lg-4">
                <div class="rectorate-section-heading">
                    <span class="rectorate-section-heading__icon"><i class="ti ti-user-heart"></i></span>
                    <div><h3>Información del estudiante</h3><p data-student-help></p></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label required" for="student_relationship">Parentesco con el titular</label><select class="form-select @error('student_relationship') is-invalid @enderror" id="student_relationship" name="student_relationship" required autofocus><option value="">Seleccionar...</option>@foreach (['Titular', 'Hijo/a', 'Hermano/a', 'Nieto/a', 'Sobrino/a', 'Otro'] as $relationship)<option @selected(old('student_relationship', $application->student_relationship) === $relationship)>{{ $relationship }}</option>@endforeach</select>@error('student_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label required" for="student_identity_document">CI / documento</label><input class="form-control @error('student_identity_document') is-invalid @enderror" id="student_identity_document" name="student_identity_document" value="{{ old('student_identity_document', $application->student_identity_document) }}" maxlength="30" required>@error('student_identity_document')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label required" for="student_gender">Género</label><select class="form-select @error('student_gender') is-invalid @enderror" id="student_gender" name="student_gender" required><option value="">Seleccionar...</option>@foreach (['Femenino', 'Masculino', 'Otro'] as $gender)<option @selected(old('student_gender', $application->student_gender) === $gender)>{{ $gender }}</option>@endforeach</select>@error('student_gender')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label required" for="student_first_name">Nombre</label><input class="form-control @error('student_first_name') is-invalid @enderror" id="student_first_name" name="student_first_name" value="{{ old('student_first_name', $application->student_first_name) }}" maxlength="100" required>@error('student_first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label required" for="student_paternal_surname">Apellido paterno</label><input class="form-control @error('student_paternal_surname') is-invalid @enderror" id="student_paternal_surname" name="student_paternal_surname" value="{{ old('student_paternal_surname', $application->student_paternal_surname) }}" maxlength="100" required>@error('student_paternal_surname')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="student_maternal_surname">Apellido materno</label><input class="form-control @error('student_maternal_surname') is-invalid @enderror" id="student_maternal_surname" name="student_maternal_surname" value="{{ old('student_maternal_surname', $application->student_maternal_surname) }}" maxlength="100">@error('student_maternal_surname')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label required" for="student_birth_date">Fecha de nacimiento</label><input type="date" class="form-control @error('student_birth_date') is-invalid @enderror" id="student_birth_date" name="student_birth_date" value="{{ old('student_birth_date', $application->student_birth_date?->toDateString()) }}" max="{{ now()->subDay()->toDateString() }}" required>@error('student_birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="student_email">Correo electrónico</label><input type="email" class="form-control @error('student_email') is-invalid @enderror" id="student_email" name="student_email" value="{{ old('student_email', $application->student_email) }}" maxlength="255">@error('student_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="student_phone">Celular</label><input class="form-control @error('student_phone') is-invalid @enderror" id="student_phone" name="student_phone" value="{{ old('student_phone', $application->student_phone) }}" maxlength="30">@error('student_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>

                <div class="rectorate-section-heading mt-4">
                    <span class="rectorate-section-heading__icon"><i class="ti ti-address-book"></i></span>
                    <div><h3>Contacto de referencia principal</h3><p>Persona a quien se contactará primero durante la formación.</p></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required" for="primary_contact_type">Contacto principal</label>
                        <select class="form-select @error('primary_contact_type') is-invalid @enderror" id="primary_contact_type" name="primary_contact_type" required data-primary-contact-type>
                            <option value="">Seleccionar...</option>
                            @foreach (['Titular', 'Estudiante', 'Otro'] as $contactType)<option @selected(old('primary_contact_type', $application->primary_contact_type) === $contactType)>{{ $contactType }}</option>@endforeach
                        </select>
                        @error('primary_contact_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-3" data-other-reference-fields>
                    <div class="alert alert-info py-2"><i class="ti ti-info-circle me-1" aria-hidden="true"></i>Completa los datos de la persona de referencia.</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label required" for="reference_first_name">Nombre</label><input class="form-control @error('reference_first_name') is-invalid @enderror" id="reference_first_name" name="reference_first_name" value="{{ old('reference_first_name', $application->reference_first_name) }}" maxlength="100" data-other-reference-input>@error('reference_first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label required" for="reference_last_name">Apellido</label><input class="form-control @error('reference_last_name') is-invalid @enderror" id="reference_last_name" name="reference_last_name" value="{{ old('reference_last_name', $application->reference_last_name) }}" maxlength="150" data-other-reference-input>@error('reference_last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label required" for="reference_relationship">Parentesco</label><input class="form-control @error('reference_relationship') is-invalid @enderror" id="reference_relationship" name="reference_relationship" value="{{ old('reference_relationship', $application->reference_relationship) }}" maxlength="60" data-other-reference-input>@error('reference_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label required" for="reference_phone">Teléfono de contacto</label><input class="form-control @error('reference_phone') is-invalid @enderror" id="reference_phone" name="reference_phone" value="{{ old('reference_phone', $application->reference_phone) }}" maxlength="30" inputmode="tel" data-other-reference-input>@error('reference_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                </div>
            </div>
            <div class="card-footer rectorate-form__footer">
                <a class="btn btn-outline-secondary" href="{{ route('rectorate.applications.holder.edit', $application) }}"><i class="ti ti-user-edit me-1"></i>Titular</a>
                <a class="btn btn-outline-secondary" href="{{ route('rectorate.applications.plan.edit', $application) }}"><i class="ti ti-arrow-left me-1"></i>Volver a programa</a>
                <button class="btn btn-primary" type="submit">Guardar estudiante <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
@endsection
