@extends('layouts.admin')
@section('title', 'Cuenta del estudiante')
@section('page-title', 'Cuenta del estudiante')
@section('page-subtitle', trim($student->first_name.' '.$student->paternal_surname.' '.$student->maternal_surname).' · CI '.$student->identity_document)
@section('content')
<a class="btn btn-outline-secondary mb-3" href="{{ route('additional-charges.index') }}">Volver a estudiantes</a>
<div class="row g-3 mb-3">
    @foreach(['Total cargos' => $totals->charged, 'Total aplicado' => $totals->paid, 'Saldo pendiente' => $totals->charged - $totals->paid] as $label => $amount)
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-secondary">{{ $label }}</div><div class="h2 mb-0">Bs {{ number_format($amount, 2, ',', '.') }}</div></div></div></div>
    @endforeach
</div>
@can('additional-charges.manage')
<x-ui.card title="Agregar cargo adicional" class="mb-3">
    <div class="card-body">
    @if($contracts->where('status', '!=', 'cancelled')->isEmpty())
        <div class="alert alert-info mb-0">El estudiante no tiene una cuenta vigente a la cual agregar cargos.</div>
    @elseif($services->isEmpty())
        <div class="alert alert-info mb-0">No hay servicios activos configurados para registrar cargos.</div>
    @else
        <form class="row g-3" method="POST" action="{{ route('additional-charges.store', $student) }}">
            @csrf
            <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
            <div class="col-md-5"><label class="form-label required" for="charge-contract">Cuenta / Programa</label><select class="form-select @error('enrollment_contract_id') is-invalid @enderror" id="charge-contract" name="enrollment_contract_id" required><option value="">Seleccione una cuenta</option>@foreach($contracts->where('status', '!=', 'cancelled') as $contract)<option value="{{ $contract->id }}" @selected((int) old('enrollment_contract_id', $contracts->where('status', '!=', 'cancelled')->count() === 1 ? $contract->id : null) === $contract->id)>{{ $contract->program->title }} · {{ $contract->account_number ?: '#'.$contract->contract_number }}</option>@endforeach</select><div class="invalid-feedback">@error('enrollment_contract_id'){{ $message }}@enderror</div></div>
            <div class="col-md-5"><label class="form-label required" for="charge-service">Servicio / Costo</label><select class="form-select @error('additional_service_id') is-invalid @enderror" name="additional_service_id" id="charge-service" required><option value="">Seleccione un servicio</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected((int) old('additional_service_id') === $service->id)>{{ $service->title }} · Bs {{ number_format($service->cost, 2, ',', '.') }}</option>@endforeach</select><div class="invalid-feedback">@error('additional_service_id'){{ $message }}@enderror</div></div>
            <div class="col-md-2"><label class="form-label required" for="charge-due-date">Vencimiento</label><input class="form-control @error('due_date') is-invalid @enderror" type="date" name="due_date" id="charge-due-date" value="{{ old('due_date', today()->toDateString()) }}" required><div class="invalid-feedback">@error('due_date'){{ $message }}@enderror</div></div>
            <div class="col-12"><details><summary>Ver detalle de los servicios disponibles</summary><dl class="mt-2">@foreach($services as $service)<dt>{{ $service->title }} · Bs {{ number_format($service->cost, 2, ',', '.') }}</dt><dd class="text-break">{{ $service->detail }}</dd>@endforeach</dl></details></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Agregar cargo a la cuenta</button></div>
        </form>
    @endif
    </div>
</x-ui.card>
@endcan
<x-ui.table-card title="Todos los cargos">
    <table class="table table-hover align-middle"><thead><tr><th>Cuenta / Programa</th><th>Concepto / Detalle</th><th>Tipo / Periodo</th><th>Vencimiento</th><th class="text-end">Importe</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th><th>Estado</th></tr></thead><tbody>
        @forelse($charges as $charge)
            <tr><td>{{ $charge->contract->program->title }}<small class="d-block text-secondary">{{ $charge->contract->account_number ?: '#'.$charge->contract->contract_number }}</small>@can('accounts.collect')<a class="small" href="{{ route('rectorate.contracts.account.show', $charge->enrollment_contract_id) }}">Ver cuenta y cobrar</a>@endcan</td><td>{{ $charge->concept ?: 'Mensualidad' }}<small class="d-block text-secondary text-break">{{ $charge->detail }}</small></td><td>{{ $charge->charge_type === 'additional' ? 'Adicional' : 'Mensualidad' }}<small class="d-block text-secondary">{{ $charge->period?->format('m/Y') }}</small></td><td>{{ $charge->due_date->format('d/m/Y') }}</td><td class="text-end text-nowrap">Bs {{ number_format($charge->amount, 2, ',', '.') }}</td><td class="text-end text-nowrap">Bs {{ number_format($charge->paid_amount, 2, ',', '.') }}</td><td class="text-end text-nowrap">Bs {{ number_format($charge->status === 'cancelled' ? 0 : $charge->amount - $charge->paid_amount, 2, ',', '.') }}</td><td><span class="badge {{ $charge->status === 'paid' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado', 'cancelled' => 'Anulado'][$charge->status] }}</span></td></tr>
        @empty
            <x-ui.empty-row colspan="8" message="El estudiante no tiene cargos registrados." />
        @endforelse
    </tbody></table>
    <x-slot:footer>{{ $charges->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
