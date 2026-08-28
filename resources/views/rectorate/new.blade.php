@extends('layouts.admin')

@section('title', 'Nuevo registro')
@section('page-title', 'Nuevo registro')
@section('page-subtitle', 'Inscripciones · Paso 1 de 4')

@section('content')
    @php($editing = isset($application))
    <div class="rectorate-wizard">
        <div class="rectorate-wizard__intro">
            <div>
                <span class="rectorate-wizard__eyebrow">{{ $editing ? 'Inscripción #'.$application->id : 'Proceso de inscripción' }}</span>
                <h2>Datos del titular</h2>
            </div>
            <span class="rectorate-wizard__step-count">01 <small>/ 04</small></span>
        </div>

        <ol class="rectorate-steps" aria-label="Progreso del registro">
            <li class="is-active" aria-current="step"><span>1</span><div><strong>Titular</strong><small>Datos básicos</small></div></li>
            <li><span>2</span><div><strong>Programa</strong><small>Programa y plan</small></div></li>
            <li><span>3</span><div><strong>Estudiante</strong><small>Datos básicos</small></div></li>
            <li><span>4</span><div><strong>Confirmación</strong><small>Próximamente</small></div></li>
        </ol>

        <form method="POST" action="{{ $editing ? route('rectorate.applications.holder.update', $application) : route('rectorate.new.store') }}" class="card rectorate-form" data-rectorate-holder-form data-lookup-url="{{ route('rectorate.new.lookup') }}" novalidate>
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="card-body p-3 p-lg-4">
                @error('campus')
                    <div class="alert alert-warning" role="alert"><i class="ti ti-building-community me-2" aria-hidden="true"></i>{{ $message }}</div>
                @enderror
                <div class="rectorate-section-heading">
                    <span class="rectorate-section-heading__icon"><i class="ti ti-user"></i></span>
                    <div><h3>Información personal</h3></div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="identity_document">CI del titular</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-id"></i></span>
                            <input class="form-control @error('identity_document') is-invalid @enderror" id="identity_document" name="identity_document" value="{{ old('identity_document', $application->identity_document ?? '') }}" maxlength="10" inputmode="numeric" autocomplete="off" data-holder-ci required autofocus>
                        </div>
                        <div class="form-hint" data-holder-lookup-status>{{ $editing ? 'Editando los datos del titular de esta inscripción.' : 'Ingresa primero el CI para buscar datos anteriores del titular.' }}</div>
                        @error('identity_document')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="first_name">Nombre</label>
                        <input class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $application->first_name ?? '') }}" maxlength="100" autocomplete="given-name" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="paternal_surname">Apellido paterno</label>
                        <input class="form-control @error('paternal_surname') is-invalid @enderror" id="paternal_surname" name="paternal_surname" value="{{ old('paternal_surname', $application->paternal_surname ?? '') }}" maxlength="100" autocomplete="family-name" required>
                        @error('paternal_surname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="maternal_surname">Apellido materno</label>
                        <input class="form-control @error('maternal_surname') is-invalid @enderror" id="maternal_surname" name="maternal_surname" value="{{ old('maternal_surname', $application->maternal_surname ?? '') }}" maxlength="100">
                        @error('maternal_surname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="birth_date">Fecha de nacimiento</label>
                        <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" value="{{ old('birth_date', isset($application) ? $application->birth_date?->toDateString() : '') }}" max="{{ now()->subDay()->toDateString() }}" required>
                        @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="email">Correo electrónico</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $application->email ?? '') }}" maxlength="255" autocomplete="email" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="phone">Celular de contacto</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $application->phone ?? '') }}" maxlength="30" autocomplete="tel" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="rectorate-divider"></div>

                <div class="rectorate-section-heading">
                    <span class="rectorate-section-heading__icon is-invoice"><i class="ti ti-receipt-tax"></i></span>
                    <div><h3>Datos para facturación</h3></div>
                </div>

                @if ($identityDocumentTypes->isEmpty())
                    <div class="alert alert-warning"><i class="ti ti-alert-triangle me-2"></i>Primero sincroniza los tipos de documento del catálogo SIAT.</div>
                @endif

                <div class="row g-2">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="identity_document_type_code">Tipo de documento</label>
                        <select class="form-select @error('identity_document_type_code') is-invalid @enderror" id="identity_document_type_code" name="identity_document_type_code" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($identityDocumentTypes as $type)
                                <option value="{{ $type['code'] }}" @selected((string) old('identity_document_type_code', $application->customer->identity_document_type_code ?? '') === $type['code'])>{{ $type['description'] }}</option>
                            @endforeach
                        </select>
                        @error('identity_document_type_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="document_number">Número de documento</label>
                        <input class="form-control @error('document_number') is-invalid @enderror" id="document_number" name="document_number" value="{{ old('document_number', $application->customer->document_number ?? '') }}" maxlength="80" inputmode="numeric" required>
                        @error('document_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="document_complement">Complemento</label>
                        <input class="form-control @error('document_complement') is-invalid @enderror" id="document_complement" name="document_complement" value="{{ old('document_complement', $application->customer->document_complement ?? '') }}" maxlength="20" placeholder="Ej.: 1A">
                        @error('document_complement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required" for="legal_name">Razón social</label>
                        <input class="form-control @error('legal_name') is-invalid @enderror" id="legal_name" name="legal_name" value="{{ old('legal_name', $application->customer->name ?? '') }}" maxlength="255" required>
                        @error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer rectorate-form__footer">
                <button class="btn btn-primary" type="submit" @disabled($identityDocumentTypes->isEmpty())>{{ $editing ? 'Guardar y volver al plan' : 'Guardar paso 1' }} <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
@endsection
