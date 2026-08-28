@extends('layouts.admin')

@section('title', 'Confirmar inscripción')
@section('page-title', 'Nueva inscripción')
@section('page-subtitle', 'Inscripciones · Paso 4 de 4')

@section('content')
    <div class="rectorate-wizard">
        <div class="rectorate-wizard__intro">
            <div><span class="rectorate-wizard__eyebrow">Cuenta {{ $application->account_number }} · Inscripción #{{ $application->id }}</span><h2>Resumen</h2></div>
            <span class="rectorate-wizard__step-count">04 <small>/ 04</small></span>
        </div>

        <ol class="rectorate-steps" aria-label="Progreso del registro">
            <li class="is-complete"><span><i class="ti ti-check"></i></span><div><strong>Titular</strong><small>Completado</small></div></li>
            <li class="is-complete"><span><i class="ti ti-check"></i></span><div><strong>Programa</strong><small>Completado</small></div></li>
            <li class="is-complete"><span><i class="ti ti-check"></i></span><div><strong>Estudiante</strong><small>Completado</small></div></li>
            <li class="is-active" aria-current="step"><span>4</span><div><strong>Confirmación</strong><small>Resumen</small></div></li>
        </ol>

        <div class="card rectorate-form">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="rectorate-summary">
                            <div class="rectorate-section-heading"><span class="rectorate-section-heading__icon"><i class="ti ti-user"></i></span><div><h3>Titular</h3></div></div>
                            <dl><dt>Número de cuenta</dt><dd class="fw-semibold">{{ $application->account_number }}</dd><dt>Sede</dt><dd>{{ $application->campus->name }} · {{ $application->campus->code }}</dd><dt>Nombre</dt><dd>{{ $application->first_name }} {{ $application->paternal_surname }} {{ $application->maternal_surname }}</dd><dt>CI</dt><dd>{{ $application->identity_document }}</dd><dt>Nacimiento</dt><dd>{{ $application->birth_date->format('d/m/Y') }}</dd><dt>Contacto</dt><dd>{{ $application->email }} · {{ $application->phone }}</dd></dl>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="rectorate-summary">
                            <div class="rectorate-section-heading"><span class="rectorate-section-heading__icon is-invoice"><i class="ti ti-receipt-tax"></i></span><div><h3>Facturación, programa y plan</h3></div></div>
                            <dl><dt>Razón social</dt><dd>{{ $application->customer->name }}</dd><dt>Documento</dt><dd>{{ $application->customer->document_number }} {{ $application->customer->document_complement }}</dd><dt>Programa</dt><dd>{{ $application->program->title }} · {{ $application->program->duration_months }} meses</dd><dt>Plan</dt><dd>{{ $application->plan->name }} · Bs {{ number_format((float) $application->plan->monthly_cost, 2, ',', '.') }}</dd><dt>Origen comercial</dt><dd>{{ $application->commercialOrigin->name }}</dd><dt>Ejecutivo de ventas</dt><dd>{{ $application->salesExecutive->full_name }}</dd></dl>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="rectorate-summary">
                            <div class="rectorate-section-heading"><span class="rectorate-section-heading__icon"><i class="ti ti-user-heart"></i></span><div><h3>Estudiante</h3></div></div>
                            <dl class="rectorate-summary__student"><dt>Número de cuenta</dt><dd class="fw-semibold">{{ $application->account_number }}</dd><dt>Sede</dt><dd>{{ $application->campus->name }}</dd><dt>Nombre</dt><dd>{{ $application->student_first_name }} {{ $application->student_paternal_surname }} {{ $application->student_maternal_surname }}</dd><dt>CI</dt><dd>{{ $application->student_identity_document }}</dd><dt>Nacimiento</dt><dd>{{ $application->student_birth_date->format('d/m/Y') }}</dd><dt>Parentesco</dt><dd>{{ $application->student_relationship }}</dd><dt>Género</dt><dd>{{ $application->student_gender }}</dd><dt>Contacto</dt><dd>{{ $application->student_email ?: '—' }} · {{ $application->student_phone ?: '—' }}</dd></dl>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="rectorate-summary">
                            <div class="rectorate-section-heading"><span class="rectorate-section-heading__icon"><i class="ti ti-address-book"></i></span><div><h3>Contacto de referencia principal</h3></div></div>
                            @switch($application->primary_contact_type)
                                @case('Titular')
                                    <dl><dt>Contacto</dt><dd>Titular</dd><dt>Nombre</dt><dd>{{ $application->first_name }} {{ $application->paternal_surname }} {{ $application->maternal_surname }}</dd><dt>Teléfono</dt><dd>{{ $application->phone }}</dd></dl>
                                    @break
                                @case('Estudiante')
                                    <dl><dt>Contacto</dt><dd>Estudiante</dd><dt>Nombre</dt><dd>{{ $application->student_first_name }} {{ $application->student_paternal_surname }} {{ $application->student_maternal_surname }}</dd><dt>Teléfono</dt><dd>{{ $application->student_phone }}</dd></dl>
                                    @break
                                @case('Otro')
                                    <dl><dt>Contacto</dt><dd>Otra persona</dd><dt>Nombre</dt><dd>{{ $application->reference_first_name }} {{ $application->reference_last_name }}</dd><dt>Parentesco</dt><dd>{{ $application->reference_relationship }}</dd><dt>Teléfono</dt><dd>{{ $application->reference_phone }}</dd></dl>
                                    @break
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer rectorate-form__footer">
                @if ($application->status === 'completed')
                    @if ($application->contract)
                        <a class="btn btn-success" href="{{ route('rectorate.contracts.account.show', $application->contract) }}"><i class="ti ti-cash me-1"></i>Estado de cuenta</a>
                    @endif
                    <a class="btn btn-primary" href="{{ route('rectorate.index') }}"><i class="ti ti-arrow-left me-1"></i>Volver al listado</a>
                @else
                    <a class="btn btn-outline-secondary" href="{{ route('rectorate.applications.student.edit', $application) }}"><i class="ti ti-arrow-left me-1"></i>Editar estudiante</a>
                    <form method="POST" action="{{ route('rectorate.applications.confirmation.store', $application) }}" data-disable-on-submit data-submitting-label="Confirmando…">@csrf<button class="btn btn-success" type="submit"><i class="ti ti-check me-1"></i><span>Confirmar inscripción</span></button></form>
                @endif
            </div>
        </div>
    </div>
@endsection
