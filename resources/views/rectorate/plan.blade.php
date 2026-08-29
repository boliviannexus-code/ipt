@extends('layouts.admin')

@section('title', 'Seleccionar programa')
@section('page-title', 'Nueva inscripción')
@section('page-subtitle', 'Inscripciones · Paso 2 de 4')

@section('content')
    <div class="rectorate-wizard">
        <div class="rectorate-wizard__intro">
            <div><span class="rectorate-wizard__eyebrow">Matrícula {{ $application->account_number ?: 'pendiente' }}</span><h2>Programa y plan</h2></div>
            <span class="rectorate-wizard__step-count">02 <small>/ 04</small></span>
        </div>

        <ol class="rectorate-steps" aria-label="Progreso del registro">
            <li class="is-complete"><span><i class="ti ti-check"></i></span><div><strong>Titular</strong><small>{{ $application->first_name }} {{ $application->paternal_surname }}</small></div></li>
            <li class="is-active" aria-current="step"><span>2</span><div><strong>Programa</strong><small>Programa y plan</small></div></li>
            <li><span>3</span><div><strong>Estudiante</strong><small>Datos básicos</small></div></li>
            <li><span>4</span><div><strong>Confirmación</strong><small>Próximamente</small></div></li>
        </ol>

        <form method="POST" action="{{ route('rectorate.applications.plan.update', $application) }}" class="card rectorate-form" data-program-plan-form>
            @csrf
            @method('PUT')
            <div class="card-body p-3 p-lg-4">
                <div class="rectorate-section-heading">
                    <span class="rectorate-section-heading__icon"><i class="ti ti-books"></i></span>
                    <div><h3>Selección académica</h3></div>
                </div>

                @if ($programs->isEmpty())
                    <div class="alert alert-warning mb-0"><i class="ti ti-alert-triangle me-2"></i>No existen programas con planes para la empresa activa.</div>
                @else
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label required" for="program_id">Programa</label><select class="form-select @error('program_id') is-invalid @enderror" id="program_id" name="program_id" required><option value="">Seleccionar...</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected((int) old('program_id', $application->program_id) === $program->id)>{{ $program->title }} · {{ $program->enrollment_code ?: 'Sin código' }} · {{ $program->duration_months }} meses</option>@endforeach</select>@error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label required" for="plan_id">Plan</label><select class="form-select @error('plan_id') is-invalid @enderror" id="plan_id" name="plan_id" required><option value="">Selecciona primero un programa</option>@foreach ($programs as $program) @foreach ($program->plans as $plan)<option value="{{ $plan->id }}" data-program-id="{{ $program->id }}" @selected((int) old('plan_id', $application->plan_id) === $plan->id)>{{ $plan->name }} · Bs {{ number_format((float) $plan->monthly_cost, 2, ',', '.') }}</option>@endforeach @endforeach</select>@error('plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label required" for="commercial_origin_id">Origen comercial</label><select class="form-select @error('commercial_origin_id') is-invalid @enderror" id="commercial_origin_id" name="commercial_origin_id" required><option value="">Seleccionar...</option>@foreach ($commercialOrigins as $origin)<option value="{{ $origin->id }}" @selected((int) old('commercial_origin_id', $application->commercial_origin_id) === $origin->id)>{{ $origin->name }}</option>@endforeach</select>@error('commercial_origin_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @if($commercialOrigins->isEmpty())<div class="form-hint text-warning">No hay orígenes comerciales registrados.</div>@endif</div>
                        <div class="col-md-6"><label class="form-label required" for="sales_executive_id">Ejecutivo de ventas</label><select class="form-select @error('sales_executive_id') is-invalid @enderror" id="sales_executive_id" name="sales_executive_id" required><option value="">Seleccionar...</option>@foreach ($salesExecutives as $executive)<option value="{{ $executive->id }}" @selected((int) old('sales_executive_id', $application->sales_executive_id) === $executive->id)>{{ $executive->full_name }}</option>@endforeach</select>@error('sales_executive_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @if($salesExecutives->isEmpty())<div class="form-hint text-warning">No hay personal activo con el cargo Ejecutivo de Ventas.</div>@endif</div>
                    </div>
                @endif
            </div>
            <div class="card-footer rectorate-form__footer">
                <a class="btn btn-outline-secondary" href="{{ route('rectorate.applications.holder.edit', $application) }}"><i class="ti ti-arrow-left me-1"></i>Volver al titular</a>
                <button class="btn btn-primary" type="submit" @disabled($programs->isEmpty() || $commercialOrigins->isEmpty() || $salesExecutives->isEmpty())>Continuar al estudiante <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
@endsection
